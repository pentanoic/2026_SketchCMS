<?php $this->layout('layout'); ?>

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
    <span class="dw-breadcrumb-current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <span itemprop="name"><?= $PostDetail['title'] ?></span>
        <meta itemprop="position" content="3" />
    </span>
</div>

<?php if ($AdminActionResult): ?>
    <div class="dw-alert dw-alert-success"><i class="fa fa-check-circle"></i> <?= _e($AdminActionResult) ?></div>
<?php endif ?>

<div class="dw-post-wrap" itemscope="itemscope" itemtype="http://schema.org/BlogPosting">
    <meta content="<?= $PostDetail['thumbnail'] ?>" itemprop="image" />
    <a name="<?= $PostDetail['title'] ?>" itemprop="name headline"></a>

    <div class="dw-post-header">
        <h1><?= $PostDetail['title'] ?></h1>
        <div class="dw-post-header-meta">
            <span class="dw-meta-chip"><i class="fa fa-clock-o"></i> <abbr class="updated published" itemprop="datePublished"><?= ago($PostDetail['time']) ?></abbr></span>
            <span class="dw-meta-chip"><i class="fa fa-eye"></i> <?= number_format($PostDetail['view']) ?> lượt xem</span>
        </div>
        
        <?php $isPage2OrMore = isset($_GET['page']) && (int)$_GET['page'] > 1; ?>
        <?php if ($isPage2OrMore): ?>
        <div class="text-center mt-3">
            <button class="dw-btn dw-btn-outline dw-btn-sm" onclick="var el = document.getElementById('main-post-content'); if(el.style.display === 'none') { el.style.display = 'grid'; this.innerHTML = '<i class=\'fa fa-angle-up\'></i> Ẩn bài viết gốc'; } else { el.style.display = 'none'; this.innerHTML = '<i class=\'fa fa-angle-down\'></i> Xem bài viết gốc'; }">
                <i class="fa fa-angle-down"></i> Xem bài viết gốc
            </button>
        </div>
        <?php endif ?>
    </div>

    <div class="dw-post-layout" id="main-post-content" <?= $isPage2OrMore ? 'style="display:none;"' : '' ?>>
        <div class="dw-post-author-col">
            <div class="dw-post-author-sticky">
                <img class="dw-avt lozad" data-src="<?= getAvtUser($UserDetail) ?>" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="Avatar" />
                <div class="dw-post-author-name"><?= RoleColor($UserDetail) ?></div>
                <div class="dw-post-author-role"><?= strip_tags(RoleColor($UserDetail, 'position')) ?></div>
                <div class="dw-post-author-status"><?= $UserDetail['status'] ? $UserDetail['status'] : 'Tôi yêu Việt Nam' ?></div>
                
                <div class="dw-post-author-stats">
                    <div class="dw-post-author-stat-row">
                        <span>Bài viết</span>
                        <span><?= number_format((int)($TotalPostsAndComments ?? 0)) ?></span>
                    </div>
                    <div class="dw-post-author-stat-row">
                        <span>Tài sản</span>
                        <span><?= number_format((int)($UserDetail['xu'] ?? 0)) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dw-post-content-col">
            <div class="dw-post-time-bar">
                <span><i class="fa fa-calendar"></i> <?= date('d/m/Y H:i', $PostDetail['time']) ?></span>
                <a href="#comment" class="dw-badge dw-badge-accent"><i class="fa fa-comments"></i> Bình luận (<?= $ForumStats['count_comment_in_post'] ?? 0 ?>)</a>
            </div>
            
            <div class="dw-post-body" id="content">
                <?= $PostDetail['content'] ?>
                
                <?php if (!empty($PostLikeListDisplay)): ?>
                <div class="dw-alert dw-alert-info mt-3 p-2" style="font-size: 12px; margin-bottom: 0;">
                    <?= $PostLikeListDisplay ?>
                </div>
                <?php endif ?>
                
                <?php if (!empty($TagsList)): ?>
                <div class="mt-3" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                    <i class="fa fa-tags text-muted"></i>
                    <?php foreach ($TagsList as $tag): ?>
                    <a href="<?= url('/tag/' . $tag['slug']) ?>" class="dw-badge" style="background: var(--surface-muted); color: var(--fg); border: 1px solid var(--border); font-size: 12px; padding: 4px 8px; text-decoration: none;"><i class="fa fa-hashtag text-muted"></i> <?= $tag['name'] ?></a>
                    <?php endforeach ?>
                </div>
                <?php endif ?>
            </div>
            
            <div class="dw-post-action-bar">
                <?php if ($isLogin && !$isInLikeList): ?>
                    <a href="?mod=like" class="dw-btn dw-btn-outline dw-btn-sm"><i class="fa fa-thumbs-up"></i> Thích</a>
                <?php endif ?>
                <button onclick="var el=document.getElementById('post-share-block'); el.style.display = el.style.display === 'none' ? 'block' : 'none';" class="dw-btn dw-btn-outline dw-btn-sm"><i class="fa fa-share-square-o"></i> Chia sẻ</button>
                
                <?php if (isset($isPersonCanAction) && $isPersonCanAction): ?>
                    <a href="/articles/post-<?= $PostDetail['id'] ?>/edit" class="dw-btn dw-btn-ghost dw-btn-sm"><i class="fa fa-pencil-square-o"></i> Sửa</a>
                    <a href="/articles/post-<?= $PostDetail['id'] ?>/upload" class="dw-btn dw-btn-ghost dw-btn-sm"><i class="fa fa-cloud-upload"></i> Tải file</a>
                    <a href="/articles/post-<?= $PostDetail['id'] ?>/add-chap" class="dw-btn dw-btn-ghost dw-btn-sm"><i class="fa fa-plus"></i> Chương</a>

                    <?php if (isset($isAdminForum) && $isAdminForum): ?>
                        <div style="margin-left: auto; display: flex; gap: 6px; flex-wrap: wrap;">
                            <a href="?mod=<?= $ActionLock ?>" class="dw-btn dw-btn-ghost dw-btn-sm"><?= $ActionLockName ?></a>
                            <a href="?mod=<?= $ActionPin ?>" class="dw-btn dw-btn-ghost dw-btn-sm"><?= $ActionPinName ?></a>
                            <a href="/articles/post-<?= $PostDetail['id'] ?>/delete" class="dw-btn dw-btn-danger dw-btn-sm" onclick="return confirm('XÁC NHẬN XÓA?')"><i class="fa fa-trash-o"></i> Xóa</a>
                        </div>
                    <?php endif ?>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- Share Block -->
