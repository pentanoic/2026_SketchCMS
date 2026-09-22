<?php
$this->layout('manager/layout', ['page_title' => $page_title ?? 'Diễn đàn', 'panelActive' => 'articles']);
?>

<div class="mb-4">
    <h5 class="font-weight-bold text-uppercase mb-0" style="color:var(--heading); font-size:16px;">
        <i class="fa fa-comments-o" style="color:var(--accent)"></i> Quản lý Diễn đàn
    </h5>
</div>

<?php if (isset($_COOKIE['manager_articles_success'])): ?>
    <div class="alert alert-success">
        <i class="fa fa-check-circle"></i> <?= $_COOKIE['manager_articles_success'] ?>
    </div>
    <?php setcookie('manager_articles_success', '', time() - 3600, '/'); ?>
<?php endif; ?>

<ul class="nav nav-tabs mb-4 border-0" style="border-bottom: 2px solid var(--border) !important;">
    <li class="nav-item">
        <a class="nav-link <?= ($tab === 'categories') ? 'active' : '' ?>" href="<?= url('/manager/articles?tab=categories') ?>" style="<?= ($tab === 'categories') ? 'font-weight:bold; border:none; border-bottom: 3px solid var(--primary); background: transparent; color: var(--primary);' : 'border:none; color: var(--fg-muted);' ?>">Chuyên mục</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($tab === 'posts') ? 'active' : '' ?>" href="<?= url('/manager/articles?tab=posts') ?>" style="<?= ($tab === 'posts') ? 'font-weight:bold; border:none; border-bottom: 3px solid var(--primary); background: transparent; color: var(--primary);' : 'border:none; color: var(--fg-muted);' ?>">Chủ đề</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($tab === 'chapters') ? 'active' : '' ?>" href="<?= url('/manager/articles?tab=chapters') ?>" style="<?= ($tab === 'chapters') ? 'font-weight:bold; border:none; border-bottom: 3px solid var(--primary); background: transparent; color: var(--primary);' : 'border:none; color: var(--fg-muted);' ?>">Chương</a>
    </li>
</ul>

