<?php
$this->layout('manager/layout', ['page_title' => $page_title ?? 'Bảng điều khiển', 'panelActive' => 'dashboard']);
?>

<div class="mb-4">
    <h5 class="font-weight-bold text-uppercase mb-0" style="color:var(--heading); font-size:16px;">
        <i class="fa fa-gauge" style="color:var(--accent)"></i> Bảng điều khiển
    </h5>
</div>

<div class="row">
    <!-- Thống kê Users -->
    <?php if (module_menu_check('user')): ?>
    <div class="col-6 col-md-3 mb-4">
        <a href="<?= url('/manager/users') ?>" class="text-decoration-none">
            <div class="dw-card text-center h-100" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: #fff; border: none;">
                <div class="dw-card-body py-4">
                    <i class="fa fa-users mb-2" style="font-size: 2rem; opacity: 0.8;"></i>
                    <h6 class="font-weight-bold text-uppercase mb-1" style="font-size: 0.8rem; letter-spacing: 1px;">Thành viên</h6>
                    <div class="font-weight-bold" style="font-size: 2rem; line-height: 1;"><?= _e($totalUsers) ?></div>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <!-- Thống kê Bài viết -->
    <?php if (module_menu_check('articles')): ?>
    <div class="col-6 col-md-3 mb-4">
        <a href="<?= url('/manager/articles?tab=posts') ?>" class="text-decoration-none">
            <div class="dw-card text-center h-100" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: #fff; border: none;">
                <div class="dw-card-body py-4">
                    <i class="fa fa-file-text-o mb-2" style="font-size: 2rem; opacity: 0.8;"></i>
                    <h6 class="font-weight-bold text-uppercase mb-1" style="font-size: 0.8rem; letter-spacing: 1px;">Bài viết</h6>
                    <div class="font-weight-bold" style="font-size: 2rem; line-height: 1;"><?= _e($totalPosts) ?></div>
                </div>
            </div>
        </a>
    </div>
    <!-- Thống kê Chuyên mục -->
    <div class="col-6 col-md-3 mb-4">
        <a href="<?= url('/manager/articles?tab=categories') ?>" class="text-decoration-none">
            <div class="dw-card text-center h-100" style="background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); color: #fff; border: none;">
                <div class="dw-card-body py-4">
                    <i class="fa fa-folder-open-o mb-2" style="font-size: 2rem; opacity: 0.8;"></i>
                    <h6 class="font-weight-bold text-uppercase mb-1" style="font-size: 0.8rem; letter-spacing: 1px;">Chuyên mục</h6>
                    <div class="font-weight-bold" style="font-size: 2rem; line-height: 1;"><?= _e($totalCategories) ?></div>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
    <!-- Thống kê Shoutbox -->
    <?php if (module_menu_check('shoutbox')): ?>
    <div class="col-6 col-md-3 mb-4">
        <a href="<?= url('/manager/shoutbox') ?>" class="text-decoration-none">
            <div class="dw-card text-center h-100" style="background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%); color: #fff; border: none;">
                <div class="dw-card-body py-4">
                    <i class="fa fa-comments-o mb-2" style="font-size: 2rem; opacity: 0.8;"></i>
                    <h6 class="font-weight-bold text-uppercase mb-1" style="font-size: 0.8rem; letter-spacing: 1px;">Phòng chat</h6>
                    <div class="font-weight-bold" style="font-size: 2rem; line-height: 1;"><?= _e($totalShouts) ?></div>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="dw-card" style="border-left: 4px solid var(--info);">
    <div class="dw-card-body">
        <i class="fa fa-info-circle mr-2" style="color:var(--info);"></i> <strong>Hướng dẫn:</strong> Bảng điều khiển quản trị (Manager) cho phép bạn quản lý mọi thứ trong diễn đàn. Hãy chọn các chức năng bên menu trái để bắt đầu.
    </div>
</div>
