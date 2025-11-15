'use strict';

const API_URL = 'api.php';
const todoForm = document.querySelector('#todoForm');
const titleInput = document.querySelector('#titleInput');
const cancelEditBtn = document.querySelector('#cancelEditBtn');
const submitBtn = document.querySelector('#submitBtn');
const formTitle = document.querySelector('#formTitle');
const todoListEl = document.querySelector('#todoList');
const emptyStateEl = document.querySelector('#emptyState');
const statusFilterEl = document.querySelector('#statusFilter');
const priorityFilterEl = document.querySelector('#priorityFilter');
const searchInputEl = document.querySelector('#searchInput');
const clearCompletedBtn = document.querySelector('#clearCompletedBtn');
const toastEl = document.querySelector('#toast');
const totalCountEl = document.querySelector('#totalCount');
const activeCountEl = document.querySelector('#activeCount');
const completedCountEl = document.querySelector('#completedCount');

let todos = [];
let editingId = null;

const fetchJSON = async (url, options) => {
  const response = await fetch(url, options);
  const payload = await response.json().catch(() => ({ success: false, message: 'Respons tidak valid' }));
  if (!response.ok || !payload.success) {
    throw new Error(payload.message || 'Terjadi kesalahan pada server');
  }
  return payload;
};

const showToast = (message, isError = false) => {
  toastEl.textContent = message;
  toastEl.classList.toggle('error', isError);
  toastEl.classList.add('show');
  setTimeout(() => toastEl.classList.remove('show'), 2600);
};

const refreshStats = () => {
  const total = todos.length;
  const completed = todos.filter((todo) => todo.isDone).length;
  const active = total - completed;
  totalCountEl.textContent = total;
  activeCountEl.textContent = active;
  completedCountEl.textContent = completed;
};

const setFormMode = (mode, todo = null) => {
  if (mode === 'edit' && todo) {
    editingId = todo.id;
    formTitle.textContent = 'Edit Tugas';
    submitBtn.textContent = 'Perbarui Tugas';
    cancelEditBtn.hidden = false;
    titleInput.focus();
    todoForm.title.value = todo.title;
    todoForm.description.value = todo.description || '';
    todoForm.dueDate.value = todo.dueDate || '';
    todoForm.priority.value = todo.priority;
  } else {
    editingId = null;
    formTitle.textContent = 'Tambah Tugas';
    submitBtn.textContent = 'Simpan Tugas';
    cancelEditBtn.hidden = true;
    todoForm.reset();
    todoForm.priority.value = 'medium';
    titleInput.focus();
  }
};

const renderTodos = () => {
  const searchTerm = searchInputEl.value.trim().toLowerCase();
  const statusFilter = statusFilterEl.value;
  const priorityFilter = priorityFilterEl.value;

  const filtered = todos
    .filter((todo) => {
      const matchesSearch = todo.title.toLowerCase().includes(searchTerm) || (todo.description || '').toLowerCase().includes(searchTerm);
      const matchesStatus =
        statusFilter === 'all' || (statusFilter === 'active' && !todo.isDone) || (statusFilter === 'completed' && todo.isDone);
      const matchesPriority = priorityFilter === 'all' || todo.priority === priorityFilter;
      return matchesSearch && matchesStatus && matchesPriority;
    })
    .sort((a, b) => {
      if (a.isDone !== b.isDone) {
        return a.isDone ? 1 : -1;
      }
      return new Date(a.createdAt) - new Date(b.createdAt);
    });

  todoListEl.innerHTML = '';
  emptyStateEl.hidden = filtered.length !== 0;

  filtered.forEach((todo) => {
    const item = document.createElement('li');
    item.className = `todo-item ${todo.isDone ? 'done' : ''}`;

    const checkboxWrapper = document.createElement('div');
    checkboxWrapper.className = 'checkbox-wrapper';
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.checked = todo.isDone;
    checkbox.ariaLabel = `Tandai ${todo.title}`;
    checkbox.addEventListener('change', () => handleToggle(todo));
    checkboxWrapper.appendChild(checkbox);

    const body = document.createElement('div');
    body.className = 'todo-body';
    const title = document.createElement('p');
    title.className = 'todo-title';
    title.innerHTML = `${todo.title} ${todo.isDone ? '<span>(Selesai)</span>' : ''}`;
    body.appendChild(title);

    if (todo.description) {
      const desc = document.createElement('p');
      desc.className = 'todo-desc';
      desc.textContent = todo.description;
      body.appendChild(desc);
    }

    const meta = document.createElement('div');
    meta.className = 'meta';
    if (todo.dueDate) {
      const due = document.createElement('span');
      due.className = 'badge due';
      const formatted = new Date(todo.dueDate).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      });
      due.textContent = `Tenggat: ${formatted}`;
      meta.appendChild(due);
    }

    const priority = document.createElement('span');
    priority.className = `badge priority-${todo.priority}`;
    priority.textContent = `Prioritas ${todo.priority}`;
    meta.appendChild(priority);

    const created = document.createElement('span');
    created.className = 'badge';
    created.textContent = new Date(todo.createdAt).toLocaleString('id-ID');
    meta.appendChild(created);
    body.appendChild(meta);

    const actions = document.createElement('div');
    actions.className = 'todo-actions';

    const editBtn = document.createElement('button');
    editBtn.type = 'button';
    editBtn.textContent = 'Edit';
    editBtn.addEventListener('click', () => setFormMode('edit', todo));

    const deleteBtn = document.createElement('button');
    deleteBtn.type = 'button';
    deleteBtn.textContent = 'Hapus';
    deleteBtn.addEventListener('click', () => handleDelete(todo.id));

    actions.append(editBtn, deleteBtn);

    item.append(checkboxWrapper, body, actions);
    todoListEl.appendChild(item);
  });

  refreshStats();
};