<?php if ($tab === 'categories'): ?>

    <div class="mb-3 text-right">
        <button class="dw-btn dw-btn-primary" data-toggle="modal" data-target="#addCategoryModal"><i class="fa fa-plus"></i> Thêm chuyên mục</button>
    </div>

    <div class="dw-card">
        <div class="table-responsive">
            <table class="table mb-0" style="color: var(--fg);">
                <thead style="background: var(--surface-muted); color: var(--fg-muted);">
                    <tr>
                        <th width="50" class="border-top-0">ID</th>
                        <th class="border-top-0">Tên chuyên mục</th>
                        <th class="border-top-0">Slug</th>
                        <th width="120" class="text-right border-top-0">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Chưa có chuyên mục nào.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $c): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td class="align-middle border-top-0"><?= $c['id'] ?></td>
                                <td class="align-middle border-top-0 font-weight-bold" style="color:var(--heading);"><?= $c['name'] ?></td>
                                <td class="align-middle border-top-0">
                                    <span style="background: var(--surface-muted); padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; border: 1px solid var(--border);"><?= $c['slug'] ?></span>
                                </td>
                                <td class="text-right align-middle border-top-0">
                                    <button class="dw-btn dw-btn-sm" style="background: var(--surface-muted); color: var(--primary); border: 1px solid var(--border);" data-toggle="modal" data-target="#editCatModal<?= $c['id'] ?>"><i class="fa fa-pencil"></i></button>
                                    <button class="dw-btn dw-btn-sm" style="background: var(--surface-muted); color: #ff4d4f; border: 1px solid var(--border);" data-toggle="modal" data-target="#deleteCatModal<?= $c['id'] ?>"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modals for categories -->
    <?php if (!empty($categories)): ?>
        <?php foreach ($categories as $c): ?>
            <!-- Modal Edit Category -->
            <div class="modal fade" id="editCatModal<?= $c['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <form class="modal-content" action="<?= url('/manager/articles/cat/edit') ?>" method="post" style="background: var(--surface); color: var(--fg); border-radius: 12px; border: 1px solid var(--border);">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <div class="modal-header border-bottom-0">
                            <h5 class="modal-title font-weight-bold text-primary">Sửa chuyên mục</h5>
                            <button type="button" class="close" data-dismiss="modal" style="color:var(--fg);">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Tên chuyên mục</label>
                                <input type="text" class="form-control" name="name" value="<?= $c['name'] ?>" required style="background:var(--surface-muted); border-radius:8px;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Đường dẫn tĩnh (Slug)</label>
                                <input type="text" class="form-control" name="slug" value="<?= $c['slug'] ?>" required style="background:var(--surface-muted); border-radius:8px;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Mô tả (Content)</label>
                                <textarea class="form-control" name="content" rows="3" style="background:var(--surface-muted); border-radius:8px;"><?= $c['content'] ?></textarea>
                            </div>
                            <?php
                            $metaDataObj = [];
                            if (!empty($c['meta_data'])) {
                                $metaDataObj = json_decode($c['meta_data'], true) ?: [];
                            }
                            ?>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Từ khóa (Keyword)</label>
                                <input type="text" class="form-control" name="keyword" value="<?= $c['keyword'] ?>" style="background:var(--surface-muted); border-radius:8px;">
                            </div>
                            <hr style="border-color:var(--border);">
                            <h6 class="font-weight-bold text-primary mb-3"><i class="fa fa-search"></i> Tối ưu hoá SEO</h6>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Meta Title</label>
                                <input type="text" class="form-control" name="meta_title" value="<?= $metaDataObj['meta_title'] ?? '' ?>" placeholder="Để trống sẽ lấy Tên chuyên mục" style="background:var(--surface-muted); border-radius:8px;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Meta Description</label>
                                <textarea class="form-control" name="meta_desc" rows="2" placeholder="Mô tả ngắn cho công cụ tìm kiếm..." style="background:var(--surface-muted); border-radius:8px;"><?= $metaDataObj['meta_desc'] ?? '' ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Meta Keywords</label>
                                <input type="text" class="form-control" name="meta_keywords" value="<?= $metaDataObj['meta_keywords'] ?? '' ?>" placeholder="Từ khoá, phân cách bằng dấu phẩy" style="background:var(--surface-muted); border-radius:8px;">
                            </div>
                            <hr style="border-color:var(--border);">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold text-danger">Xác thực thao tác</label>
                                <p class="small text-muted mb-2">Nhập <code>articles</code> và mật khẩu Admin của bạn để lưu thay đổi.</p>
                                <input type="text" class="form-control mb-2" name="module_name" required autocomplete="off" placeholder="Tên phân hệ (articles)" style="background:var(--surface-muted); border-radius:8px;">
                                <input type="password" class="form-control" name="admin_pass" required placeholder="Mật khẩu Admin" style="background:var(--surface-muted); border-radius:8px;">
                            </div>
                        </div>
                        <div class="modal-footer border-top-0">
                            <button type="button" class="dw-btn dw-btn-ghost" data-dismiss="modal">Hủy</button>
                            <button type="submit" class="dw-btn dw-btn-primary"><i class="fa fa-save"></i> Lưu thay đổi</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Delete Category -->
            <div class="modal fade" id="deleteCatModal<?= $c['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <form class="modal-content" action="<?= url('/manager/articles/cat/delete') ?>" method="post" style="background: var(--surface); color: var(--fg); border-radius: 12px; border: 1px solid var(--border);">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <div class="modal-header" style="background-color: #ff4d4f; color: #fff; border-radius: 12px 12px 0 0;">
                            <h5 class="modal-title font-weight-bold">Xóa chuyên mục</h5>
                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <p class="mt-3">Bạn có chắc chắn muốn xóa chuyên mục <strong style="color:var(--heading);"><?= $c['name'] ?></strong>?</p>
                            <div class="alert" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107; border: 1px solid #ffc107;">
                                <i class="fa fa-warning"></i> CẢNH BÁO: Tất cả bài viết, bình luận, chương và tập tin thuộc chuyên mục này sẽ bị <b>XÓA VĨNH VIỄN</b>!
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Tên phân hệ (Nhập <code>articles</code>)</label>
                                <input type="text" class="form-control" name="module_name" required autocomplete="off" placeholder="articles" style="background:var(--surface-muted); border-radius:8px;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Mật khẩu Admin của bạn</label>
                                <input type="password" class="form-control" name="admin_pass" required style="background:var(--surface-muted); border-radius:8px;">
                            </div>
                        </div>
                        <div class="modal-footer border-top-0">
                            <button type="button" class="dw-btn dw-btn-ghost" data-dismiss="modal">Hủy</button>
                            <button type="submit" class="dw-btn" style="background-color: #ff4d4f; color: #fff;"><i class="fa fa-trash"></i> Xóa vĩnh viễn</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Modal Add Category -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" action="<?= url('/manager/articles/cat/add') ?>" method="post" style="background: var(--surface); color: var(--fg); border-radius: 12px; border: 1px solid var(--border);">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title font-weight-bold text-primary">Thêm chuyên mục mới</h5>
                    <button type="button" class="close" data-dismiss="modal" style="color:var(--fg);">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Tên chuyên mục</label>
                        <input type="text" class="form-control" name="name" required style="background:var(--surface-muted); border-radius:8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Đường dẫn tĩnh (Slug)</label>
                        <input type="text" class="form-control" name="slug" required style="background:var(--surface-muted); border-radius:8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Mô tả (Content)</label>
                        <textarea class="form-control" name="content" rows="3" style="background:var(--surface-muted); border-radius:8px;"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Từ khóa (Keyword)</label>
                        <input type="text" class="form-control" name="keyword" style="background:var(--surface-muted); border-radius:8px;">
                    </div>
                    <hr style="border-color:var(--border);">
                    <h6 class="font-weight-bold text-primary mb-3"><i class="fa fa-search"></i> Tối ưu hoá SEO</h6>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Meta Title</label>
                        <input type="text" class="form-control" name="meta_title" placeholder="Để trống sẽ lấy Tên chuyên mục" style="background:var(--surface-muted); border-radius:8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Meta Description</label>
                        <textarea class="form-control" name="meta_desc" rows="2" placeholder="Mô tả ngắn cho công cụ tìm kiếm..." style="background:var(--surface-muted); border-radius:8px;"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Meta Keywords</label>
                        <input type="text" class="form-control" name="meta_keywords" placeholder="Từ khoá, phân cách bằng dấu phẩy" style="background:var(--surface-muted); border-radius:8px;">
                    </div>
                    <hr style="border-color:var(--border);">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold text-danger">Xác thực thao tác</label>
                        <p class="small text-muted mb-2">Nhập <code>articles</code> và mật khẩu Admin của bạn để thêm.</p>
                        <input type="text" class="form-control mb-2" name="module_name" required autocomplete="off" placeholder="Tên phân hệ (articles)" style="background:var(--surface-muted); border-radius:8px;">
                        <input type="password" class="form-control" name="admin_pass" required placeholder="Mật khẩu Admin" style="background:var(--surface-muted); border-radius:8px;">
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="dw-btn dw-btn-ghost" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="dw-btn dw-btn-primary"><i class="fa fa-plus"></i> Thêm</button>
                </div>
            </form>
        </div>
    </div>

