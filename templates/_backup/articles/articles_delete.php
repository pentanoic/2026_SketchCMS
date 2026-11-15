<?php $this->layout('layout'); ?>

<div class="dw-breadcrumb" itemscope="itemscope" itemtype="https://schema.org/BreadcrumbList">
    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="<?= url('/articles') ?>" itemprop="item"><i class="fa fa-home"></i> <span itemprop="name">Diễn đàn</span></a>
        <meta itemprop="position" content="1" />
    </span>
    <span class="dw-breadcrumb-sep"><i class="fa fa-angle-right"></i></span>
    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="<?= url('/index.html?category=' . $CategoryDetail['id']) ?>" itemprop="item"><span itemprop="name"><?= $CategoryDetail['name'] ?></span></a>
        <meta itemprop="position" content="2" />
    </span>
    <span class="dw-breadcrumb-sep"><i class="fa fa-angle-right"></i></span>
    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="<?= url('/articles/' . $PostDetail['id'] . '-' . $PostDetail['slug'] . '.html') ?>" itemprop="item"><span itemprop="name"><?= $PostDetail['title'] ?></span></a>
        <meta itemprop="position" content="3" />
    </span>
    <span class="dw-breadcrumb-sep"><i class="fa fa-angle-right"></i></span>
    <?php if ($action == 'post'): ?>
        <span class="dw-breadcrumb-current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span itemprop="name">Xóa chủ đề</span>
            <meta itemprop="position" content="4" />
        </span>
    <?php else: ?>
        <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="<?= url('/view-chap/' . $ChapterDetail['id'] . '-' . $ChapterDetail['slug'] . '.html') ?>" itemprop="item"><span itemprop="name">Chương: <?= $ChapterDetail['title'] ?></span></a>
            <meta itemprop="position" content="4" />
        </span>
        <span class="dw-breadcrumb-sep"><i class="fa fa-angle-right"></i></span>
        <span class="dw-breadcrumb-current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span itemprop="name">Xóa chương</span>
            <meta itemprop="position" content="5" />
        </span>
    <?php endif; ?>
</div>

<div class="dw-card mt-3">
    <div class="dw-card-header">
        <h3 class="dw-card-title"><i class="fa fa-trash-o text-danger"></i> <?php echo ($action == 'post') ? 'Xóa chủ đề' : 'Xóa nội dung chương'; ?></h3>
    </div>
    
    <div class="dw-card-body">
        <?php if ($error): ?>
            <div class="dw-alert dw-alert-warning mb-3"><?= _e($error) ?></div>
        <?php endif ?>
        
        <form name="form" action method="post" class="text-center py-4">
    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <i class="fa fa-exclamation-triangle fa-4x text-warning mb-3"></i>
            <h4 class="mb-3">Bạn có thực sự muốn xóa <?php echo ($action == 'post') ? 'chủ đề' : 'chương'; ?> này không?</h4>
            <p class="text-muted mb-4">Hành động này không thể hoàn tác. Mọi dữ liệu liên quan sẽ bị xóa vĩnh viễn.</p>
            
            <div>
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <a href="javascript:history.back()" class="dw-btn dw-btn-ghost mr-2">Hủy bỏ</a>
                <button type="submit" class="dw-btn dw-btn-danger"><i class="fa fa-trash"></i> Xác nhận xóa</button>
            </div>
        </form>
    </div>
</div>