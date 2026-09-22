<?php $this->layout('layout'); ?>

<div class="dw-card mb-4">
    <!-- Header with Tabs -->
    <div class="dw-card-header d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom-0 pb-0">
        <h3 class="dw-card-title m-0"><i class="fa fa-envelope text-primary"></i> Hộp thư cá nhân</h3>
        <ul class="nav nav-pills flex-nowrap" style="gap: 5px; overflow-x: auto;">
            <li class="nav-item">
                <a class="nav-link text-muted bg-light" style="border-radius: 20px; padding: 5px 15px; white-space: nowrap;" href="/mail">
                    <i class="fa fa-inbox mr-1"></i> Tin nhắn riêng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active font-weight-bold text-white shadow-sm" style="background: var(--accent-gradient); border-radius: 20px; padding: 5px 15px; white-space: nowrap;" href="/mail/system">
                    <i class="fa fa-bell-o mr-1"></i> Tin hệ thống
                </a>
            </li>
        </ul>
    </div>
    
    <div class="dw-card-body p-0 mt-3 border-top">
        <?php if ($MailSystemCount > 0): ?>
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-muted text-uppercase small"><i class="fa fa-bullhorn mr-1"></i> Thông báo từ Ban Quản Trị</h6>
                <div>
                    <a href="?mod=view" class="btn btn-sm btn-outline-primary rounded-pill px-3 mr-2"><i class="fa fa-check mr-1"></i> Đánh dấu đã đọc</a>
                    <a href="?mod=clear" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Bạn có chắc chắn muốn xóa toàn bộ tin hệ thống?');"><i class="fa fa-trash mr-1"></i> Xóa tất cả</a>
                </div>
            </div>
            
            <div class="list-group list-group-flush rounded-bottom">
                <?php foreach ($MailSystemList as $MailSystemDetail): ?>
                <?php $isNew = $MailSystemDetail['view'] != 'yes'; ?>
                <div class="list-group-item list-group-item-action d-flex align-items-start p-3 <?= $isNew ? 'bg-light' : '' ?>" style="border-color: var(--border) !important;">
                    <div class="mr-3 mt-1">
                        <div class="rounded-circle d-flex align-items-center justify-content-center <?= $isNew ? 'bg-danger text-white' : 'bg-secondary text-white' ?>" style="width: 40px; height: 40px;">
                            <i class="fa <?= $isNew ? 'fa-bell' : 'fa-bell-o' ?>"></i>
                        </div>
                    </div>
                    
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="m-0 font-weight-bold <?= $isNew ? 'text-dark' : 'text-muted' ?>">
                                <?php
                                $sysName = 'Hệ thống';
                                if (defined('UserBot')) {
                                    $sysNick = UserBot;
                                    $sysInfo = \Container::get(\DB::class)->prepare("SELECT name FROM users WHERE nick = ?");
                                    $sysInfo->execute([$sysNick]);
                                    if ($sysFetch = $sysInfo->fetch(\PDO::FETCH_ASSOC)) {
                                        $sysName = _e($sysFetch['name']);
                                    }
                                }
                                echo $sysName;
                                ?>
                                <?php if ($isNew): ?>
                                    <span class="badge badge-danger ml-1">Mới</span>
                                <?php endif ?>
                            </h6>
                            <small class="text-muted text-nowrap"><i class="fa fa-clock-o mr-1"></i><?= ago($MailSystemDetail['time']) ?></small>
                        </div>
                        <div class="small <?= $isNew ? 'font-weight-bold text-dark' : 'text-muted' ?>" style="line-height: 1.5; word-wrap: break-word;">
                            <?= $MailSystemDetail['content'] ?>
                        </div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
            
        <?php else: ?>
            <div class="p-5 text-center text-muted">
                <i class="fa fa-bell-slash-o fa-4x mb-3 text-light"></i>
                <h5 class="mb-1">Không có thông báo mới</h5>
                <p class="mb-0 small">Ban quản trị chưa gửi tin nhắn nào cho bạn.</p>
            </div>
        <?php endif ?>
    </div>
</div>