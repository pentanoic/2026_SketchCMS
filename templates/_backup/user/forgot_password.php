<?php
/**
 * Software: SketchCMS
 * Author: valedrat
 */
$this->layout('layout', ['page_title' => $page_title ?? 'Quên mật khẩu']);
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-12">
        <div class="card mb-4">
            <h5 class="card-header">Quên mật khẩu</h5>
            <div class="card-body">
                <form action="<?= url('/login/password/forgot') ?>" method="POST">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?= _e($success) ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= _e($error) ?></div>
                    <?php endif; ?>
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email đã đăng ký</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Gửi link khôi phục</button>
                    <div class="mt-3 text-center">
                        <a href="<?= url('/login') ?>">Quay lại đăng nhập</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
