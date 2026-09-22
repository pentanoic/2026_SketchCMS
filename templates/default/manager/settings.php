<?php
$this->layout('manager/layout', ['page_title' => $page_title ?? 'Cài đặt Website', 'panelActive' => 'settings']);
?>

<div class="mb-4">
    <h5 class="font-weight-bold text-uppercase mb-0" style="color:var(--heading); font-size:16px;">
        <i class="fa fa-cog" style="color:var(--accent)"></i> Cài đặt SEO
    </h5>
</div>

<?php if (isset($_COOKIE['manager_settings_success'])): ?>
    <div class="alert alert-success">
        <i class="fa fa-check-circle"></i> <?= $_COOKIE['manager_settings_success'] ?>
    </div>
    <?php setcookie('manager_settings_success', '', time() - 3600, '/'); ?>
<?php endif; ?>

<?php if (isset($_COOKIE['manager_settings_error'])): ?>
    <div class="alert alert-danger">
        <i class="fa fa-exclamation-triangle"></i> <?= $_COOKIE['manager_settings_error'] ?>
    </div>
    <?php setcookie('manager_settings_error', '', time() - 3600, '/'); ?>
<?php endif; ?>

<div class="dw-card">
    <div class="dw-card-header d-flex justify-content-between align-items-center">
        <h5 class="m-0 font-weight-bold"><i class="fa fa-globe mr-2" style="color:var(--primary)"></i>Cấu hình Website</h5>
    </div>
    <div class="dw-card-body">
        <form action="<?= url('/manager/settings') ?>" method="post">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            
            <h6 class="border-bottom pb-2 mb-3 text-primary font-weight-bold">Thông tin Website</h6>
            
            <div class="mb-3">
                <label for="app_name" class="form-label font-weight-bold">Tên Website (App Name)</label>
                <input type="text" class="form-control" id="app_name" name="app_name" value="<?= $systemConfig['app']['name'] ?? '' ?>" required>
                <small class="text-muted d-block mt-1">Tên chính của website, hiển thị trên tiêu đề và thanh điều hướng.</small>
            </div>
            
            <div class="mb-3">
                <label for="app_description" class="form-label font-weight-bold">Mô tả (Description)</label>
                <textarea class="form-control" id="app_description" name="app_description" rows="3"><?= $systemConfig['app']['description'] ?? '' ?></textarea>
                <small class="text-muted d-block mt-1">Mô tả ngắn gọn về website, giúp cải thiện SEO (Meta Description).</small>
            </div>
            
            <div class="mb-4">
                <label for="app_keyword" class="form-label font-weight-bold">Từ khóa (Keywords)</label>
                <input type="text" class="form-control" id="app_keyword" name="app_keyword" value="<?= $systemConfig['app']['keyword'] ?? '' ?>">
                <small class="text-muted d-block mt-1">Các từ khóa tìm kiếm cách nhau bằng dấu phẩy (Meta Keywords).</small>
            </div>
            
            <h6 class="border-bottom pb-2 mb-3 mt-4 text-primary font-weight-bold">Cấu hình Nâng cao</h6>
            
            <div class="mb-3">
                <label class="form-label font-weight-bold">Giao thức (Scheme)</label>
                <select class="form-control" name="app_scheme">
                    <option value="http://" <?= ($systemConfig['app']['site']['scheme'] ?? '') === 'http://' ? 'selected' : '' ?>>http://</option>
                    <option value="https://" <?= ($systemConfig['app']['site']['scheme'] ?? '') === 'https://' ? 'selected' : '' ?>>https://</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="form-label font-weight-bold">Tên miền (Host)</label>
                <input type="text" class="form-control" name="app_host" value="<?= $systemConfig['app']['site']['host'] ?? '' ?>" required>
            </div>
            
            <hr style="border-color:var(--border); margin:20px 0;">
            <div class="mb-4">
                <label class="form-label font-weight-bold text-danger">Xác thực thao tác</label>
                <p class="small text-muted mb-2">Nhập <code>settings</code> và mật khẩu Admin của bạn để lưu cài đặt.</p>
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <input type="text" class="form-control" name="module_name" required autocomplete="off" placeholder="Tên phân hệ (settings)">
                    </div>
                    <div class="col-md-6">
                        <input type="password" class="form-control" name="admin_pass" required placeholder="Mật khẩu Admin">
                    </div>
                </div>
            </div>
            
            <div class="text-right">
                <button type="submit" class="dw-btn dw-btn-primary px-4 font-weight-bold">
                    <i class="fa fa-save mr-2"></i>Lưu cài đặt
                </button>
            </div>
        </form>
    </div>
</div>