<div class="dw-card mb-3" id="post-share-block" style="display: none;">
    <div class="dw-card-header"><h3 class="dw-card-title"><i class="fa fa-share-alt"></i> Chia sẻ liên kết</h3></div>
    <div class="dw-card-body">
        <div class="dw-form-group">
            <label class="dw-label">BBcode:</label>
            <input class="dw-input" type="text" value="[url=<?= url('/articles/' . $PostDetail['id'] . '-' . $PostDetail['slug'] . '.html') ?>]<?= $PostDetail['title'] ?>[/url]" onclick="this.select()" readonly />
        </div>
        <div class="dw-form-group">
            <label class="dw-label">Markdown:</label>
            <input class="dw-input" type="text" value="[<?= $PostDetail['title'] ?>](<?= url('/articles/' . $PostDetail['id'] . '-' . $PostDetail['slug'] . '.html') ?>)" onclick="this.select()" readonly />
        </div>
    </div>
</div>

<!-- Files and Chapters (From original post_detail) -->
<?php if ($ForumStats['count_chapter_in_post'] > 0 || $ForumStats['count_file_in_post'] > 0): ?>
<div class="dw-card mb-3">
    <!-- Check if chapters exist, else hide -->
    <?php if ($ForumStats['count_chapter_in_post'] > 0): ?>
    <div class="dw-card-header"><h3 class="dw-card-title"><i class="fa fa-list-ol"></i> Danh sách chương (<?= $ForumStats['count_chapter_in_post'] ?>)</h3></div>
    <div class="dw-card-body p-0">
        <div class="list-group list-group-flush" style="border-radius: 0;">
            <?php $chapIdx = 1; foreach ($ChapterList as $ChapterDetail): ?>
            <a href="<?= url('/view-chap/' . $ChapterDetail['id'] . '-' . $ChapterDetail['slug']) ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2" style="background: transparent; border-color: var(--border);">
                <div>
                    <span class="dw-badge dw-badge-accent mr-2">CH-<?= str_pad($chapIdx++, 2, '0', STR_PAD_LEFT) ?></span>
                    <span style="font-size: 13.5px; font-weight: 500; color: var(--fg);"><?= $ChapterDetail['title'] ?></span>
                </div>
                <i class="fa fa-chevron-right text-muted" style="font-size:11px"></i>
            </a>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>
    
    <!-- Files -->
    <?php if ($ForumStats['count_file_in_post'] > 0): ?>
    <div class="dw-card-header <?= $ForumStats['count_chapter_in_post'] > 0 ? 'border-top' : '' ?>" style="border-color: var(--border);"><h3 class="dw-card-title"><i class="fa fa-folder-open-o"></i> Kho tập tin (<?= $ForumStats['count_file_in_post'] ?>)</h3></div>
    <div class="dw-card-body p-0">
        <div class="list-group list-group-flush" style="border-radius: 0;">
            <?php foreach ($FileList as $FileDetail): $isFree = $FileDetail['status'] === 'public'; ?>
            <a href="/view-file/<?= $FileDetail['id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center py-2" style="gap: 12px; background: transparent; border-color: var(--border);">
                <div style="width: 38px; height: 38px; border-radius: 8px; background: var(--surface-muted); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--fg-secondary);">
                    <i class="fa fa-<?= checkExtension($FileDetail['filename']) ?>"></i>
                </div>
                <div style="min-width: 0; flex: 1;">
                    <div style="font-size: 13.5px; font-weight: 500; color: var(--fg); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                        <?= $FileDetail['filename'] ?>
                    </div>
                    <div style="font-size: 11.5px; color: var(--fg-muted); margin-top: 2px;">
                        <i class="fa fa-hdd-o"></i> <?= FileSizeFormat($FileDetail['filesize']) ?> &bull; 
                        <?php if ($isFree): ?>
                            <span class="text-success"><i class="fa fa-unlock-alt"></i> Miễn phí</span>
                        <?php else: ?>
                            <span class="text-warning"><i class="fa fa-diamond"></i> <?= number_format($FileDetail['price']) ?> xu</span>
                        <?php endif ?>
                    </div>
                </div>
                <i class="fa fa-download text-muted"></i>
            </a>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>
