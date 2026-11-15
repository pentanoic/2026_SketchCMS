<?php
$this->layout('manager/layout', ['page_title' => 'Quản lý Trang Tĩnh', 'panelActive' => 'static_pages']);
?>
<div class="row">
    <div class="col-md-12">
        <?php if (isset($_COOKIE['manager_sp_error'])): ?>
            <div class="alert alert-danger"><?= $_COOKIE['manager_sp_error'] ?></div>
            <?php setcookie('manager_sp_error', '', time() - 3600, '/'); ?>
        <?php endif; ?>
        <?php if (isset($_COOKIE['manager_sp_success'])): ?>
            <div class="alert alert-success"><?= $_COOKIE['manager_sp_success'] ?></div>
            <?php setcookie('manager_sp_success', '', time() - 3600, '/'); ?>
        <?php endif; ?>

        <div class="dw-card mb-4">
            <div class="dw-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Danh sách Trang tĩnh</h5>
                <div>
                    <a href="<?= url('/manager/static_pages/add') ?>" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus"></i> Thêm trang mới
                    </a>
                </div>
            </div>
            
            <div class="table-responsive text-nowrap">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tiêu đề / Tên File</th>
                            <th>Slug</th>
                            <th>Kích thước</th>
                            <th>Cập nhật lần cuối</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Chưa có trang tĩnh nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($p['title']) ?></strong><br>
                                        <small class="text-muted"><a href="<?= url('/' . $p['slug'] . '.html') ?>" target="_blank">/<?= $p['slug'] ?>.html</a></small>
                                    </td>
                                    <td><?= $p['slug'] ?></td>
                                    <td><?= round($p['size'] / 1024, 2) ?> KB</td>
                                    <td><?= date('d/m/Y H:i', $p['mtime']) ?></td>
                                    <td>
                                        <a href="<?= url('/manager/static_pages/edit?slug=' . urlencode($p['slug'])) ?>" class="btn btn-warning btn-sm">
                                            <i class="fa fa-pencil"></i> Sửa
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('<?= $p['slug'] ?>')">
                                            <i class="fa fa-trash"></i> Xóa
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <form action="<?= url('/manager/static_pages/delete') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận Xóa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa trang tĩnh <strong id="deleteSlugDisplay"></strong>?</p>
                <div class="alert alert-warning" style="background-color: rgba(255, 193, 7, 0.1); color: #ffc107; border: 1px solid #ffc107;">
                    <i class="fa fa-warning"></i> CẢNH BÁO: Hành động này sẽ xóa trang tĩnh vĩnh viễn và không thể khôi phục!
                </div>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Tên phân hệ (Nhập <code>static_pages</code>)</label>
                    <input type="text" class="form-control" name="module_name" required autocomplete="off" placeholder="static_pages" style="background:var(--surface-muted); border-radius:8px;">
                </div>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Mật khẩu Admin của bạn</label>
                    <input type="password" class="form-control" name="admin_pass" required style="background:var(--surface-muted); border-radius:8px;">
                </div>
                <input type="hidden" name="slug" id="deleteSlugInput" value="">
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-danger">Xóa</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete(slug) {
    document.getElementById('deleteSlugInput').value = slug;
    document.getElementById('deleteSlugDisplay').innerText = slug;
    $('#deleteModal').modal('show');
}
</script>