const handleError = (error) => {
  console.error(error);
  showToast(error.message, true);
};

const loadTodos = async () => {
  try {
    const payload = await fetchJSON(API_URL, { headers: { Accept: 'application/json' } });
    todos = payload.data.todos ?? payload.data ?? [];
    renderTodos();
  } catch (error) {
    handleError(error);
  }
};

const apiRequest = async (action, body = {}) => {
  return await fetchJSON(API_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ action, ...body }),
  });
};

const handleToggle = async (todo) => {
  try {
    await apiRequest('toggle', { id: todo.id, isDone: !todo.isDone });
    await loadTodos();
  } catch (error) {
    handleError(error);
  }
};

const handleDelete = async (id) => {
  if (!confirm('Hapus tugas ini?')) return;
  try {
    await apiRequest('delete', { id });
    await loadTodos();
    if (editingId === id) setFormMode('create');
    showToast('Tugas dihapus');
  } catch (error) {
    handleError(error);
  }
};

const handleSubmit = async (event) => {
  event.preventDefault();
  submitBtn.disabled = true;
  submitBtn.textContent = editingId ? 'Menyimpan...' : 'Menambahkan...';

  const formData = new FormData(todoForm);
  const payload = {
    title: formData.get('title')?.trim(),
    description: formData.get('description')?.trim(),
    dueDate: formData.get('dueDate'),
    priority: formData.get('priority'),
  };

  if (!payload.title) {
    showToast('Judul wajib diisi', true);
    submitBtn.disabled = false;
    submitBtn.textContent = editingId ? 'Perbarui Tugas' : 'Simpan Tugas';
    return;
  }

  try {
    if (editingId) {
      await apiRequest('update', { id: editingId, ...payload });
      showToast('Tugas diperbarui');
    } else {
      await apiRequest('add', payload);
      showToast('Tugas ditambahkan');
    }
    await loadTodos();
    setFormMode('create');
  } catch (error) {
    handleError(error);
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = editingId ? 'Perbarui Tugas' : 'Simpan Tugas';
  }
};

const handleClearCompleted = async () => {
  if (!todos.some((todo) => todo.isDone)) {
    showToast('Tidak ada tugas selesai', true);
    return;
  }
  if (!confirm('Bersihkan semua tugas yang sudah selesai?')) return;
  try {
    await apiRequest('clearCompleted');
    await loadTodos();
    showToast('Tugas selesai dibersihkan');
  } catch (error) {
    handleError(error);
  }
};

statusFilterEl.addEventListener('change', renderTodos);
priorityFilterEl.addEventListener('change', renderTodos);
searchInputEl.addEventListener('input', () => {
  window.requestAnimationFrame(renderTodos);
});
clearCompletedBtn.addEventListener('click', handleClearCompleted);
cancelEditBtn.addEventListener('click', () => setFormMode('create'));
todoForm.addEventListener('submit', handleSubmit);

document.addEventListener('DOMContentLoaded', () => {
  setFormMode('create');
  loadTodos();
});
