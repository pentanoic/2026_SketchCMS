<?php
/**
 * Software: SketchCMS
 * Author: valedrat
 */
$this->layout('layout', ['page_title' => $page_title ?? 'Đặt lại mật khẩu']);
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-12">
        <div class="card mb-4">
            <h5 class="card-header">Đặt lại mật khẩu mới</h5>
            <div class="card-body">
                <form action="<?= url('/login/password/reset?token=' . urlencode($_GET['token'] ?? '')) ?>" method="POST">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?= _e($success) ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= _e($error) ?></div>
                    <?php endif; ?>
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu mới</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">Xác nhận mật khẩu</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Đổi mật khẩu</button>
                    <div class="mt-3 text-center">
                        <a href="<?= url('/login') ?>">Quay lại đăng nhập</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
