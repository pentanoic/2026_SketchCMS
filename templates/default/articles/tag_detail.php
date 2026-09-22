<?php $this->layout('layout'); ?>

<div class="dw-breadcrumb" itemscope="itemscope" itemtype="https://schema.org/BreadcrumbList">
    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="<?= url('/articles') ?>" itemprop="item"><i class="fa fa-home"></i> <span itemprop="name">Diễn đàn</span></a>
        <meta itemprop="position" content="1" />
    </span>
    <span class="dw-breadcrumb-sep"><i class="fa fa-angle-right"></i></span>
    <span class="dw-breadcrumb-current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <span itemprop="name">Tag: <?= $TagDetail['name'] ?></span>
        <meta itemprop="position" content="2" />
    </span>
</div>

<div class="dw-card mt-3">
    <div class="dw-card-header">
        <h3 class="dw-card-title">
            <i class="fa fa-tags"></i> Bài viết gắn thẻ: <?= $TagDetail['name'] ?>
        </h3>
    </div>

    <div class="dw-card-body p-0">
        <?php if ($PostCount > 0): ?>
            <?php foreach ($PostList as $PostItem): ?>
                <?php
                $isUpdatedToday = date('dmY', $PostItem['update_time']) == date('dmY');
                $isCreatedToday = date('dmY', $PostItem['time']) == date('dmY');
                $isHot = $isUpdatedToday && !$isCreatedToday;
                $isNew = $isUpdatedToday && $isCreatedToday;
                ?>
                <div class="dw-post-row <?= ($isNew || $isHot) ? 'dw-post-row-new' : '' ?>">
                    <div class="dw-post-icon">
                        <img src="<?= getAvtUser($PostItem['UserDetail'] ?? []) ?>" class="lozad" width="38" height="38" style="object-fit: cover; border-radius: 50%; border: 1px solid var(--border);" alt="Avatar">
                        <div style="min-width:0;">
                            <div class="dw-post-title">
                                <?php if ($isHot): ?><span class="badge badge-warning text-white mr-1">Hot</span><?php endif ?>
                                <?php if ($isNew): ?><span class="badge badge-success mr-1">New</span><?php endif ?>
                                <a href="<?= url('/articles/' . $PostItem['id'] . '-' . $PostItem['slug']) ?>.html">
                                    <?= ($PostItem['blocked'] == 1 ? '<i class="fa fa-lock text-danger mr-1" title="Đã khóa"></i>' : '') ?>
                                    <?= ($PostItem['sticked'] == 1 ? '<i class="fa fa-thumb-tack text-warning mr-1" title="Ghim"></i>' : '') ?>
                                    <?= $PostItem['title'] ?>
                                </a>
                            </div>
                            <div class="dw-post-meta">
                                <span class="mr-2"><i class="fa fa-clock-o"></i> <?= ago($PostItem['time']) ?></span>
                                <span><i class="fa fa-user"></i> <?= RoleColor($PostItem['UserDetail'] ?? ['nick' => $PostItem['author'], 'level' => 0]) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="dw-post-stat">
                        <span class="dw-post-count"><?= $PostItem['CommentCount'] ?? 0 ?></span>
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
                <i class="fa fa-hashtag fa-3x mb-2" style="color:var(--border-strong)"></i>
                <p>Chưa có chủ đề nào được gắn thẻ này.</p>
            </div>
        <?php endif ?>
    </div>
    <?php if (isset($PostPaging) && $PostCount > 0): ?>
        <div class="dw-card-footer d-flex justify-content-center">
            <?= $PostPaging ?>
        </div>
    <?php endif ?>
</div>