<?php elseif ($tab === 'posts'): // Posts Tab ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <form action="<?= url('/manager/articles') ?>" method="get" class="d-flex" style="gap: 10px;">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <input type="hidden" name="tab" value="posts">
                <input type="text" class="form-control" name="search" value="<?= _e($search) ?>" placeholder="Tìm ID hoặc Tên bài viết..." style="background:var(--surface-muted); border-radius:20px;">
                <button type="submit" class="dw-btn dw-btn-primary" style="border-radius:20px; white-space: nowrap;"><i class="fa fa-search"></i> Tìm</button>
            </form>
        </div>
    </div>

    <div class="dw-card">
        <div class="table-responsive">
            <table class="table mb-0" style="color: var(--fg);">
                <thead style="background: var(--surface-muted); color: var(--fg-muted);">
                    <tr>
                        <th width="50" class="border-top-0">ID</th>
                        <th class="border-top-0">Tiêu đề bài viết</th>
                        <th class="border-top-0">Chuyên mục</th>
                        <th class="border-top-0">Người đăng</th>
                        <th class="border-top-0 d-none d-md-table-cell">Ngày đăng</th>
                        <th class="border-top-0 d-none d-md-table-cell">Lượt xem</th>
                        <th width="100" class="text-right border-top-0">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted border-top-0">Không tìm thấy bài viết nào.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($posts as $p): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td class="align-middle border-top-0 text-muted"><?= $p['id'] ?></td>
                                <td class="align-middle border-top-0">
                                    <a href="<?= url('/articles/' . $p['id'] . '-' . $p['slug']) ?>.html" target="_blank" class="font-weight-bold" style="color:var(--heading); text-decoration: none;"><?= $p['title'] ?></a>
                                </td>
                                <td class="align-middle border-top-0">
                                    <span style="background: rgba(40, 167, 69, 0.1); color: #28a745; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;"><?= $p['category_name'] ?? 'Không xác định' ?></span>
                                </td>
                                <td class="align-middle border-top-0">
                                    <span style="background: var(--surface-muted); color: var(--fg); padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; border: 1px solid var(--border);"><?= $p['author'] ?></span>
                                </td>
                                <td class="align-middle border-top-0 text-muted d-none d-md-table-cell small"><?= date('d/m/Y H:i', $p['time']) ?></td>
                                <td class="align-middle border-top-0 text-muted d-none d-md-table-cell small"><?= number_format($p['view']) ?></td>
                                <td class="text-right align-middle border-top-0">
                                    <a href="<?= url('/articles/' . $p['id'] . '-' . $p['slug']) ?>.html" target="_blank" class="dw-btn dw-btn-sm" style="background: var(--surface-muted); color: #28a745; border: 1px solid var(--border);" title="Xem"><i class="fa fa-eye"></i></a>
                                    <button class="dw-btn dw-btn-sm" style="background: var(--surface-muted); color: #ff4d4f; border: 1px solid var(--border);" data-toggle="modal" data-target="#deletePostModal<?= $p['id'] ?>" title="Xóa"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modals for posts -->
    <?php if (!empty($posts)): ?>
        <?php foreach ($posts as $p): ?>
            <!-- Modal Delete Post -->
            <div class="modal fade" id="deletePostModal<?= $p['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <form class="modal-content" action="<?= url('/manager/articles/post/delete') ?>" method="post" style="background: var(--surface); color: var(--fg); border-radius: 12px; border: 1px solid var(--border);">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <div class="modal-header" style="background-color: #ff4d4f; color: #fff; border-radius: 12px 12px 0 0;">
                            <h5 class="modal-title font-weight-bold">Xóa bài viết</h5>
                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <p class="mt-3">Bạn có chắc chắn muốn xóa bài viết <strong style="color:var(--heading);"><?= $p['title'] ?></strong>?</p>
                            <div class="alert" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107; border: 1px solid #ffc107;">
                                <i class="fa fa-warning"></i> CẢNH BÁO: Xóa bài viết này cũng sẽ xóa tất cả bình luận, chương (chapter) và tệp đính kèm liên quan vĩnh viễn!
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Tên phân hệ (Nhập <code>articles</code>)</label>
                                <input type="text" class="form-control" name="module_name" required autocomplete="off" placeholder="articles" style="background:var(--surface-muted); border-radius:8px;">
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Mật khẩu Admin của bạn</label>
                                <input type="password" class="form-control" name="admin_pass" required style="background:var(--surface-muted); border-radius:8px;">
                            </div>
                        </div>
                        <div class="modal-footer border-top-0">
                            <button type="button" class="dw-btn dw-btn-ghost" data-dismiss="modal">Hủy</button>
                            <button type="submit" class="dw-btn" style="background-color: #ff4d4f; color: #fff;"><i class="fa fa-trash"></i> Xóa vĩnh viễn</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Phân trang (Pagination) -->
    <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?= url('/manager/articles?tab=posts&search=' . urlencode($search) . '&page=' . ($page - 1)) ?>" style="background: var(--surface); color: var(--fg); border-color: var(--border);">Trước</a></li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('/manager/articles?tab=posts&search=' . urlencode($search) . '&page=' . $i) ?>" style="<?= ($i == $page) ? 'background-color: var(--primary); border-color: var(--primary); color: #fff;' : 'background: var(--surface); color: var(--fg); border-color: var(--border);' ?>"><?= _e($i) ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item"><a class="page-link" href="<?= url('/manager/articles?tab=posts&search=' . urlencode($search) . '&page=' . ($page + 1)) ?>" style="background: var(--surface); color: var(--fg); border-color: var(--border);">Sau</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

