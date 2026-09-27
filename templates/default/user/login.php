<?php $this->layout('layout'); ?>

<div class="dw-card mb-4">
    <div class="dw-card-header">
        <h3 class="dw-card-title"><i class="fa fa-sign-in" style="color:var(--accent)"></i> Đăng nhập</h3>
    </div>
    <div class="dw-card-body">
        <?php if ($error): ?>
        <div class="dw-alert dw-alert-danger mb-4">
            <i class="fa fa-exclamation-triangle mr-1"></i> <?= _e($error) ?>
        </div>
        <?php endif ?>
        
        <form action="<?= url('/login') ?>" method="post" autocomplete="off">
            <div class="form-group mb-4">
                <label class="font-weight-bold text-muted small text-uppercase">Tên tài khoản</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-light border-right-0"><i class="fa fa-user-circle-o text-muted"></i></span>
                    </div>
                    <input class="form-control border-left-0 pl-2" type="text" name="account" pattern="^[a-zA-Z0-9]{3,15}$" id="inputAccount" value="<?= _e($inputAccount) ?>" placeholder="Nhập tên đăng nhập" required autocomplete="username" />
                </div>
            </div>
            
            <div class="form-group mb-4">
                <label class="font-weight-bold text-muted small text-uppercase">Mật khẩu</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-light border-right-0"><i class="fa fa-key text-muted"></i></span>
                    </div>
                    <input class="form-control border-left-0 pl-2" type="password" name="password" pattern="^.{3,32}$" id="inputPassword" placeholder="••••••••" required autocomplete="current-password" />
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="rememberMe" name="remember" value="1" <?= ($inputRemember ? 'checked' : '') ?>>
                    <label class="custom-control-label font-weight-bold text-muted" for="rememberMe" style="cursor: pointer;">Ghi nhớ đăng nhập</label>
                </div>
                
                <div class="text-right">
                    <a href="<?= url('/register') ?>" class="small font-weight-bold text-primary d-block mb-1">Tạo tài khoản mới</a>
                    <a href="<?= url('/login/password/forgot') ?>" class="small font-weight-bold text-muted d-block">Quên mật khẩu?</a>
                </div>
            </div>
            
            <input type="hidden" name="csrf_token" value="<?=getCSRFToken()?>">
            
            <div class="mb-4">
                <script src="<?= $this->asset('/templates' . get_template() . '/public/js/doomcaptcha.js') ?>" countdown="on" label="Captcha" enemies="3" type="text/javascript"></script>
                <input id="dai-check" style="display:none" type="checkbox" name="doomcaptcha" value="<?= substr(sha1(getCSRFToken()),5,6) ?>" <?= ($inputCaptcha ? 'checked' : '') ?> />
            </div>
            
            <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold text-uppercase" id="dai" style="background:var(--accent-gradient); border:none; border-radius: var(--radius-md); letter-spacing: 1px;">
                <i class="fa fa-sign-in mr-2"></i>Đăng nhập
            </button>
        </form>
    </div>
</div>
