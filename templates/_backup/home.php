<?php $this->layout('layout'); ?>

<?php if (!$isLogin): ?>
    <!-- LOGIN PANEL -->
    <div class="dw-card mb-3">
        <div class="dw-card-header">
            <h3 class="dw-card-title"><i class="fa fa-lock"></i> Đăng nhập hệ thống</h3>
        </div>
        <div class="dw-card-body">
            <form action="<?= url('/login') ?>" method="post" autocomplete="off">
                <div class="form-group">
                    <label>Tên tài khoản</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-user-circle-o"></i></span>
                        </div>
                        <input class="form-control" type="text" name="account"
                            pattern="^[a-zA-Z0-9]{3,15}$"
                            placeholder="Nhập ID người dùng" autocomplete="username">
                    </div>
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-key"></i></span>
                        </div>
                        <input class="form-control" type="password" name="password"
                            pattern="^.{3,32}$"
                            placeholder="••••••••••••" autocomplete="current-password">
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="rememberMe" name="remember" value="1" checked="checked">
                        <label class="custom-control-label" for="rememberMe">Ghi nhớ</label>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <input style="display:none" type="checkbox" name="doomcaptcha"
                        value="<?= substr(sha1(getCSRFToken()), 5, 6) ?>" checked="checked" />
                    <button type="submit" class="btn btn-primary" style="background:var(--accent-gradient);border:none;">
                        <i class="fa fa-sign-in mr-2"></i>Đăng nhập
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif ?>


<!-- TAB NAVIGATION -->
<style>
    div[role="category-list"],
    div[role="stats"] {
        display: none;
    }

    .dw-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 12px;
        overflow-x: auto;
        padding-bottom: 4px;
    }

    .dw-tab-btn {
        background: var(--surface-muted);
        border: 1px solid var(--border);
        color: var(--fg-secondary);
        padding: 6px 12px;
        border-radius: var(--radius-sm);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
        white-space: nowrap;
    }

    .dw-tab-btn:hover {
        background: var(--border-muted);
        color: var(--fg);
    }

    .dw-tab-btn.active {
        background: var(--accent-soft);
        color: var(--accent);
        border-color: var(--accent-soft-bd);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = {
            'post-list-btn': 'post-list',
            'category-list-btn': 'category-list',
            'stats-btn': 'stats'
        };

        function showContent(role) {
            ['post-list', 'category-list', 'stats'].forEach(r => document.querySelector('div[role="' + r + '"]').style.display = 'none');
            document.querySelector('div[role="' + role + '"]').style.display = 'block';
        }
        Object.keys(buttons).forEach(function(buttonId) {
            document.getElementById(buttonId).onclick = function() {
                showContent(buttons[buttonId]);
                document.querySelectorAll('.dw-tab-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
            };
        });
    });
</script>

<div class="dw-tabs">
    <button type="button" class="dw-tab-btn active" id="post-list-btn"><i class="fa fa-th-list mr-1"></i> Chủ đề</button>
    <button type="button" class="dw-tab-btn" id="category-list-btn"><i class="fa fa-sitemap mr-1"></i> Chuyên mục</button>
    <button type="button" class="dw-tab-btn" id="stats-btn"><i class="fa fa-bar-chart mr-1"></i> Thống kê</button>
</div>


