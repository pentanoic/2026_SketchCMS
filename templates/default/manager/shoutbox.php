<?php
$this->layout('manager/layout', ['page_title' => $page_title ?? 'Làm sạch Chatbox', 'panelActive' => 'shoutbox']);
?>

<div class="mb-4">
    <h5 class="font-weight-bold text-uppercase mb-0" style="color:var(--heading); font-size:16px;">
        <i class="fa fa-commenting" style="color:var(--accent)"></i> Quản lý Phòng chat
    </h5>
</div>

<?php if (isset($_COOKIE['manager_shoutbox_success'])): ?>
    <div class="alert alert-success">
        <i class="fa fa-check-circle"></i> <?= $_COOKIE['manager_shoutbox_success'] ?>
    </div>
    <?php setcookie('manager_shoutbox_success', '', time() - 3600, '/'); ?>
<?php endif; ?>

<?php if (isset($_COOKIE['manager_shoutbox_error'])): ?>
    <div class="alert alert-danger">
        <i class="fa fa-times-circle"></i> <?= $_COOKIE['manager_shoutbox_error'] ?>
    </div>
    <?php setcookie('manager_shoutbox_error', '', time() - 3600, '/'); ?>
<?php endif; ?>

<div class="dw-card">
    <div class="dw-card-body">
        <div class="alert" style="background: rgba(255, 77, 79, 0.1); color: #ff4d4f; border: 1px solid rgba(255, 77, 79, 0.2);">
            <h5 class="font-weight-bold"><i class="fa fa-exclamation-triangle"></i> Khu vực nguy hiểm</h5>
            <p class="mb-0">
                Hiện tại đang có <strong><?= number_format($totalShouts) ?></strong> tin nhắn trong Shoutbox.<br>
                Chức năng này sẽ làm sạch Phòng chat, nhưng giữ lại 50 tin nhắn cuối cùng và cấp phát lại ID của chúng từ 1 đến 50. ID tin nhắn mới sẽ tiếp tục đếm từ 51. Hành động này không thể hoàn tác.
            </p>
        </div>

        <?php if ($totalShouts > 50): ?>
            <button class="dw-btn" style="background: #ff4d4f; color: #fff; border: 1px solid #ff4d4f;" data-toggle="modal" data-target="#cleanShoutboxModal">
                <i class="fa fa-trash mr-2"></i> Làm sạch Chatbox ngay
            </button>
        <?php else: ?>
            <button class="dw-btn dw-btn-ghost" disabled style="opacity:0.6; cursor:not-allowed;">
                <i class="fa fa-trash mr-2"></i> Chatbox đang sạch sẽ (<= 50 tin nhắn)
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Clean Shoutbox -->
<div class="modal fade" id="cleanShoutboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" action="<?= url('/manager/shoutbox/clean') ?>" method="post" style="background: var(--surface); color: var(--fg); border-radius: 12px; border: 1px solid var(--border);">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <div class="modal-header" style="background-color: #ff4d4f; color: #fff; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title font-weight-bold">Xác nhận làm sạch Chatbox</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Bạn đang chuẩn bị thực hiện hành động nguy hiểm: <strong>Làm sạch hệ thống Chatbox</strong>.</p>
                <p>Để xác nhận, vui lòng nhập chính xác từ khóa <code>shoutbox</code> vào ô bên dưới, cùng với mật khẩu Admin của bạn.</p>
                
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Tên phân hệ (Nhập <code>shoutbox</code>)</label>
                    <input type="text" class="form-control" name="module_name" required autocomplete="off" placeholder="shoutbox" style="background:var(--surface-muted); border-radius:8px;">
                </div>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Mật khẩu Admin của bạn</label>
                    <input type="password" class="form-control" name="admin_pass" required style="background:var(--surface-muted); border-radius:8px;">
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="dw-btn dw-btn-ghost" data-dismiss="modal">Hủy bỏ</button>
                <button type="submit" class="dw-btn" style="background-color: #ff4d4f; color: #fff;">Xác nhận Làm sạch</button>
            </div>
        </form>
    </div>
</div>
