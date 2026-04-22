<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Комментарии</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <style>
            body {
                background: #f8f9fa;
            }

            .comment-card {
                transition: all 0.3s;
            }

            .comment-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            }

            .header-gradient {
                background: linear-gradient(90deg, #0d6efd, #6610f2);
                color: white;
            }

            @media (max-width: 768px) {
                .comment-card {
                    margin-bottom: 1rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="container py-4">
            <div class="header-gradient p-4 rounded-3 mb-4 text-center">
                <h1 class="display-5 fw-bold">Список комментариев</h1>
            </div>

            <!-- Фильтры -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Сортировать по</label>
                            <select id="sortBy" class="form-select">
                                <option value="id">ID</option>
                                <option value="created_at" selected>Дата добавления</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Направление</label>
                            <select id="order" class="form-select">
                                <option value="desc" selected>По убыванию</option>
                                <option value="asc">По возрастанию</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button id="apply-sort" class="btn btn-primary w-100">Применить сортировку</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="comments-list" class="mb-5"></div>

            <div id="pagination" class="d-flex justify-content-center mb-5"></div>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Добавить комментарий</h5>
                </div>
                <div class="card-body">
                    <form id="add-form">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="name" id="email" class="form-control" required>
                            <div id="email-error" class="text-danger small"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Текст комментария</label>
                            <textarea name="text" id="text" class="form-control" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Отправить комментарий</button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            let currentPage = 1;
            let currentSort = 'created_at';
            let currentOrder = 'desc';

            function loadComments(page = 1, sort = null, order = null) {
                if (sort !== null) currentSort = sort;
                if (order !== null) currentOrder = order;

                currentPage = Math.max(1, parseInt(page));

                $.get('/comments/list', {
                    page: currentPage,
                    sort: currentSort,
                    order: currentOrder
                }, function (data) {
                    let html = '';

                    if (!data.comments || data.comments.length === 0) {
                        html = `<div class="alert alert-info text-center py-5">Пока нет комментариев. Добавьте первый!</div>`;
                    } else {
                        data.comments.forEach(comment => {
                            const date = new Date(comment.created_at).toLocaleString('ru-RU', {
                                year: 'numeric', month: 'long', day: 'numeric',
                                hour: '2-digit', minute: '2-digit'
                            });

                            html += `
                    <div class="card comment-card mb-3 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong class="text-primary fs-5">${comment.name}</strong>
                                    <small class="text-muted d-block mt-1">${date}</small>
                                </div>
                                <button onclick="deleteComment(${comment.id}); return false;"
                                        class="btn btn-sm btn-outline-danger">Удалить</button>
                            </div>
                            <div class="mt-3">${comment.text.replace(/\n/g, '<br>')}</div>
                        </div>
                    </div>`;
                        });
                    }

                    $('#comments-list').html(html);

                    // Улучшенная пагинация с стрелочками и многоточием
                    renderPagination(data.pagination || {pages: 1, current: 1});

                }).fail(function () {
                    $('#comments-list').html('<div class="alert alert-danger">Ошибка загрузки комментариев. Попробуйте обновить страницу.</div>');
                });
            }

            function renderPagination(pagination) {
                const totalPages = pagination.pages || 1;
                const current = pagination.current || 1;

                if (totalPages <= 1) {
                    $('#pagination').html('');
                    return;
                }

                let html = `<nav aria-label="Пагинация комментариев"><ul class="pagination justify-content-center flex-wrap">`;

                // Кнопка "Предыдущая"
                const prevDisabled = (current <= 1) ? ' disabled' : '';
                html += `
            <li class="page-item${prevDisabled}">
                <a class="page-link" href="#" onclick="loadComments(${current - 1}); return false;" aria-label="Предыдущая">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>`;

                // Логика отображения страниц (максимум ~7 элементов)
                const maxVisible = 5; // сколько номеров показывать
                let startPage = Math.max(1, current - Math.floor(maxVisible / 2));
                let endPage = Math.min(totalPages, startPage + maxVisible - 1);

                if (endPage - startPage + 1 < maxVisible) {
                    startPage = Math.max(1, endPage - maxVisible + 1);
                }

                // Первая страница + многоточие слева
                if (startPage > 1) {
                    html += `<li class="page-item"><a class="page-link" href="#" onclick="loadComments(1); return false;">1</a></li>`;
                    if (startPage > 2) {
                        html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
                    }
                }

                // Основные номера страниц
                for (let i = startPage; i <= endPage; i++) {
                    const active = (i === current) ? ' active' : '';
                    html += `
                <li class="page-item${active}">
                    <a class="page-link" href="#" onclick="loadComments(${i}); return false;">${i}</a>
                </li>`;
                }

                // Последняя страница + многоточие справа
                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
                    }
                    html += `<li class="page-item"><a class="page-link" href="#" onclick="loadComments(${totalPages}); return false;">${totalPages}</a></li>`;
                }

                // Кнопка "Следующая"
                const nextDisabled = (current >= totalPages) ? ' disabled' : '';
                html += `
            <li class="page-item${nextDisabled}">
                <a class="page-link" href="#" onclick="loadComments(${current + 1}); return false;" aria-label="Следующая">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>`;

                html += `</ul></nav>`;

                $('#pagination').html(html);
            }

            function deleteComment(id) {
                if (!confirm('Вы действительно хотите удалить этот комментарий?')) return;

                $.get(`/comments/delete/${id}`, function (res) {
                    if (res.success) {
                        loadComments(currentPage);   // остаёмся на той же странице
                    } else {
                        alert('Не удалось удалить комментарий');
                    }
                });
            }

            $(document).ready(function () {
                // Начальная загрузка
                loadComments(1, 'created_at', 'desc');

                // Применить сортировку
                $('#apply-sort').on('click', function () {
                    const sortBy = $('#sortBy').val();
                    const order = $('#order').val();
                    loadComments(1, sortBy, order);
                });

                // Форма добавления
                $('#add-form').on('submit', function (e) {
                    e.preventDefault();
                    $('#email-error').text('');

                    const email = $('#email').val().trim();
                    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        $('#email-error').text('Введите корректный email адрес');
                        return;
                    }

                    $.post('/comments/add', $(this).serialize(), function (res) {
                        if (res.success) {
                            $('#add-form')[0].reset();
                            loadComments(1, currentSort, currentOrder); // сбрасываем на первую страницу

                            // Красивое уведомление
                            const toastHTML = `
                        <div class="toast align-items-center text-white bg-success border-0 position-fixed bottom-0 end-0 m-3" role="alert">
                            <div class="d-flex">
                                <div class="toast-body">Комментарий успешно добавлен!</div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                            </div>
                        </div>`;
                            const toastEl = $(toastHTML).appendTo('body');
                            new bootstrap.Toast(toastEl[0], {delay: 3000}).show();
                            setTimeout(() => toastEl.remove(), 4000);
                        } else {
                            if (res.errors && res.errors.name) {
                                $('#email-error').text(res.errors.name);
                            } else {
                                alert('Ошибка при добавлении комментария');
                            }
                        }
                    }, 'json');
                });
            });
        </script>
    </body>
</html>