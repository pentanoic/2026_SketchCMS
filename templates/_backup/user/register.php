<?php $this->layout('layout'); ?>

<div class="dw-card mb-4">
    <div class="dw-card-header">
        <h3 class="dw-card-title"><i class="fa fa-user-plus" style="color:var(--accent)"></i> Đăng ký tài khoản</h3>
    </div>
    <div class="dw-card-body">
        <?php if ($error): ?>
        <div class="dw-alert dw-alert-danger mb-4">
            <i class="fa fa-exclamation-triangle mr-1"></i> <?= _e($error) ?>
        </div>
        <?php endif ?>
        
        <form action="<?= url('/register') ?>" method="post" autocomplete="off">
            <div class="form-group mb-4">
                <label class="font-weight-bold text-muted small text-uppercase">Tên tài khoản</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-light border-right-0"><i class="fa fa-user-circle-o text-muted"></i></span>
                    </div>
                    <input type="text" name="account" class="form-control border-left-0 pl-2" id="inputAccount" placeholder="Nhập tên đăng nhập" value="<?= _e($inputAccount) ?>" required />
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-muted small text-uppercase">Mật khẩu</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0"><i class="fa fa-key text-muted"></i></span>
                            </div>
                            <input type="password" name="password" class="form-control border-left-0 pl-2" id="inputPassword" placeholder="••••••••" required />
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-muted small text-uppercase">Nhập lại mật khẩu</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light border-right-0"><i class="fa fa-check-circle-o text-muted"></i></span>
                            </div>
                            <input type="password" name="re_password" class="form-control border-left-0 pl-2" placeholder="••••••••" required />
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group mb-4">
                <label class="font-weight-bold text-muted small text-uppercase">Giới tính</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-light border-right-0"><i class="fa fa-venus-mars text-muted"></i></span>
                    </div>
                    <select name="sex" class="form-control border-left-0 pl-2" required>
                        <option value="" disabled <?= (!$inputSex ? 'selected' : '') ?>>Chọn giới tính</option>
                        <option value="boy" <?= ($inputSex == 'boy' ? 'selected' : '') ?>>Nam</option>
                        <option value="girl" <?= ($inputSex == 'girl' ? 'selected' : '') ?>>Nữ</option>
                    </select>
                </div>
            </div>
            
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            
            <div class="form-group mb-4">
                <label class="font-weight-bold text-muted small text-uppercase">Mã bảo vệ</label>
                <div class="d-flex align-items-center bg-light p-2 rounded" style="border: 1px solid var(--border);">
                    <img src="<?= captchaSrc() ?>" alt="Captcha" class="mr-3 rounded" style="background: #fff; border: 1px solid #ccc; height: 38px;" />
                    <input type="text" name="captcha" class="form-control border-0 bg-transparent shadow-none px-0" id="inputcaptcha" placeholder="Nhập mã bên cạnh..." required />
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold text-uppercase mt-2" style="background:var(--accent-gradient); border:none; border-radius: var(--radius-md); letter-spacing: 1px;">
                <i class="fa fa-user-plus mr-2"></i>Tạo tài khoản
            </button>
            
            <div class="text-center mt-4">
                <span class="text-muted small">Đã có tài khoản?</span>
                <a href="<?= url('/login') ?>" class="small font-weight-bold text-primary ml-1">Đăng nhập ngay</a>
            </div>
        </form>
    </div>
</div>