<?php if (!$isInCategory && !empty($PostListSticked)): ?>
    <!-- PINNED POSTS -->
    <div class="dw-card">
        <div class="dw-card-header">
            <h3 class="dw-card-title"><i class="fa fa-thumb-tack" style="color:var(--warning)"></i> Chủ đề nổi bật</h3>
        </div>
        <div class="dw-card-body p-0">
            <?php foreach ($PostListSticked as $PostItem): ?>
                <div class="dw-post-row dw-post-row-new" style="background:var(--warning-soft);">
                    <div class="dw-post-icon">
                        <div class="dw-post-topic-icon" style="color:var(--warning);border-color:var(--warning);"><i class="fa fa-thumb-tack"></i></div>
                        <div style="min-width:0;">
                            <div class="dw-post-title">
                                <a href="<?= url('/articles/' . $PostItem['id'] . '-' . $PostItem['slug']) ?>.html">
                                    <?= ($PostItem['blocked'] == 1 ? '<i class="fa fa-lock text-danger mr-1"></i>' : '') ?>
                                    <?= $PostItem['title'] ?>
                                </a>
                            </div>
                            <div class="dw-post-meta">
                                Bởi <?= RoleColor($PostItem['UserDetail_LastCommentAuthor'] ?? $PostItem['UserDetail_Author']) ?>
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
        </div>
    </div>
<?php endif ?>


<!-- POST LIST -->
<div class="dw-card" role="post-list">
    <div class="dw-card-header">
        <h3 class="dw-card-title">
            <?php if ($isLogin): ?>
                <?php if ($NewsBool): ?>
                    <i class="fa fa-newspaper-o"></i> Tin tức hệ thống
                <?php else: ?>
                    <i class="fa fa-comments"></i> <?= $isInCategory ? strtoupper($CategoryName) : 'Thảo luận gần đây' ?>
                <?php endif ?>
            <?php else: ?>
                <i class="fa fa-list-ul"></i> <?= $isInCategory ? strtoupper($CategoryName) : 'Danh sách chủ đề' ?>
            <?php endif ?>
        </h3>
        <?php if ($CanPostPublish): ?>
            <a href="<?= url('/articles/' . $CategorySlug . '/post') ?>" class="btn btn-sm btn-primary rounded-pill px-3" style="background:var(--accent-gradient);border:none;">
                <i class="fa fa-pencil"></i> Đăng bài
            </a>
        <?php endif ?>
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
                        <img src="<?= getAvtUser($PostItem['UserDetail']) ?>" class="lozad" width="38" height="38" style="object-fit: cover; border-radius: 50%; border: 1px solid var(--border);" alt="Avatar">
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
                                <span class="mr-2"><i class="fa fa-cube"></i> <?= $PostItem['category_name'] ?></span>
                                <span class="mr-2"><i class="fa fa-clock-o"></i> <?= ago($PostItem['time']) ?></span>
                                <span><i class="fa fa-user"></i> <?= RoleColor($PostItem['UserDetail']) ?></span>
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
                <i class="fa fa-database fa-3x mb-2" style="color:var(--border-strong)"></i>
                <p>Chưa có chủ đề nào.</p>
            </div>
        <?php endif ?>
    </div>
    <?php if (isset($PostListPaging)): ?>
        <div class="dw-card-footer d-flex justify-content-center">
            <?= $PostListPaging ?>
        </div>
    <?php endif ?>
</div>


<!-- CATEGORY LIST -->
<div class="dw-card" role="category-list">
    <div class="dw-card-header">
        <h3 class="dw-card-title"><i class="fa fa-sitemap" style="color:var(--info)"></i> Chuyên mục</h3>
    </div>
    <div class="dw-card-body p-0">
        <?php foreach ($CategoryList as $CategoryItem): ?>
            <div class="dw-category-row">
                <div class="dw-category-item">
                    <div class="dw-cat-icon"><i class="fa fa-cube"></i></div>
                    <div>
                        <div class="dw-cat-name">
                            <a href="<?= url('/index.html?category=' . $CategoryItem['id']) ?>"><?= $CategoryItem['name'] ?></a>
                        </div>
                    </div>
                    <div class="dw-cat-stat">
                        <div class="dw-cat-stat-num"><?= $CategoryItem['count_post'] ?></div>
                        <div class="dw-cat-stat-label">Chủ đề</div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
</div>


