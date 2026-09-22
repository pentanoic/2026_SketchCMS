<?php $this->layout('layout'); ?>

<div class="dw-card mb-4">
    <!-- Header with Tabs -->
    <div class="dw-card-header d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom-0 pb-0">
        <h3 class="dw-card-title m-0"><i class="fa fa-envelope text-primary"></i> Hộp thư cá nhân</h3>
        <ul class="nav nav-pills flex-nowrap" style="gap: 5px; overflow-x: auto;">
            <li class="nav-item">
                <a class="nav-link active font-weight-bold text-white shadow-sm" style="background: var(--accent-gradient); border-radius: 20px; padding: 5px 15px; white-space: nowrap;" href="/mail">
                    <i class="fa fa-inbox mr-1"></i> Tin nhắn riêng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-muted bg-light" style="border-radius: 20px; padding: 5px 15px; white-space: nowrap;" href="/mail/system">
                    <i class="fa fa-server mr-1"></i> Tin hệ thống
                </a>
            </li>
        </ul>
    </div>
    
    <div class="dw-card-body p-0 mt-3">
        <?php if ($MailCount > 0): ?>
            <?php foreach ($MailList as $MailDetail): ?>
            <?php $isNew = $MailDetail['LatestMailDetail']['view'] != 'yes' && $MailDetail['LatestMailDetail']['nick'] != $MyDetail['nick']; ?>
            
            <div class="d-flex p-3 border-top position-relative <?= $isNew ? 'bg-light' : '' ?>" style="border-color: var(--border) !important; transition: background 0.2s;">
                <!-- Avatar -->
                <div class="mr-3 text-center" style="width: 50px;">
                    <a href="<?= url('/user/' . $MailDetail['ReceiverDetail']['nick']) ?>">
                        <img src="<?= getAvtUser($MailDetail['ReceiverDetail']) ?>" class="rounded-circle shadow-sm" style="width: 50px; height: 50px; object-fit: cover; border: 2px solid <?= $isNew ? 'var(--info)' : 'transparent' ?>;">
                    </a>
                </div>
                
                <!-- Content -->
                <div class="flex-grow-1 min-width-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="m-0 font-weight-bold text-truncate" style="font-size: 1.05rem;">
                            <?= RoleColor($MailDetail['ReceiverDetail']) ?>
                            <?php if ($isNew): ?>
                                <span class="badge badge-danger ml-1" style="font-size: 10px; vertical-align: middle;">MỚI</span>
                            <?php endif ?>
                        </h6>
                        <small class="text-muted text-nowrap ml-2"><i class="fa fa-clock-o mr-1"></i><?= ago($MailDetail['LatestMailDetail']['time']) ?></small>
                    </div>
                    
                    <div class="text-muted small mb-2 text-truncate" style="max-width: 90%;">
                        <?php if ($MyDetail['id'] == $MailDetail['SenderDetail']['id']): ?>
                            <span class="text-secondary font-italic mr-1"><i class="fa fa-reply"></i> Bạn:</span>
                        <?php endif ?>
                        <?= strip_tags($MailDetail['LatestMailDetail']['content']) ?>
                    </div>
                    
                    <div>
                        <a href="/mail/send/<?= $MailDetail['ReceiverDetail']['nick'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 12px; font-weight: bold;">
                            <i class="fa fa-comments mr-1"></i> Hội thoại (<?= $MailDetail['MailCount'] ?>)
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach ?>
            
            <div class="dw-card-footer bg-transparent text-center pt-3 border-0">
                <?= $MailListPaging ?>
            </div>
            
        <?php else: ?>
            <div class="p-5 text-center text-muted border-top">
                <i class="fa fa-inbox fa-4x mb-3 text-light"></i>
                <h5 class="mb-1">Hộp thư trống</h5>
                <p class="mb-0 small">Bạn chưa có cuộc hội thoại nào.</p>
            </div>
        <?php endif ?>
    </div>
</div>