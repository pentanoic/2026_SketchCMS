<?php
/**
 * Software: SketchCMS
 * Author: valedrat
 */
$this->layout('manager/layout', ['page_title' => $page_title ?? 'Cấu hình SMTP', 'panelActive' => 'smtp']);
?>
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <h5 class="card-header">Quản lý Cấu hình SMTP</h5>
            <div class="card-body">
                <?php if (isset($_COOKIE['manager_smtp_error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i> <?= $_COOKIE['manager_smtp_error'] ?>
                    </div>
                    <?php setcookie('manager_smtp_error', '', time() - 3600, '/'); ?>
                <?php endif; ?>
                <form action="<?= url('/manager/smtp') ?>" method="POST">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?= _e($success) ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= _e($error) ?></div>
                    <?php endif; ?>
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <div class="mb-3">
                        <label for="smtp_host" class="form-label">SMTP Host</label>
                        <input type="text" class="form-control" id="smtp_host" name="host" value="<?= $smtp['host'] ?? '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_port" class="form-label">SMTP Port</label>
                        <input type="number" class="form-control" id="smtp_port" name="port" value="<?= $smtp['port'] ?? '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_user" class="form-label">SMTP Username (Email)</label>
                        <input type="text" class="form-control" id="smtp_user" name="user" value="<?= $smtp['user'] ?? '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_pass" class="form-label">SMTP Password</label>
                        <input type="password" class="form-control" id="smtp_pass" name="pass" value="<?= $smtp['pass'] ?? '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_encrypt" class="form-label">Encryption (tls/ssl/none)</label>
                        <select class="form-control" id="smtp_encrypt" name="encrypt">
                            <option value="tls" <?= ($smtp['encrypt'] ?? '') == 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= ($smtp['encrypt'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                            <option value="none" <?= ($smtp['encrypt'] ?? '') == 'none' ? 'selected' : '' ?>>None</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_from_email" class="form-label">From Email</label>
                        <input type="email" class="form-control" id="smtp_from_email" name="from_email" value="<?= $smtp['from_email'] ?? '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="smtp_from_name" class="form-label">From Name</label>
                        <input type="text" class="form-control" id="smtp_from_name" name="from_name" value="<?= $smtp['from_name'] ?? '' ?>" required>
                    </div>

                    <hr style="border-color:var(--border); margin:20px 0;">
                    <div class="mb-4">
                        <label class="form-label font-weight-bold text-danger">Xác thực thao tác</label>
                        <p class="small text-muted mb-2">Nhập <code>smtp</code> và mật khẩu Admin của bạn để lưu cài đặt.</p>
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <input type="text" class="form-control" name="module_name" required autocomplete="off" placeholder="Tên phân hệ (smtp)">
                            </div>
                            <div class="col-md-6">
                                <input type="password" class="form-control" name="admin_pass" required placeholder="Mật khẩu Admin">
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="dw-btn dw-btn-primary px-4 font-weight-bold">
                            <i class="fa fa-save mr-2"></i>Lưu cấu hình
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
