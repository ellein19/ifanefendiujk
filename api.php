<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

const DB_HOST = 'localhost';
const DB_PORT = 5432;
const DB_NAME = 'todolist';
const DB_USER = 'postgres';
const DB_PASSWORD = 'root';

/**
 * @param bool $success
 * @param string $message
 * @param array|null $data
 * @param int $status
 * @return void
 */
function respond(bool $success, string $message = '', ?array $data = null, int $status = 200): void
{
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * @return \PDO
 */
function getDbConnection(): \PDO
{
    $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s;', DB_HOST, DB_PORT, DB_NAME);
    try {
        return new \PDO($dsn, DB_USER, DB_PASSWORD, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (\PDOException $exception) {
        respond(false, 'Gagal tersambung ke database: ' . $exception->getMessage(), null, 500);
    }
}

/**
 * @param \PDO $pdo
 * @return void
 */
function ensureSchema(\PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS todos (
            id TEXT PRIMARY KEY,
            title TEXT NOT NULL,
            description TEXT,
            due_date DATE,
            priority TEXT NOT NULL CHECK (priority IN (\'low\', \'medium\', \'high\')),
            is_done BOOLEAN NOT NULL DEFAULT FALSE,
            is_archived BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMPTZ NOT NULL DEFAULT now()
        )'
    );
}

/**
 * @return array
 */
function getPayload(): array
{
    $raw = file_get_contents('php://input');
    if ($raw) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
    }

    return $_POST ?? [];
}

/**
 * @param array $row
 * @return array
 */
function mapRowToTodo(array $row): array
{
    $createdAt = $row['created_at'] ?? null;
    if ($createdAt !== null) {
        try {
            $createdAt = (new \DateTimeImmutable($createdAt))
                ->setTimezone(new \DateTimeZone('Asia/Jakarta'))
                ->format(\DateTimeInterface::ATOM);
        } catch (\Throwable) {
            $createdAt = $row['created_at'];
        }
    }

    return [
        'id' => $row['id'],
        'title' => $row['title'],
        'isArchived' => isset($row['is_archived']) ? (bool)$row['is_archived'] : false,
        'description' => $row['description'] ?? null,
        'dueDate' => $row['due_date'] ?? null,
        'priority' => $row['priority'],
        'isDone' => isset($row['is_done']) ? (bool)$row['is_done'] : false,
        'createdAt' => $createdAt,
    ];
}

/**
 * @param \PDO $pdo
 * @return array
 */
function fetchTodos(\PDO $pdo): array
{
    $query = $pdo->query('SELECT id, title, description, due_date, priority, is_done, is_archived, created_at FROM todos WHERE is_archived = FALSE ORDER BY created_at ASC');
    $rows = $query->fetchAll();
    return array_map('mapRowToTodo', $rows ?: []);
}

/**
 * @param \PDO $pdo
 * @param string $id
 * @param string $title
 * @param string|null $description
 * @param string|null $dueDate
 * @param string $priority
 * @return void
 */
function insertTodo(\PDO $pdo, string $id, string $title, ?string $description, ?string $dueDate, string $priority): void
{
    $statement = $pdo->prepare(
        'INSERT INTO todos (id, title, description, due_date, priority, is_done, is_archived) VALUES (:id, :title, :description, :due_date, :priority, FALSE, FALSE)'
    );
    $statement->execute([
        ':id' => $id,
        ':title' => $title,
        ':description' => $description,
        ':due_date' => $dueDate,
        ':priority' => $priority,
    ]);
}

/**
 * @param \PDO $pdo
 * @param string $id
 * @param string $title
 * @param string|null $description
 * @param string|null $dueDate
 * @param string $priority
 * @return int
 */
function updateTodo(\PDO $pdo, string $id, string $title, ?string $description, ?string $dueDate, string $priority): int
{
    $statement = $pdo->prepare(
        'UPDATE todos SET title = :title, description = :description, due_date = :due_date, priority = :priority WHERE id = :id'
    );
    $statement->execute([
        ':title' => $title,
        ':description' => $description,
        ':due_date' => $dueDate,
        ':priority' => $priority,
        ':id' => $id,
    ]);

    return $statement->rowCount();
}

/**
 * @param \PDO $pdo
 * @param string $id
 * @param bool $isDone
 * @return int
 */
function toggleTodo(\PDO $pdo, string $id, bool $isDone): int
{
    $statement = $pdo->prepare('UPDATE todos SET is_done = :is_done WHERE id = :id');
    $statement->bindValue(':is_done', $isDone, \PDO::PARAM_BOOL);
    $statement->bindValue(':id', $id, \PDO::PARAM_STR);
    $statement->execute();

    return $statement->rowCount();
}

/**
 * @param \PDO $pdo
 * @param string $id
 * @return int
 */
function deleteTodo(\PDO $pdo, string $id): int
{
    $statement = $pdo->prepare('DELETE FROM todos WHERE id = :id');
    $statement->execute([':id' => $id]);
    return $statement->rowCount();
}

/**
 * @param \PDO $pdo
 * @return void
 */
function clearCompletedTodos(\PDO $pdo): void
{
    $pdo->exec('UPDATE todos SET is_archived = TRUE WHERE is_done = TRUE AND is_archived = FALSE');
}

try {
    $pdo = getDbConnection();
    ensureSchema($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $todos = fetchTodos($pdo);
        respond(true, 'Daftar tugas', ['todos' => $todos]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(false, 'Metode tidak didukung', null, 405);
    }

    $payload = getPayload();
    $action = $payload['action'] ?? null;

    if ($action === null) {
        respond(false, 'Parameter action wajib diisi', null, 400);
    }

    switch ($action) {
        case 'add':
            $title = trim((string)($payload['title'] ?? ''));
            if ($title === '') {
                respond(false, 'Judul wajib diisi', null, 422);
            }

            $description = trim((string)($payload['description'] ?? '')) ?: null;
            $dueDate = $payload['dueDate'] ?? null;
            if ($dueDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$dueDate)) {
                respond(false, 'Format tanggal tidak valid (YYYY-MM-DD)', null, 422);
            }

            $priority = strtolower((string)($payload['priority'] ?? 'medium'));
            if (!in_array($priority, ['low', 'medium', 'high'], true)) {
                $priority = 'medium';
            }

            insertTodo($pdo, bin2hex(random_bytes(8)), $title, $description, $dueDate, $priority);
            $todos = fetchTodos($pdo);
            respond(true, 'Tugas ditambahkan', ['todos' => $todos]);

        case 'toggle':
            $id = (string)($payload['id'] ?? '');
            if ($id === '') {
                respond(false, 'ID tugas wajib diisi', null, 422);
            }
            $isDone = isset($payload['isDone']) ? (bool)$payload['isDone'] : null;
            if ($isDone === null) {
                $todos = fetchTodos($pdo);
                $target = array_filter($todos, static fn ($todo) => $todo['id'] === $id);
                if ($target === []) {
                    respond(false, 'Tugas tidak ditemukan', null, 404);
                }
                $first = reset($target);
                $isDone = !$first['isDone'];
            }

            if (toggleTodo($pdo, $id, $isDone) === 0) {
                respond(false, 'Tugas tidak ditemukan', null, 404);
            }
            respond(true, 'Status tugas diperbarui', ['todos' => fetchTodos($pdo)]);

        case 'update':
            $id = (string)($payload['id'] ?? '');
            if ($id === '') {
                respond(false, 'ID tugas wajib diisi', null, 422);
            }

            $title = trim((string)($payload['title'] ?? ''));
            if ($title === '') {
                respond(false, 'Judul wajib diisi', null, 422);
            }
            $description = trim((string)($payload['description'] ?? '')) ?: null;
            $dueDate = $payload['dueDate'] ?? null;
            if ($dueDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$dueDate)) {
                respond(false, 'Format tanggal tidak valid', null, 422);
            }

            $priority = strtolower((string)($payload['priority'] ?? 'medium'));
            if (!in_array($priority, ['low', 'medium', 'high'], true)) {
                $priority = 'medium';
            }

            if (updateTodo($pdo, $id, $title, $description, $dueDate, $priority) === 0) {
                respond(false, 'Tugas tidak ditemukan', null, 404);
            }

            respond(true, 'Tugas diperbarui', ['todos' => fetchTodos($pdo)]);

        case 'delete':
            $id = (string)($payload['id'] ?? '');
            if ($id === '') {
                respond(false, 'ID tugas wajib diisi', null, 422);
            }

            if (deleteTodo($pdo, $id) === 0) {
                respond(false, 'Tugas tidak ditemukan', null, 404);
            }

            respond(true, 'Tugas dihapus', ['todos' => fetchTodos($pdo)]);

        case 'clearCompleted':
            clearCompletedTodos($pdo);
            respond(true, 'Tugas selesai dibersihkan', ['todos' => fetchTodos($pdo)]);

        default:
            respond(false, 'Aksi tidak dikenali', null, 400);
    }
} catch (\Throwable $exception) {
    respond(false, $exception->getMessage(), null, 500);
}