</div>
<?php endif ?>

<a name="comment"></a>
<div class="dw-section-header mb-2 mt-4" style="background: transparent; border: none; padding: 0;">
    <div class="dw-section-header-label" style="font-size: 14px; color: var(--fg);"><i class="fa fa-comments mr-1"></i> BÌNH LUẬN (<?= $ForumStats['count_comment_in_post'] ?? 0 ?>)</div>
</div>

<?php if (!empty($ForumStats['count_comment_in_post']) && $ForumStats['count_comment_in_post'] > 0): ?>
    <?php if (isset($CommentListPaging)) echo $CommentListPaging; ?>

    <div class="dw-comment-wrap">
        <?php foreach ($CommentList as $CommentDetail): ?>
        <div class="dw-comment">
            <div class="dw-comment-author-col">
                <img class="dw-avt lozad" data-src="<?= getAvtUser($CommentDetail['UserDetail']) ?>" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="Avatar" />
                <div class="dw-comment-author-name">
                    <?= RoleColor($CommentDetail['UserDetail']) ?>
                </div>
                <?php if (isset($UserDetail['id']) && $CommentDetail['UserDetail']['id'] == $UserDetail['id']): ?>
                    <div class="dw-badge dw-badge-accent mt-1" style="font-size: 9px; padding: 1px 4px;">Tác giả</div>
                <?php endif ?>
            </div>
            <div style="min-width: 0;">
                <div class="dw-post-time-bar" style="padding: 6px 14px; background: transparent; border-bottom: 1px dashed var(--border);">
                    <span><i class="fa fa-clock-o"></i> <abbr class="updated published" itemprop="datePublished"><?= ago($CommentDetail['time']) ?></abbr></span>
                    <?php if (!isset($user) || (isset($user) && $user['id'] != $CommentDetail['UserDetail']['id'])): ?>
                    <a href="javascript:void(0)" onclick="let t=document.getElementById('postText');t.value+='@<?= $CommentDetail['UserDetail']['nick'] ?> ';t.focus();" class="badge badge-light shadow-sm float-right ml-1" title="Gắn thẻ" style="color: var(--accent);"><i class="fa fa-at"></i> Tag</a>
                    <a href="javascript:void(0)" onclick="let t=document.getElementById('postText');t.value+='@[module=articles;quote=<?= $CommentDetail['id'] ?>] ';t.focus();" class="badge badge-light shadow-sm float-right" title="Trích dẫn" style="color: var(--accent);"><i class="fa fa-quote-left"></i> Quote</a>
                    <?php endif; ?>
                </div>
                <div class="dw-comment-body" id="content">
                    <?= $CommentDetail['comment'] ?>
                </div>
            </div>
        </div>
        <?php endforeach ?>
    </div>
    
    <?php if (isset($CommentListPaging)) echo $CommentListPaging; ?>