<?php elseif ($tab === 'chapters'): ?>

    <div class="mb-3 d-flex justify-content-between align-items-center">
        <form action="" method="get" class="d-flex" style="width: 300px;">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <input type="hidden" name="tab" value="chapters">
            <input type="text" name="search" class="form-control" placeholder="Tìm ID hoặc tên chương..." value="<?= _e($search) ?>" style="border-top-right-radius: 0; border-bottom-right-radius: 0; border: 1px solid var(--border); background: var(--surface-muted); color: var(--fg);">
            <button class="btn btn-primary" type="submit" style="border-top-left-radius: 0; border-bottom-left-radius: 0;"><i class="fa fa-search"></i></button>
        </form>
    </div>

    <div class="dw-card">
        <div class="table-responsive">
            <table class="table mb-0" style="color: var(--fg);">
                <thead style="background: var(--surface-muted); color: var(--fg-muted);">
                    <tr>
                        <th width="50" class="border-top-0">ID</th>
                        <th class="border-top-0">Tên chương</th>
                        <th class="border-top-0">Bài viết (Chủ đề)</th>
                        <th class="border-top-0">Tác giả</th>
                        <th class="border-top-0 text-right">Ngày tạo</th>
                        <th width="120" class="text-right border-top-0">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($chapters)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Chưa có chương nào hoặc không tìm thấy.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($chapters as $c): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td class="align-middle border-top-0"><?= $c['id'] ?></td>
                                <td class="align-middle border-top-0">
                                    <div class="font-weight-bold" style="color:var(--heading); font-size:15px;"><a href="<?= url('/view-chap/' . $c['id'] . '-' . $c['slug'] . '.html') ?>" target="_blank" style="color:var(--heading);"><?= $c['title'] ?></a></div>
                                </td>
                                <td class="align-middle border-top-0"><span style="color:var(--primary); font-weight:500;"><a href="<?= url('/articles/post-' . $c['box'] . '.html') ?>" target="_blank"><?= $c['post_title'] ?? 'Bài viết không tồn tại' ?></a></span></td>
                                <td class="align-middle border-top-0 font-weight-bold" style="color:var(--accent);"><?= $c['author'] ?></td>
                                <td class="align-middle text-right border-top-0 text-muted" style="font-size:0.9rem;"><?= date('d/m/Y', $c['time']) ?></td>
                                <td class="text-right align-middle border-top-0">
                                    <a href="<?= url('/articles/chapter-' . $c['id'] . '/edit') ?>" target="_blank" class="dw-btn dw-btn-sm" style="background: var(--surface-muted); color: var(--primary); border: 1px solid var(--border);"><i class="fa fa-pencil"></i></a>
                                    <button class="dw-btn dw-btn-sm" style="background: var(--surface-muted); color: #ff4d4f; border: 1px solid var(--border);" data-toggle="modal" data-target="#deleteChapModal<?= $c['id'] ?>"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?= url('/manager/articles?tab=chapters&search=' . urlencode($search) . '&page=' . ($page - 1)) ?>" style="background: var(--surface); color: var(--fg); border-color: var(--border);">Trước</a></li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('/manager/articles?tab=chapters&search=' . urlencode($search) . '&page=' . $i) ?>" style="<?= ($i == $page) ? 'background-color: var(--primary); border-color: var(--primary); color: #fff;' : 'background: var(--surface); color: var(--fg); border-color: var(--border);' ?>"><?= _e($i) ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item"><a class="page-link" href="<?= url('/manager/articles?tab=chapters&search=' . urlencode($search) . '&page=' . ($page + 1)) ?>" style="background: var(--surface); color: var(--fg); border-color: var(--border);">Sau</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <!-- Modals Delete Chapter -->
    <?php if (!empty($chapters)): ?>
        <?php foreach ($chapters as $c): ?>
            <div class="modal fade" id="deleteChapModal<?= $c['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <form class="modal-content" action="<?= url('/manager/articles/chapter/delete') ?>" method="post" style="background: var(--surface); color: var(--fg); border-radius: 12px; border: 1px solid var(--border);">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <div class="modal-header border-bottom-0">
                            <h5 class="modal-title font-weight-bold text-danger"><i class="fa fa-warning"></i> Xóa chương</h5>
                            <button type="button" class="close" data-dismiss="modal" style="color:var(--fg);">&times;</button>
                        </div>
                        <div class="modal-body text-center">
                            <p>Bạn có chắc chắn muốn xóa vĩnh viễn chương <strong><?= $c['title'] ?></strong>?</p>
                            <p class="text-muted small">Mọi bình luận trong chương này sẽ bị xóa theo.</p>
                            <div class="mt-3 text-left">
                                <label class="form-label font-weight-bold text-danger">Tên phân hệ xác nhận</label>
                                <input type="text" name="module_name" class="form-control text-center" placeholder="articles" required style="background:var(--surface-muted); border-radius:8px;">
                            </div>
                            <div class="mt-3 text-left">
                                <label class="form-label font-weight-bold text-danger">Mật khẩu Admin</label>
                                <input type="password" name="admin_pass" class="form-control text-center" required style="background:var(--surface-muted); border-radius:8px;">
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0 justify-content-center">
                            <button type="button" class="dw-btn dw-btn-outline" data-dismiss="modal">Hủy</button>
                            <button type="submit" class="dw-btn" style="background:#ff4d4f; color:#fff;">Xóa ngay</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php endif; ?>