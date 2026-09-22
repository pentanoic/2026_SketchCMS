<?php 
$this->layout('layout'); 
$ReceiverColor = GetRoleColorStr($ReceiverDetail);
?>
<link href="<?= $this->asset('/templates' . get_template() . '/public/css/in-mail.css') ?>" rel="stylesheet" />

<style>
/* CSS cho Fullscreen Chat */
.chat-fullscreen-wrapper.is-fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 99999;
    background: #f0f2f5;
    display: flex;
    flex-direction: row;
    padding: 0 !important;
    margin: 0 !important;
}

.chat-fullscreen-wrapper.is-fullscreen .chat-sidebar {
    display: flex !important;
    width: 350px;
    background: white;
    border-right: 1px solid var(--border);
    flex-direction: column;
}

.chat-fullscreen-wrapper.is-fullscreen .dw-card {
    flex-grow: 1;
    margin-bottom: 0 !important;
    border: none;
    border-radius: 0;
    display: flex;
    flex-direction: column;
    height: 100vh;
}

.chat-fullscreen-wrapper.is-fullscreen .dw-card-body {
    flex-grow: 1;
    max-height: none !important;
}

.chat-sidebar-header {
    padding: 15px;
    font-size: 1.25rem;
    font-weight: bold;
    border-bottom: 1px solid var(--border);
    background: white;
}

.chat-sidebar-content {
    overflow-y: auto;
    flex-grow: 1;
}

.chat-sidebar-item {
    padding: 10px 15px;
    border-bottom: 1px solid var(--border);
    transition: background 0.2s;
    cursor: pointer;
    text-decoration: none !important;
    display: flex;
    align-items: center;
    color: inherit;
}

.chat-sidebar-item:hover {
    background: var(--surface-muted);
}

@media (max-width: 768px) {
    .chat-fullscreen-wrapper.is-fullscreen .chat-sidebar {
        display: none !important; /* Ẩn sidebar trên mobile khi fullscreen */
    }
}
</style>

