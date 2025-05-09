<!DOCTYPE html>
<html>

<head>
    <title>AJAX Task Manager</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .completed-row {
            background-color: #e2e3e5;
            text-decoration: line-through;
            color: #6c757d;
        }

        .badge-status {
            font-size: 0.85rem;
            padding: 0.4em 0.6em;
        }

        .card {
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        .form-control,
        .btn {
            border-radius: 8px;
        }

        .btn-toggle-status {
            font-size: 0.8rem;
            padding: 0.25rem 0.7rem;
        }

        #alert-area {
            position: fixed;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            width: auto;
            max-width: 90%;
        }
    </style>
</head>

<body>
    <div id="alert-area"></div>

    <div class="container mt-5">
        <h2 class="mb-4 text-center fw-bold">AJAX Task Manager</h2>

        <!-- Create Form -->
        <div class="card mb-4 bg-light border-0">
            <div class="card-header bg-primary text-white">Create New Task</div>
            <div class="card-body">
                <form id="create-task-form">
                    <div class="mb-3">
                        <input type="text" name="title" class="form-control" placeholder="Title" required>
                    </div>
                    <div class="mb-3">
                        <textarea name="description" class="form-control" placeholder="Description"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success fw-bold">Create Task</button>
                </form>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="mb-3">
            <input type="text" id="search" class="form-control" placeholder="Search tasks by title...">
        </div>

        <!-- Task List -->
        <div id="task-table"></div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form id="edit-task-form" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Task</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="task_id" id="edit-task-id">
                        <div class="mb-3">
                            <input type="text" name="title" id="edit-title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <textarea name="description" id="edit-description" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary fw-bold">Update Task</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery & Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            loadTasks();

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            function showAlert(message, type = 'success') {
                const alert = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
                $('#alert-area').html(alert);
                setTimeout(() => $('.alert').alert('close'), 3000);
            }

            function loadTasks(page = 1, search = '') {
                $.get('/fetch-tasks', {
                    page,
                    search
                }, function(res) {
                    let tasks = res.tasks.data;
                    let html = '<table class="table table-bordered table-hover bg-white shadow-sm"><thead class="table-light"><tr><th>Title</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead><tbody>';

                    if (tasks.length === 0) {
                        html += '<tr><td colspan="4" class="text-center">No tasks found.</td></tr>';
                    } else {
                        tasks.forEach(task => {
                            let statusBadge = task.completed ?
                                '<span class="badge bg-success badge-status">Completed</span>' :
                                '<span class="badge bg-warning text-dark badge-status">Pending</span>';

                            let toggleBtn = `<button class="btn btn-sm btn-outline-${task.completed ? 'secondary' : 'success'} btn-toggle-status" data-id="${task.id}">
                                ${task.completed ? 'Mark Pending' : 'Mark Done'}
                            </button>`;

                            html += `<tr class="${task.completed ? 'completed-row' : ''}">
                                <td>${task.title}</td>
                                <td>${task.description}</td>
                                <td>${statusBadge}</td>
                                <td>
                                    ${toggleBtn}
                                    <button class="btn btn-sm btn-primary edit-btn ms-1" data-id="${task.id}" data-title="${task.title}" data-description="${task.description}">Edit</button>
                                    <button class="btn btn-sm btn-danger delete-btn ms-1" data-id="${task.id}">Delete</button>
                                </td>
                            </tr>`;
                        });
                    }

                    html += '</tbody></table>';

                    if (res.tasks.last_page > 1) {
                        html += '<nav><ul class="pagination justify-content-center">';
                        for (let i = 1; i <= res.tasks.last_page; i++) {
                            html += `<li class="page-item ${res.tasks.current_page === i ? 'active' : ''}">
                                <a href="#" class="page-link" data-page="${i}">${i}</a>
                            </li>`;
                        }
                        html += '</ul></nav>';
                    }

                    $('#task-table').html(html);
                });
            }

            $('#create-task-form').submit(function(e) {
                e.preventDefault();
                $.post('/tasks', $(this).serialize(), function(res) {
                    $('#create-task-form')[0].reset();
                    loadTasks();
                    showAlert(res.message);
                }).fail(function(xhr) {
                    alert(xhr.responseJSON.message || 'Validation failed');
                });
            });

            $(document).on('click', '.page-link', function(e) {
                e.preventDefault();
                let page = $(this).data('page');
                let search = $('#search').val();
                loadTasks(page, search);
            });

            $('#search').on('input', function() {
                loadTasks(1, $(this).val());
            });

            $(document).on('click', '.edit-btn', function() {
                $('#edit-task-id').val($(this).data('id'));
                $('#edit-title').val($(this).data('title'));
                $('#edit-description').val($(this).data('description'));
                $('#editModal').modal('show');
            });

            $('#edit-task-form').submit(function(e) {
                e.preventDefault();
                let id = $('#edit-task-id').val();
                $.ajax({
                    url: '/tasks/' + id,
                    type: 'PUT',
                    data: {
                        title: $('#edit-title').val(),
                        description: $('#edit-description').val()
                    },
                    success: function(res) {
                        $('#editModal').modal('hide');
                        loadTasks();
                        showAlert(res.message);
                    }
                });
            });

            $(document).on('click', '.delete-btn', function() {
                if (confirm('Are you sure you want to delete this task?')) {
                    let id = $(this).data('id');
                    $.ajax({
                        url: '/tasks/' + id,
                        type: 'DELETE',
                        success: function(res) {
                            loadTasks();
                            showAlert(res.message, 'warning');
                        }
                    });
                }
            });

            $(document).on('click', '.btn-toggle-status', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: '/tasks/' + id + '/toggle',
                    type: 'PATCH',
                    success: function(res) {
                        loadTasks();
                        showAlert(res.message);
                    }
                });
            });
        });
    </script>
</body>

</html>