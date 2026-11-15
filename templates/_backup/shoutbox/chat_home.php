<?php $this->layout('layout'); ?>
<link href="<?= $this->asset('/templates' . get_template() . '/public/css/in-shoutbox.css') ?>" rel="stylesheet" />
<style>
    .redactor_box{display:inline-block}
    /* Style giả lập Bootstrap 4 .page-link cho phân trang JS */
    #phan-trang { display: flex; padding-left: 0; list-style: none; border-radius: 0.25rem; }
    #phan-trang a, #phan-trang span {
        position: relative;
        display: block;
        padding: 0.5rem 0.75rem;
        margin-left: -1px;
        line-height: 1.25;
        color: #007bff;
        background-color: #fff;
        border: 1px solid #dee2e6;
        text-decoration: none;
    }
    #phan-trang a:hover {
        z-index: 2;
        color: #0056b3;
        text-decoration: none;
        background-color: #e9ecef;
        border-color: #dee2e6;
    }
    #phan-trang span.current, #phan-trang span[style*="font-weight:bold"] {
        z-index: 1;
        color: #fff !important;
        background-color: #007bff;
        border-color: #007bff;
        font-weight: normal !important;
    }
    #phan-trang a:first-child, #phan-trang span:first-child { border-top-left-radius: 0.25rem; border-bottom-left-radius: 0.25rem; }
    #phan-trang a:last-child, #phan-trang span:last-child { border-top-right-radius: 0.25rem; border-bottom-right-radius: 0.25rem; }
</style>

<div class="dw-card mb-4">
    <div class="dw-card-header d-flex align-items-center justify-content-between p-3 border-bottom">
        <h5 class="m-0 font-weight-bold text-uppercase" style="font-size: 15px; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            <i class="fa fa-comments mr-2"></i> Trò chuyện
        </h5>
        <div class="d-flex align-items-center">
            <div class="d-inline-block" style="transform: scale(0.8); transform-origin: right center;">
                <div class="pagination m-0" id="phan-trang"></div>
            </div>
            <div class="d-none d-md-flex align-items-center ml-2">
                <input class="form-control form-control-sm text-center mr-1" style="width: 50px; font-weight: bold; padding: 2px;" type="number" id="ano" name="page" min="1" max="10" placeholder="Trang">
                <button type="button" class="btn btn-sm btn-primary rounded-pill font-weight-bold shadow-sm" style="background: var(--accent-gradient); border: none; padding: 2px 10px;" onclick="getPage(totalChat)">
                    Go
                </button>
            </div>
        </div>
    </div>

    <!-- Form gửi -->
    <div class="bg-white p-3 border-bottom">
        <form id="form" action method="POST" name="form">
    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <div class="form-group mb-2">
                <textarea id="postText" name="content" rows="2" class="form-control border-0 bg-light" placeholder="Nhập tin nhắn..." style="resize: none; border-radius: 15px; padding: 15px;"></textarea>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-none d-md-block">
                    <?php include (dirname(dirname(__FILE__)) . '/toolbar.php') ?>
                </div>
                <div class="d-md-none">
                    <button type="button" class="btn btn-light rounded-circle" onclick="show_hide('shoutboxToolbar')"><i class="fa fa-plus text-muted"></i></button>
                </div>
                <button id="submit" type="submit" class="btn btn-primary rounded-pill px-4 font-weight-bold shadow-sm" style="background: var(--accent-gradient); border: none;">
                    <i class="fa fa-paper-plane mr-1"></i> Gửi
                </button>
            </div>
            <div id="shoutboxToolbar" style="display:none;" class="mt-2">
                <?php include (dirname(dirname(__FILE__)) . '/toolbar.php') ?>
            </div>
        </form>
    </div>

    <!-- Khu vực chat (Nơi JS sẽ tải nội dung vào div#idChat) -->
    <div class="dw-card-body p-0" style="background: var(--surface-muted); max-height: 500px; overflow-y: auto;">
        <div id="postText" style="display:none;"></div> <!-- Giữ lại div này phòng khi JS của user cần, ẩn đi tránh lỗi ID trùng -->
        <div id="idChat" class="p-3"></div>
    </div>
</div>

<script type="text/javascript">
    function show_hide(id) {
        var el = document.getElementById(id);
        if (el) el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
    }
    window.tag = function(prefix, suffix) {
        var txt = document.getElementById('postText');
        if (txt) {
            txt.value = txt.value + prefix + suffix;
            txt.focus();
        }
    };
    var totalChat = "<?= _e($chat_count) ?>";
    var pageID = 1;
    var sysRateLimit = <?= (int)(config('system.security.rate_limit') ?? 1) * 1000 ?>;
    var chatbox = "<?= url('/shoutbox/list') ?>",
        chat_send = "<?= url('/shoutbox/send') ?>",
        chat_count = "<?= url('/shoutbox/count') ?>",
        chat_ele = "<?= url('/shoutbox/ele?chatID=') ?>",
        chat_list = "<?= url('/shoutbox/list?page=') ?>";
</script>
<script type="text/javascript" src="<?= url('/templates' . get_template() . '/public/js/shoutbox.js?v=' . time()) ?>"></script>