<div class="chat-fullscreen-wrapper" id="chatWrapper">
    
    <!-- Sidebar (Chỉ hiện khi Fullscreen) -->
    <div class="chat-sidebar" style="display: none;">
        <div class="chat-sidebar-header d-flex justify-content-between align-items-center">
            <span>Đoạn chat</span>
            <a href="/mail" class="btn btn-sm btn-light rounded-circle"><i class="fa fa-external-link"></i></a>
        </div>
        <div class="chat-sidebar-content" id="sidebarChatList">
            <div class="p-4 text-center text-muted">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
                <div class="mt-2 small">Đang tải...</div>
            </div>
        </div>
    </div>

    <div class="dw-card mb-4" id="chatCard">
        <!-- Header -->
        <div class="dw-card-header d-flex align-items-center justify-content-between p-3 border-bottom">
            <div class="d-flex align-items-center">
                <a href="/mail" class="btn btn-light rounded-circle mr-3" style="width: 35px; height: 35px; padding: 0; line-height: 35px; text-align: center;"><i class="fa fa-arrow-left"></i></a>
                <div>
                    <h5 class="m-0 font-weight-bold"><?= RoleColor($ReceiverDetail) ?></h5>
                    <?php $isOnline = $ReceiverDetail['on'] >= (date('U') - 300); ?>
                    <small class="<?= $isOnline ? 'text-success' : 'text-muted' ?>">
                        <i class="fa fa-circle" style="font-size: 8px; vertical-align: middle;"></i> <?= $isOnline ? 'Đang hoạt động' : 'Ngoại tuyến' ?>
                    </small>
                </div>
            </div>
            
            <div>
                <div class="d-inline-block mr-2" style="transform: scale(0.8); transform-origin: right center;">
                    <?= $MailPaging ?>
                </div>
                <button type="button" class="btn btn-sm btn-light rounded-circle font-weight-bold mr-1" id="btnFullscreen" title="Phóng to" style="width: 32px; height: 32px; vertical-align: middle;">
                    <i class="fa fa-expand"></i>
                </button>
                <a href="?mod=blocklist" class="btn btn-sm <?= in_array($ReceiverDetail['nick'], $SenderDetailBlockList) ? 'btn-success' : 'btn-outline-danger' ?> rounded-pill font-weight-bold px-3" style="vertical-align: middle;">
                    <?php if (in_array($ReceiverDetail['nick'], $SenderDetailBlockList)): ?>
                        <i class="fa fa-unlock-alt"></i> Mở chặn
                    <?php else: ?>
                        <i class="fa fa-ban"></i> Chặn
                    <?php endif ?>
                </a>
            </div>
        </div>

        <!-- Message Stream -->
        <div class="dw-card-body p-3" style="background: var(--surface-muted); max-height: 500px; overflow-y: auto; display: flex; flex-direction: column-reverse;">
            <div class="d-flex flex-column-reverse">
                <?php foreach ($MailList as $MailDetail): ?>
                <?php
                    $isMe  = $MailDetail['UserDetail']['nick'] === $SenderDetail['nick'];
                    $isNew = $MailDetail['view'] !== 'yes';
                ?>
                <div class="d-flex mb-3 <?= $isMe ? 'justify-content-end' : '' ?>">
                    <?php if (!$isMe): ?>
                    <div class="mr-2">
                        <img src="<?= getAvtUser($MailDetail['UserDetail']) ?>" class="rounded-circle shadow-sm" style="width: 35px; height: 35px; object-fit: cover;">
                    </div>
                    <?php endif ?>
                    
                    <div style="max-width: 75%;">
                        <div class="p-3 rounded shadow-sm" style="<?= $isMe ? 'background: ' . $ReceiverColor . '; color: white; border-bottom-right-radius: 5px !important;' : 'background: white; border-bottom-left-radius: 5px !important;' ?>">
                            <?php if ($MailDetail['view'] != 'yes' && !$isMe): ?>
                                <span class="badge badge-danger mb-1">MỚI</span>
                                <?= $view($MailDetail['id']) ?>
                            <?php endif ?>
                            <div class="mb-1" style="word-wrap: break-word;">
                                <?= $MailDetail['content'] ?>
                            </div>
                        </div>
                        
                        <div class="text-muted small mt-1 <?= $isMe ? 'text-right' : '' ?>">
                            <i class="fa fa-clock-o"></i> <?= ago($MailDetail['time']) ?>
                            <?php if ($isMe && $MailDetail['view'] === 'yes'): ?>
                                <span class="text-success ml-1"><i class="fa fa-check-circle"></i> Đã xem</span>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
        </div>

        <!-- Compose Area -->
        <div class="dw-card-footer bg-white p-3 border-top">
            <?php if ($isSenderBlock): ?>
            <div class="alert alert-warning m-0">
                <i class="fa fa-ban mr-1"></i> Bạn đang chặn người dùng này. Hãy mở chặn để tiếp tục trò chuyện.
            </div>
            <?php elseif ($isReceiverBlock || $ReceiverDetail['level'] < 0): ?>
            <div class="alert alert-danger m-0">
                <i class="fa fa-minus-circle mr-1"></i> Bạn không thể nhắn tin cho người dùng này ngay lúc này!
            </div>
            <?php else: ?>
            <form id="form" action method="POST" name="form">
    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <div class="form-group mb-2">
                    <textarea id="postText" rows="2" class="form-control border-0 bg-light" name="content" placeholder="Nhập tin nhắn..." style="resize: none; border-radius: 15px; padding: 15px;"><?= $content ?></textarea>
                </div>
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-none d-md-block">
                        <?php include (dirname(dirname(__FILE__)) . '/toolbar.php') ?>
                    </div>
                    <div class="d-md-none">
                        <!-- Nút toolbar thu gọn cho mobile nếu cần -->
                        <button type="button" class="btn btn-light rounded-circle" onclick="show_hide('mobileToolbar')"><i class="fa fa-plus text-muted"></i></button>
                    </div>
                    <button id="submit" type="submit" class="btn btn-primary rounded-pill px-4 font-weight-bold shadow-sm" style="background: <?= _e($ReceiverColor) ?>; border: none;">
                        <i class="fa fa-paper-plane mr-1"></i> Gửi
                    </button>
                </div>
                
                <div id="mobileToolbar" style="display:none;" class="mt-2">
                    <?php include (dirname(dirname(__FILE__)) . '/toolbar.php') ?>
                </div>
            </form>
            <?php endif ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tự động cuộn xuống dưới cùng của trang để form soạn thảo xuất hiện
    if (!window.location.hash) {
        setTimeout(function() {
            window.scrollTo(0, document.body.scrollHeight);
            const postText = document.getElementById('postText');
            if (postText && window.innerWidth > 768) {
                postText.focus(); // Tự động focus trên Desktop
            }
        }, 100);
    }

    const btnFullscreen = document.getElementById('btnFullscreen');
    const chatWrapper = document.getElementById('chatWrapper');
    const sidebarChatList = document.getElementById('sidebarChatList');
    const icon = btnFullscreen.querySelector('i');
    let hasLoadedSidebar = false;

    btnFullscreen.addEventListener('click', function() {
        chatWrapper.classList.toggle('is-fullscreen');
        
        if (chatWrapper.classList.contains('is-fullscreen')) {
            // Chuyển sang thu nhỏ
            icon.classList.remove('fa-expand');
            icon.classList.add('fa-compress');
            
            // Nếu chưa tải danh sách chat bên sidebar thì tải bằng fetch
            if (!hasLoadedSidebar) {
                fetch('/mail')
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        // Lấy các dòng chat (các div.d-flex bên trong dw-card-body)
                        const chatItems = doc.querySelectorAll('.dw-card-body > .d-flex.border-top');
                        
                        if (chatItems.length > 0) {
                            sidebarChatList.innerHTML = '';
                            chatItems.forEach(item => {
                                // Trích xuất thông tin để tạo lại layout nhỏ gọn hơn
                                const linkUrl = item.querySelector('a.btn') ? item.querySelector('a.btn').href : '#';
                                const avatarSrc = item.querySelector('img') ? item.querySelector('img').src : '';
                                const nameHtml = item.querySelector('h6') ? item.querySelector('h6').innerHTML : 'Người dùng';
                                const msgHtml = item.querySelector('.text-muted.small.mb-2') ? item.querySelector('.text-muted.small.mb-2').innerHTML : '';
                                
                                const sidebarItem = document.createElement('a');
                                sidebarItem.className = 'chat-sidebar-item';
                                sidebarItem.href = linkUrl;
                                sidebarItem.innerHTML = `
                                    <img src="${avatarSrc}" class="rounded-circle mr-3" style="width: 45px; height: 45px; object-fit: cover;">
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="m-0 text-truncate" style="font-size: 14px;">${nameHtml}</div>
                                        <div class="text-muted text-truncate small" style="max-width: 100%;">${msgHtml}</div>
                                    </div>
                                `;
                                sidebarChatList.appendChild(sidebarItem);
                            });
                        } else {
                            sidebarChatList.innerHTML = '<div class="p-4 text-center text-muted">Không có cuộc trò chuyện nào</div>';
                        }
                        hasLoadedSidebar = true;
                    })
                    .catch(err => {
                        sidebarChatList.innerHTML = '<div class="p-4 text-center text-danger">Lỗi tải danh sách</div>';
                        console.error('Error fetching chat list:', err);
                    });
            }
            
            // Xử lý giấu Navbar của theme nếu có
            if (document.querySelector('.navbar')) {
                document.querySelector('.navbar').style.zIndex = '0';
            }
        } else {
            // Khôi phục
            icon.classList.remove('fa-compress');
            icon.classList.add('fa-expand');
            if (document.querySelector('.navbar')) {
                document.querySelector('.navbar').style.zIndex = '';
            }
        }
    });
});
</script>