<?php endif ?>

<!-- Reply Box -->
<div class="dw-card mt-3">
    <div class="dw-card-header"><h3 class="dw-card-title"><i class="fa fa-reply"></i> Viết bình luận</h3></div>
    <div class="dw-card-body">
        <?php if ($isLogin): ?>
            <?php if (isset($isPersonCanComment) && $isPersonCanComment): ?>
                <?php if (!empty($errorComment)): ?>
                    <div class="dw-alert dw-alert-danger"><?= _e($errorComment) ?></div>
                <?php endif ?>

                <form id="form" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <div class="form-group mb-2">
                        <textarea id="postText" name="content" rows="3" class="form-control border-0 bg-light" placeholder="Nội dung bình luận..." style="resize: none; border-radius: 15px; padding: 15px;" required></textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-none d-md-block">
                            <?php include(dirname(dirname(__FILE__)) . '/toolbar.php') ?>
                        </div>
                        <div class="d-md-none">
                            <button type="button" class="btn btn-light rounded-circle" onclick="var tb=document.getElementById('commentToolbar'); tb.style.display=tb.style.display==='none'?'block':'none';"><i class="fa fa-plus text-muted"></i></button>
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 font-weight-bold shadow-sm" style="background: var(--accent-gradient); border: none;">
                            <i class="fa fa-paper-plane mr-1"></i> Gửi bình luận
                        </button>
                    </div>
                    <div id="commentToolbar" style="display:none;" class="mt-2">
                        <?php include(dirname(dirname(__FILE__)) . '/toolbar.php') ?>
                    </div>
                </form>
            <?php else: ?>
                <div class="dw-empty-state">
                    <i class="fa fa-lock"></i>
                    <p><?= $ReasonCanNotComment ?? 'Bạn không có quyền bình luận ở bài viết này.' ?></p>
                </div>
            <?php endif ?>
        <?php else: ?>
            <div class="dw-empty-state">
                <i class="fa fa-sign-in"></i>
                <p>Vui lòng <a href="<?= url('/login') ?>">đăng nhập</a> để tham gia bình luận.</p>
            </div>
        <?php endif ?>
    </div>
</div>