<?php $this->layout('layout'); ?>

<div class="dw-card">
    <div class="dw-card-header">
        <h2 class="dw-card-title"><i class="fa fa-search"></i> Tìm kiếm trong trang</h2>
    </div>
    
    <div class="dw-card-body" style="border-bottom: 1px solid var(--border);">
        <?php if ($SearchResultCount > 0): ?>
            Hiển thị bài đăng được sắp xếp theo mức độ liên quan cho truy vấn: <b><?= _e($SearchQuery) ?></b>.
        <?php else: ?>
            Không có bài đăng nào phù hợp với truy vấn: <b><?= _e($SearchQuery) ?></b>.
        <?php endif ?>
        <br /><a href="<?= url('/') ?>" style="font-size: 13px; margin-top: 5px; display: inline-block;">&laquo; Hiển thị tất cả bài đăng</a>
    </div>

    <?php if ($SearchResultCount > 0): ?>
    <div class="dw-post-list">
        <?php foreach ($SearchResultList as $SearchResultDetail): ?>
        <div class="dw-post-row">
            <div class="dw-post-icon">
                <div class="dw-post-topic-icon"><i class="fa fa-file-text-o"></i></div>
                <div style="min-width: 0;">
                    <div class="dw-post-title">
                        <a href="<?= url($SearchResultDetail['url']) ?>">
                            <?= $SearchResultDetail['title'] ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>
    <?php if ($SearchResultCount > 0 && isset($SearchPaging)): ?>
    <div class="dw-card-footer d-flex justify-content-center">
        <?= $SearchPaging ?>
    </div>
    <?php endif ?>
</div>