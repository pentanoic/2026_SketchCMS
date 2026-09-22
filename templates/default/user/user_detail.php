<?php $this->layout('layout'); ?>
<link href="<?= $this->asset('/templates' . get_template() . '/public/css/in-profile.css') ?>" rel="stylesheet" />

<div class="row">
    <?php if ($PostCount < 5): ?>
        <div class="col-lg-6 mb-4">
    <?php else: ?>
        <div class="col-lg-4 mb-4">
    <?php endif; ?>

        <!-- Profile Card -->
        <div class="dw-card <?= $PostCount < 5 ? '' : 'mb-4' ?>" style="overflow: hidden; height: <?= $PostCount < 5 ? '100%' : 'auto' ?>;">
            <!-- Cover Image -->
            <div class="position-relative" style="height: 150px; background-color: var(--surface-muted); background-image: url('<?= getCoverUser($YourDetail) ?>'); background-size: cover; background-position: center;">
                <?php if ($MyDetail['id'] == $YourDetail['id']): ?>
                <a href="/user/<?= $YourDetail['nick'] ?>/cover" class="btn btn-sm btn-dark position-absolute" style="top: 10px; right: 10px; opacity: 0.8; border-radius: 20px;">
                    <i class="fa fa-camera mr-1"></i> Đổi ảnh bìa
                </a>
                <?php endif ?>
            </div>
            
            <div class="dw-card-body text-center pt-0 px-4 pb-4">
                <!-- Avatar -->
                <div class="position-relative d-inline-block" style="margin-top: -50px; margin-bottom: 15px;">
                    <?php if ($MyDetail['id'] == $YourDetail['id']): ?><a href="/user/<?= $YourDetail['nick'] ?>/avatar"><?php endif ?>
                    <img src="<?= getAvtUser($YourDetail) ?>" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover; border: 4px solid var(--surface); box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <?php if ($MyDetail['id'] == $YourDetail['id']): ?>
                        <div class="position-absolute bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; bottom: 0; right: 0; opacity: 0.8; border: 2px solid var(--surface);">
                            <i class="fa fa-camera small"></i>
                        </div>
                    </a><?php endif ?>
                </div>
                
                <!-- Name & Status -->
                <h4 class="font-weight-bold mb-1"><?= RoleColor($YourDetail) ?></h4>
                <div class="mb-2"><?= strip_tags(RoleColor($YourDetail, 'position')) ?></div>
                <p class="text-muted small mb-3 text-truncate" title="<?= $YourDetail['status'] ?? 'Tôi yêu Việt Nam' ?>">
                    <?= $YourDetail['status'] ?? 'Tôi yêu Việt Nam' ?>
                </p>
                
                <!-- Action Buttons -->
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <?php if ($MyDetail['id'] == $YourDetail['id']): ?>
                        <a href="/user/<?= $YourDetail['nick'] ?>/info" class="btn btn-outline-primary btn-sm rounded-pill px-3 font-weight-bold">
                            <i class="fa fa-pencil-square-o mr-1"></i> Chỉnh sửa hồ sơ
                        </a>
                    <?php else: ?>
                        <a href="/mail/send/<?= $YourDetail['nick'] ?>" class="btn btn-primary btn-sm rounded-pill px-3 font-weight-bold mr-2" style="background:var(--accent-gradient);border:none;">
                            <i class="fa fa-telegram mr-1"></i> Nhắn tin
                        </a>
                        <?php if ($MyDetail['level'] >= 120 && $MyDetail['level'] > $YourDetail['level']): ?>
                            <a href="/user/<?= $YourDetail['nick'] ?>/ban" class="btn btn-outline-danger btn-sm rounded-pill px-3 font-weight-bold" onclick="return confirm('Bạn có chắc chắn?');">
                                <i class="fa fa-ban mr-1"></i> <?= $YourDetail['level'] >= 0 ? 'Khóa tài khoản' : 'Mở khóa' ?>
                            </a>
                        <?php endif ?>
                    <?php endif ?>
                </div>
            </div>
            
            <div class="dw-card-footer bg-transparent p-0 mt-auto">
                <div class="d-flex text-center text-muted small">
                    <div class="col-6 py-3 border-right">
                        <div class="font-weight-bold text-dark text-lg" style="font-size: 1.2rem; color: var(--fg) !important;"><?= number_format($YourDetail['xu']) ?></div>
                        <div>Xu</div>
                    </div>
                    <div class="col-6 py-3">
                        <div class="font-weight-bold text-success text-lg" style="font-size: 1.2rem;"><?= number_format($YourDetail['karma']) ?> <i class="fa fa-paw"></i></div>
                        <div>Karma</div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php if ($PostCount < 5): ?>
        </div>
        <div class="col-lg-6 mb-4">
    <?php endif; ?>
        
        <!-- Info Card -->
        <div class="dw-card" style="height: <?= $PostCount < 5 ? '100%' : 'auto' ?>;">
            <div class="dw-card-header pb-0 border-bottom-0">
                <h6 class="font-weight-bold m-0"><i class="fa fa-info-circle text-primary mr-2"></i>Thông tin cá nhân</h6>
            </div>
            <div class="dw-card-body p-3">
                <ul class="list-group list-group-flush small" style="background: transparent;">
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center" style="background: transparent; border-color: var(--border);">
                        <span class="text-muted">Giới tính</span>
                        <span class="font-weight-bold <?= $YourDetail['sex'] == 'boy' ? 'text-primary' : 'text-danger' ?>"><?= ['boy' => 'Nam', 'girl' => 'Nữ'][$YourDetail['sex']] ?></span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center" style="background: transparent; border-color: var(--border);">
                        <span class="text-muted">Ngày tham gia</span>
                        <span class="font-weight-bold" style="color: var(--fg);"><?= date('d/m/Y', $YourDetail['reg']) ?></span>
                    </li>
                    <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center border-bottom-0" style="background: transparent;">
                        <span class="text-muted">Hoạt động cuối</span>
                        <span class="font-weight-bold <?= $YourDetail['on'] >= (date('U') - 300) ? 'text-success' : '' ?>" style="color: var(--fg);">
                            <?= $YourDetail['on'] >= (date('U') - 300) ? '<span class="text-success">Đang online <i class="fa fa-circle ml-1"></i></span>' : _e(ago($YourDetail['on'])) ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
        
    </div>
    
    <div class="<?= $PostCount < 5 ? 'col-lg-12' : 'col-lg-8' ?>">
        <!-- Recent Posts -->
        <div class="dw-card">
            <div class="dw-card-header">
                <h3 class="dw-card-title"><i class="fa fa-pencil-square-o mr-2" style="color:var(--accent)"></i>Hoạt động gần đây</h3>
            </div>
            <div class="dw-card-body p-0">
                <?php if ($PostCount > 0): ?>
                    <?php foreach ($PostList as $PostItem): ?>
                    <div class="dw-post-row">
                        <div class="dw-post-icon">
                            <div class="dw-post-topic-icon"><i class="fa fa-file-text-o"></i></div>
                            <div style="min-width:0;">
                                <div class="dw-post-title">
                                    <a href="<?= url('/articles/' . $PostItem['id'] . '-' . $PostItem['slug']) ?>.html">
                                        <?= $PostItem['title'] ?>
                                    </a>
                                </div>
                                <div class="dw-post-meta">
                                    <span class="mr-2"><i class="fa fa-cube"></i> <?= $PostItem['category_name'] ?></span>
                                    <span class="mr-2"><i class="fa fa-clock-o"></i> <?= ago($PostItem['time']) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="dw-post-stat">
                            <span class="dw-post-count"><?= $PostItem['comment'] ?></span>
                            <span>Trả lời</span>
                        </div>
                        <div class="dw-post-stat d-none d-sm-block">
                            <span class="dw-post-count"><?= $PostItem['view'] ?></span>
                            <span>Lượt xem</span>
                        </div>
                    </div>
                    <?php endforeach ?>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fa fa-folder-open-o fa-3x mb-3 text-light"></i>
                        <p class="mb-0">Chưa có bài viết nào</p>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>