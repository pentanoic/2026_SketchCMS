<?php $this->layout('layout'); ?>

<style>
    div[role="chapter"],
    div[role="share"] {
        display: none;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = {
            'chapter-btn': 'chapter',
            'share-btn': 'share'
        };
        Object.keys(buttons).forEach(buttonId => {
            const btn = document.getElementById(buttonId);
            if(btn) {
                btn.onclick = function(e) {
                    e.preventDefault();
                    const activeRole = buttons[buttonId];
                    showContent(activeRole, buttonId);
                };
            }
        });

        function showContent(role, btnId) {
            const roles = ['chapter', 'share'];
            roles.forEach(r => {
                const el = document.querySelector('div[role="' + r + '"]');
                if(el) el.style.display = 'none';
                
                const b = document.getElementById(r + '-btn');
                if(b) {
                    b.classList.remove('dw-btn-primary');
                    b.classList.add('dw-btn-outline');
                }
            });
            const el = document.querySelector('div[role="' + role + '"]');
            if(el) el.style.display = 'block';
            
            const b = document.getElementById(btnId);
            if(b) {
                b.classList.remove('dw-btn-outline');
                b.classList.add('dw-btn-primary');
            }
        }
    });
</script>

<!-- Breadcrumb -->
<div class="dw-breadcrumb" itemscope="itemscope" itemtype="https://schema.org/BreadcrumbList">
    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="<?= url('/') ?>" itemprop="item"><i class="fa fa-home"></i> <span itemprop="name">Diễn đàn</span></a>
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
</div>

<div class="dw-post-wrap" itemscope="itemscope" itemtype="http://schema.org/BlogPosting">
    <a name="<?= $ChapterDetail['title'] ?>"></a>

    <div class="dw-post-header">
        <h1 itemprop="name headline" style="text-align: center; margin-bottom: 10px;"><?= $ChapterDetail['title'] ?></h1>
    </div>

    <div class="dw-post-layout">
        <div class="dw-post-author-col">
            <div class="dw-post-author-sticky">
                <img class="dw-avt lozad" data-src="<?= getAvtUser($UserDetail) ?>" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="Avatar" />
                <div class="dw-post-author-name"><?= RoleColor($UserDetail) ?></div>
                <div class="dw-post-author-role"><?= strip_tags(RoleColor($UserDetail, 'position')) ?></div>
                <div class="dw-post-author-status"><?= $UserDetail['status'] ? $UserDetail['status'] : 'Tôi yêu Việt Nam' ?></div>
                
                <div class="dw-post-author-stats">
                    <div class="dw-post-author-stat-row">
                        <span>Ngày đăng</span>
                        <span><abbr class="updated published" itemprop="datePublished"><?= date('d/m/Y', $PostDetail['time']) ?></abbr></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dw-post-content-col">
            <?php if ($isPersonCanAction): ?>
            <div class="dw-alert dw-alert-warning p-2 mb-3 d-flex align-items-center" style="gap: 10px;">
                <b><i class="fa fa-wrench"></i> Công cụ:</b>
                <a href="/articles/chapter-<?= $ChapterDetail['id'] ?>/edit" class="dw-btn dw-btn-sm dw-btn-ghost"><i class="fa fa-pencil-square-o"></i> Sửa</a>
                <a href="/articles/post-<?= $ChapterDetail['id'] ?>/add-chap" class="dw-btn dw-btn-sm dw-btn-ghost"><i class="fa fa-plus"></i> Thêm chương</a>
                <a href="/articles/chapter-<?= $ChapterDetail['id'] ?>/delete" class="dw-btn dw-btn-sm dw-btn-danger" onclick="return confirm('XÁC NHẬN XÓA?')"><i class="fa fa-trash-o"></i> Xóa</a>
            </div>
            <?php endif ?>

            <div class="dw-post-body" style="font-size: 16px; line-height: 1.8; color: var(--fg);">
                <?= $ChapterDetail['content'] ?>
            </div>
            
            <div class="dw-post-action-bar mt-4 justify-content-center" style="border-top: 1px dashed var(--border); padding-top: 15px;">
                <button id="chapter-btn" class="dw-btn dw-btn-outline"><i class="fa fa-bars"></i> Danh sách chương (<?= $ForumStats['count_chapter_in_post'] ?>)</button>
                <button id="share-btn" class="dw-btn dw-btn-outline"><i class="fa fa-share-square-o"></i> Chia sẻ</button>
            </div>
        </div>
    </div>
</div>

<!-- Chapter List -->
<div class="dw-card mt-3" role="chapter">
    <div class="dw-card-header"><h3 class="dw-card-title"><i class="fa fa-list-ol"></i> Danh sách chương</h3></div>
    <div class="dw-card-body p-0">
        <?php if ($ForumStats['count_chapter_in_post'] > 0): ?>
        <div class="list-group list-group-flush" style="border-radius: 0;">
            <?php foreach ($ChapterList as $ChapterItem): ?>
                <?php if ($ChapterDetail['id'] == $ChapterItem['id']): ?>
                <div class="list-group-item d-flex align-items-center py-2" style="background: var(--surface-muted); font-weight: bold; border-left: 3px solid var(--primary);">
                    <i class="fa fa-angle-right mr-2 text-primary"></i> <?= $ChapterItem['title'] ?> <span class="dw-badge dw-badge-accent ml-2">Đang đọc</span>
                </div>
                <?php else: ?>
                <a href="<?= url('/view-chap/' . $ChapterItem['id'] . '-' . $ChapterItem['slug']) ?>" class="list-group-item list-group-item-action d-flex align-items-center py-2">
                    <i class="fa fa-angle-right mr-2 text-muted"></i> <?= $ChapterItem['title'] ?>
                </a>
                <?php endif ?>
            <?php endforeach ?>
        </div>
        <?php else: ?>
        <div class="dw-empty-state"><i class="fa fa-folder-open-o"></i> Trống!</div>
        <?php endif ?>
    </div>
</div>

<!-- Share -->
<div class="dw-card mt-3" role="share">
    <div class="dw-card-header"><h3 class="dw-card-title"><i class="fa fa-share-alt"></i> Chia sẻ liên kết</h3></div>
    <div class="dw-card-body">
        <div class="dw-form-group">
            <label class="dw-label">BBcode:</label>
            <input class="dw-input" type="text" value="[url=<?= url($ChapterDetail['url']) ?>]<?= $ChapterDetail['title'] ?>[/url]" onclick="this.select()" readonly />
        </div>
        <div class="dw-form-group">
            <label class="dw-label">Markdown:</label>
            <input class="dw-input" type="text" value="[<?= $ChapterDetail['title'] ?>](<?= url($ChapterDetail['url']) ?>)" onclick="this.select()" readonly />
        </div>
    </div>
</div>