<?php
$this->layout('manager/layout', ['page_title' => $page_title ?? 'Quản lý URL Rules', 'panelActive' => 'url_rules']);
?>
<div class="row">
    <div class="col-md-12">
        <?php if (isset($_COOKIE['manager_url_error'])): ?>
            <div class="alert alert-danger"><?= $_COOKIE['manager_url_error'] ?></div>
            <?php setcookie('manager_url_error', '', time() - 3600, '/'); ?>
        <?php endif; ?>
        <?php if (isset($_COOKIE['manager_url_success'])): ?>
            <div class="alert alert-success"><?= $_COOKIE['manager_url_success'] ?></div>
            <?php setcookie('manager_url_success', '', time() - 3600, '/'); ?>
        <?php endif; ?>
    </div>

    <!-- REWRITE RULES -->
    <div class="col-md-12">
        <div class="dw-card mb-4">
            <div class="dw-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Rewrite URL</h5>
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addRewriteModal">
                    <i class="fa fa-plus"></i> Thêm Rewrite
                </button>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Đường dẫn (Path)</th>
                            <th>Đích (Target)</th>
                            <th>Phương thức</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php if (empty($url_rules['rewrite'])): ?>
                            <tr><td colspan="3" class="text-center">Chưa có URL Rewrite nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($url_rules['rewrite'] as $path => $target): ?>
                                <tr>
                                    <td><strong><?= _e($path) ?></strong></td>
                                    <td><?= ($target['controller'] ?? '') . '@' . ($target['action'] ?? '') ?></td>
                                    <td><span class="badge bg-primary text-white"><?= $target['method'] ?? 'GET|POST' ?></span></td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm" onclick="editRewriteRule('<?= _e($path) ?>', '<?= ($target['controller'] ?? '') . '@' . ($target['action'] ?? '') ?>', '<?= $target['method'] ?? 'GET|POST' ?>')">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteRule('rewrite', '<?= _e($path) ?>')">
                                            <i class="fa fa-trash"></i>
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

    <!-- DISABLE RULES -->
    <div class="col-md-12">
        <div class="dw-card mb-4">
            <div class="dw-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Disable URL (404)</h5>
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addDisableModal">
                    <i class="fa fa-plus"></i> Thêm Disable
                </button>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Đường dẫn bị khóa (Path)</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php if (empty($url_rules['disable'])): ?>
                            <tr><td colspan="2" class="text-center">Chưa có URL Disable nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($url_rules['disable'] as $path): ?>
                                <tr>
                                    <td><strong><?= _e($path) ?></strong></td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteRule('disable', '<?= _e($path) ?>')">
                                            <i class="fa fa-trash"></i>
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

<!-- Add Rewrite Modal -->
<div class="modal fade" id="addRewriteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="<?= url('/manager/url_rules/add') ?>" method="POST" class="modal-content">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <div class="modal-header">
                <h5 class="modal-title">Thêm Rewrite URL</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="rule_type" value="rewrite">
                <div class="mb-3">
                    <label class="form-label">Đường dẫn (VD: /tin-tuc)</label>
                    <input type="text" name="rule_path" class="form-control" placeholder="/" required />
                    <small class="text-muted">Bắt buộc bắt đầu bằng /</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Đích đến (Controller@method)</label>
                    <input type="text" name="rule_target" class="form-control" placeholder="homeController@index" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Phương thức (Method)</label>
                    <select name="rule_method" class="form-control">
                        <option value="GET|POST">GET, POST</option>
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                    </select>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="form-label text-danger">Tên phân hệ xác nhận</label>
                    <input type="text" name="module_name" class="form-control" placeholder="url_rules" required />
                </div>
                <div class="mb-3">
                    <label class="form-label text-danger">Mật khẩu Admin</label>
                    <input type="password" name="admin_pass" class="form-control" required />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">Thêm</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Disable Modal -->
<div class="modal fade" id="addDisableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="<?= url('/manager/url_rules/add') ?>" method="POST" class="modal-content">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <div class="modal-header">
                <h5 class="modal-title">Thêm Disable URL</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="rule_type" value="disable">
                <div class="mb-3">
                    <label class="form-label">Đường dẫn cần khóa (VD: /register)</label>
                    <input type="text" name="rule_path" class="form-control" placeholder="/" required />
                    <small class="text-muted">Bắt buộc bắt đầu bằng /</small>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="form-label text-danger">Tên phân hệ xác nhận</label>
                    <input type="text" name="module_name" class="form-control" placeholder="url_rules" required />
                </div>
                <div class="mb-3">
                    <label class="form-label text-danger">Mật khẩu Admin</label>
                    <input type="password" name="admin_pass" class="form-control" required />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">Thêm</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Rule Modal -->
<div class="modal fade" id="deleteRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <form action="<?= url('/manager/url_rules/delete') ?>" method="POST" class="modal-content">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận Xóa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa quy tắc này?</p>
                <input type="hidden" name="rule_type" id="del_rule_type" value="">
                <input type="hidden" name="rule_path" id="del_rule_path" value="">
                <div class="mb-3">
                    <label class="form-label text-danger">Tên phân hệ xác nhận</label>
                    <input type="text" name="module_name" class="form-control" placeholder="url_rules" required />
                </div>
                <div class="mb-3">
                    <label class="form-label text-danger">Mật khẩu Admin</label>
                    <input type="password" name="admin_pass" class="form-control" required />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-danger">Xóa</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Rewrite Modal -->
<div class="modal fade" id="editRewriteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="<?= url('/manager/url_rules/edit') ?>" method="POST" class="modal-content">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <div class="modal-header">
                <h5 class="modal-title">Chỉnh sửa Rewrite URL</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="old_path" id="edit_old_path" value="">
                <div class="mb-3">
                    <label class="form-label">Đường dẫn cũ (Path)</label>
                    <input type="text" id="edit_path_display" class="form-control" disabled />
                </div>
                <div class="mb-3">
                    <label class="form-label">Đường dẫn mới (New Path)</label>
                    <input type="text" name="rule_path" id="edit_rule_path" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Đích đến mới (Controller@method)</label>
                    <input type="text" name="rule_target" id="edit_rule_target" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Phương thức (Method)</label>
                    <select name="rule_method" id="edit_rule_method" class="form-control">
                        <option value="GET|POST">GET, POST</option>
                        <option value="GET">GET</option>
                        <option value="POST">POST</option>
                    </select>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="form-label text-danger">Tên phân hệ xác nhận</label>
                    <input type="text" name="module_name" class="form-control" placeholder="url_rules" required />
                </div>
                <div class="mb-3">
                    <label class="form-label text-danger">Mật khẩu Admin</label>
                    <input type="password" name="admin_pass" class="form-control" required />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
function editRewriteRule(path, target, method) {
    document.getElementById('edit_old_path').value = path;
    document.getElementById('edit_path_display').value = path;
    document.getElementById('edit_rule_path').value = path;
    document.getElementById('edit_rule_target').value = target;
    document.getElementById('edit_rule_method').value = method || 'GET|POST';
    $('#editRewriteModal').modal('show');
}
function confirmDeleteRule(type, path) {
    document.getElementById('del_rule_type').value = type;
    document.getElementById('del_rule_path').value = path;
    $('#deleteRuleModal').modal('show');
}
</script>


