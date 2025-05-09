<!DOCTYPE html>
<html>

<head>
    <title>AJAX Task Manager</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .completed {
            text-decoration: line-through;
            color: gray;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-4">AJAX Task Manager</h2>


        <!-- Create Form -->
        <div class="card mb-3">
            <div class="card-header">Create New Task</div>
            <div class="card-body">
                <form id="create-task-form">
                    <div class="mb-3">
                        <input type="text" name="title" class="form-control" placeholder="Title" required>
                    </div>
                    <div class="mb-3">
                        <textarea name="description" class="form-control" placeholder="Description"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Create Task</button>
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
                        <button type="submit" class="btn btn-primary">Update Task</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>



    </div> <!-- jQuery & Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            loadTasks();
            //CSRF Setup 
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            // Load tasks with optional search
            function loadTasks(page = 1, search = '') {
                $.get('/fetch-tasks', {
                    page: page,
                    search: search
                }, function(res) {
                    let tasks = res.tasks.data;
                    let html = '<table class="table table-bordered"><thead><tr><th>Title</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
                    if (tasks.length === 0) {
                        html += '<tr><td colspan="4" class="text-center">No tasks found.</td></tr>';
                    } else {
                        tasks.forEach(task => {
                            html += `<tr class="${task.completed ? 'completed' : ''}"> <td>${task.title}</td> <td>${task.description}</td> <td> <input type="checkbox" class="toggle-complete" data-id="${task.id}" ${task.completed ? 'checked' : ''}> </td> <td> <button class="btn btn-sm btn-primary edit-btn" data-id="${task.id}" data-title="${task.title}" data-description="${task.description}">Edit</button> <button class="btn btn-sm btn-danger delete-btn" data-id="${task.id}">Delete</button> </td> </tr>`;
                        });
                    }
                    html += '</tbody></table>';
                    // Pagination Links 
                    if (res.tasks.last_page > 1) {
                        html += '<nav><ul class="pagination">';
                        for (let i = 1; i <= res.tasks.last_page; i++) {
                            html += `<li class="page-item ${res.tasks.current_page === i ? 'active' : ''}"> <a href="#" class="page-link" data-page="${i}">${i}</a></li>`;
                        }
                        html += '</ul></nav>';
                    }
                    $('#task-table').html(html);
                });
            }
            // Create task
            $('#create-task-form').submit(function(e) {
                e.preventDefault();
                $.post('/tasks', $(this).serialize(), function(res) {
                    $('#create-task-form')[0].reset();
                    loadTasks();
                }).fail(function(xhr) {
                    alert(xhr.responseJSON.message || 'Validation failed');
                });
            });
            // Handle pagination
            $(document).on('click', '.page-link', function(e) {
                e.preventDefault();
                let page = $(this).data('page');
                let search = $('#search').val();
                loadTasks(page, search);
            });
            // Search tasks 
            $('#search').on('input', function() {
                loadTasks(1, $(this).val());
            });
            // Open edit modal
            $(document).on('click', '.edit-btn', function() {
                $('#edit-task-id').val($(this).data('id'));
                $('#edit-title').val($(this).data('title'));
                $('#edit-description').val($(this).data('description'));
                $('#editModal').modal('show');
            });
            // Submit edit form
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
                    }
                });
            });
            // Delete task
            $(document).on('click', '.delete-btn', function() {
                if (confirm('Are you sure you want to delete this task?')) {
                    let id = $(this).data('id');
                    $.ajax({
                        url: '/tasks/' + id,
                        type: 'DELETE',
                        success: function(res) {
                            loadTasks();
                        }
                    });
                }
            });
            // Toggle completed
            $(document).on('change', '.toggle-complete', function() {
                let id = $(this).data('id');
                $.ajax({
                    url: '/tasks/' + id + '/toggle',
                    type: 'PATCH',
                    success: function(res) {
                        loadTasks();
                    }
                });
            });
        });
    </script>
</body>

</html>