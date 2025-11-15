<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>To-Do</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="assets/style.css" />
  </head>
  <body>
    <main class="app-shell">
      <header class="hero">
        <div>
          <p class="eyebrow">Daily Productivity</p>
          <h1>To-Do</h1>
          <p class="tagline">
            Manage your tasks easily and stay productive every day.
          </p>
        </div>
        <div class="stats">
          <div>
            <span id="totalCount">0</span>
            <p>Total tasks</p>
          </div>
          <div>
            <span id="activeCount">0</span>
            <p>Incomplete</p>
          </div>
          <div>
            <span id="completedCount">0</span>
            <p>Completed</p>
          </div>
        </div>
      </header>

      <section class="grid">
        <form id="todoForm" class="card form-pane" autocomplete="off">
          <div class="form-header">
            <h2 id="formTitle">Add Task</h2>
            <button type="button" id="cancelEditBtn" class="ghost" hidden>
              Cancel edit
            </button>
          </div>

          <label class="field">
            <span>Task title *</span>
            <input
              type="text"
              name="title"
              id="titleInput"
              placeholder="Example: Review weekly report"
              required
            />
          </label>

          <label class="field">
            <span>Description</span>
            <textarea
              name="description"
              rows="3"
              placeholder="Additional details, links, or steps"
            ></textarea>
          </label>

          <div class="field-row">
            <label class="field">
              <span>Due date</span>
              <input type="date" name="dueDate" />
            </label>

            <label class="field">
              <span>Priority</span>
              <select name="priority">
                <option value="low">Low</option>
                <option value="medium" selected>Medium</option>
                <option value="high">High</option>
              </select>
            </label>
          </div>

          <button type="submit" class="primary" id="submitBtn">
            Save Task
          </button>
        </form>

        <section class="card list-pane">
          <div class="list-toolbar">
            <input
              type="search"
              id="searchInput"
              placeholder="Search tasks..."
            />

            <select id="statusFilter">
              <option value="all" selected>All statuses</option>
              <option value="active">Active</option>
              <option value="completed">Completed</option>
            </select>

            <select id="priorityFilter">
              <option value="all" selected>All priorities</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
            </select>

            <button type="button" id="clearCompletedBtn" class="ghost">
              Clear completed
            </button>
          </div>

          <ul id="todoList" class="todo-list"></ul>
          <p id="emptyState" class="empty" hidden>
            No tasks yet. Add your first task!
          </p>
        </section>
      </section>
    </main>

    <div id="toast" class="toast" role="status" aria-live="polite"></div>

    <script src="assets/app.js" defer></script>
  </body>
</html>