<!-- STATS -->
<div class="dw-card" role="stats">
    <?php if ($isLogin && $ForumStats['count_online'] > 0): ?>
        <div class="dw-card-header">
            <h3 class="dw-card-title"><i class="fa fa-wifi" style="color:var(--success)"></i> Người dùng trực tuyến (<?= $ForumStats['count_online'] ?>)</h3>
        </div>
        <div class="dw-card-body">
            <div class="d-flex flex-wrap gap-2 mb-3" style="gap:8px;">
                <?php foreach ($UserListOnline as $UserDetail): ?>
                    <a href="/user/<?= $UserDetail['nick'] ?>" title="<?= $UserDetail['name'] ?>">
                        <img src="<?= getAvtUser($UserDetail) ?>" loading="lazy"
                            class="lozad" width="36" height="36" style="border-radius:50%;object-fit:cover;border:1px solid var(--border);"
                            alt="<?= $UserDetail['name'] ?>">
                    </a>
                <?php endforeach ?>
            </div>
            <div class="text-right">
                <a href="/users" class="btn btn-sm btn-light"><i class="fa fa-list"></i> Xem tất cả</a>
            </div>
        </div>
    <?php endif ?>

    <div class="dw-card-header border-top">
        <h3 class="dw-card-title"><i class="fa fa-tachometer" style="color:var(--warning)"></i> Thống kê hệ thống</h3>
    </div>
    <div class="dw-card-body">
        <div class="row text-center mb-3">
            <div class="col-3">
                <div style="font-size:18px;color:var(--fg-muted)"><i class="fa fa-comments"></i></div>
                <div style="font-size:18px;font-weight:700;"><?= $ForumStats['count_post'] ?></div>
                <div style="font-size:11px;color:var(--fg-muted)">CHỦ ĐỀ</div>
            </div>
            <div class="col-3">
                <div style="font-size:18px;color:var(--fg-muted)"><i class="fa fa-pencil"></i></div>
                <div style="font-size:18px;font-weight:700;"><?= $ForumStats['count_comment'] ?></div>
                <div style="font-size:11px;color:var(--fg-muted)">BÀI VIẾT</div>
            </div>
            <div class="col-3">
                <div style="font-size:18px;color:var(--fg-muted)"><i class="fa fa-paperclip"></i></div>
                <div style="font-size:18px;font-weight:700;"><?= $ForumStats['count_file'] ?></div>
                <div style="font-size:11px;color:var(--fg-muted)">TẬP TIN</div>
            </div>
            <div class="col-3">
                <div style="font-size:18px;color:var(--fg-muted)"><i class="fa fa-users"></i></div>
                <div style="font-size:18px;font-weight:700;"><?= $ForumStats['count_user'] ?></div>
                <div style="font-size:11px;color:var(--fg-muted)">THÀNH VIÊN</div>
            </div>
        </div>
        <div class="text-center" style="font-size:13px;background:var(--surface-muted);padding:8px;border-radius:var(--radius-sm);">
            <i class="fa fa-user-plus text-success mr-1"></i> Thành viên mới nhất:
            <a href="/user/<?= $ForumStats['latest_user']['nick'] ?>" style="font-weight:600;">
                <?= $ForumStats['latest_user']['name'] ?>
            </a>
        </div>
    </div>
</div>

<!-- LINKS -->
<div class="dw-card">
    <div class="dw-card-header">
        <h3 class="dw-card-title"><i class="fa fa-share-alt" style="color:var(--info)"></i> Liên kết bạn bè</h3>
    </div>
    <div class="dw-card-body p-3">
        <a href="https://lab302.ovh/" target="_blank" rel="noopener" class="d-inline-flex align-items-center" style="font-weight:500;">
            <i class="fa fa-external-link mr-2 text-info"></i> lab302.ovh
            <span class="text-muted ml-2" style="font-size:12px;font-weight:400;">— blog chia sẻ tiện ích về cad và dựng mô hình</span>
        </a>
    </div>
</div>