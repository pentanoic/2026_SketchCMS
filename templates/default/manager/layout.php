<?= generateCSRFToken() ?>
<!DOCTYPE html>
<html lang="vi-VN" <?php if (isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled') echo ' class="dark-mode"'; ?>>

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />

    <title><?= (isset($page_title) && $page_title !== _e(config('system.app.name')) ? _e($page_title) . ' - ' : '') . _e(config('system.app.name')) ?></title>

    <link rel="shortcut icon" href="<?= url('/templates' . get_template() . '/public/phpSketch.ico') ?>" />
    <meta name="description" content="<?= isset($page_description) ? _e($page_description) : _e(config('system.app.description')) ?>" />
    <meta name="keywords" content="<?= isset($page_keyword)     ? _e($page_keyword)     : _e(config('system.app.keyword')) ?>" />
    <meta name="robots" content="index,follow" />
    <link rel="canonical" href="<?= url($_SERVER['REQUEST_URI']) ?>" />

    <!-- Open Graph -->
    <meta property="og:title" content="<?= isset($page_title) ? _e($page_title) : _e(config('system.app.name')) ?>" />
    <meta property="og:description" content="<?= isset($page_description) ? _e($page_description) : _e(config('system.app.description')) ?>" />
    <meta property="og:url" content="<?= url($_SERVER['REQUEST_URI']) ?>" />
    <meta property="og:type" content="website" />

    <!-- Bootstrap 4.5.3 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.5.3/css/bootstrap.min.css"
        integrity="sha512-oc9+XSs1H243/FRN9Rw62Fn8EtxjEYWHXRvjS43YtueEewbS6ObfXcJNyohjHqVKFPoXXUxwc+q1K7Dee6vv9g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Font Awesome 4.7 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"
        integrity="sha512-SfTiTlX6kk+qitfevl/7LibUOeJWlt9rbyDn92a1DqWOw9vWG2MFoays0sgObmWazO5BQPiFucnnEAjpAB+/Sw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Inter + Be Vietnam Pro -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <!-- Theme -->
    <link rel="stylesheet" href="<?= $this->asset('/templates' . get_template() . '/public/css/styles.css') ?>" />

    <!-- Waifu Background -->
    <style>
        body {
            <?php if (isset($isLoginHaveWaifu) && $isLoginHaveWaifu && isset($user_waifu)) : ?>background-image: url('<?= $user_waifu['rleft'] == '/images/rleft.png' ? url('/templates/default/public/images/rleft.png') : _e($user_waifu['rleft']) ?>'),
                url('<?= $user_waifu['rright'] == '/images/rright.png' ? url('/templates/default/public/images/rright.png') : _e($user_waifu['rright']) ?>');
            <?php else: ?>background-image: url('<?= url('/templates/default/public/images/rleft.png') ?>'),
                url('<?= url('/templates/default/public/images/rright.png') ?>');
            <?php endif ?>background-position: left bottom, right bottom;
            background-repeat: no-repeat;
            background-attachment: fixed;
            /* Thu nhỏ kích thước waifu xuống mức mini gọn gàng */
            background-size: 180px auto, 180px auto;
        }
    </style>

    <!-- Structured data -->
    <?php if (strpos($_SERVER['REQUEST_URI'], '/404') === false): ?>
        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "WebSite",
                "name": "<?= config('system.app.name') ?>",
                "url": "<?= url('/') ?>"
                <?php if (strpos($_SERVER['REQUEST_URI'], '/articles/') !== false): ?>,
                    "potentialAction": {
                        "@type": "SearchAction",
                        "target": "<?= url('/search?q=') ?>{search_term_string}",
                        "query-input": "required name=search_term_string"
                    }
                <?php endif ?>
            }
        </script>
    <?php endif ?>

    <!-- jQuery 3.7.1 (before Bootstrap JS) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
        integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- Popper.js 1.16.1 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.1/umd/popper.min.js" integrity="sha512-ubuT8Z88WxezgSqf3RLuNi5lmjstiJcyezx34yIU2gAHonIi27Na7atqzUZCOoY4CExaoFumzOsFQ2Ch+I/HCw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- Bootstrap 4.5.3 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.5.3/js/bootstrap.min.js"
        integrity="sha512-8qmis31OQi6hIRgvkht0s6mCOittjMa9GMqtK9hes5iEQBQE/Ca6yGE5FsW36vyipGoWQswBj/QBm2JR086Rkw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>

<body>

    <!-- ═══════════════════════════════════════
     TOPBAR
════════════════════════════════════════ -->
    <nav id="dw-topbar">

        <!-- Logo -->
        <a href="<?= url('/') ?>" class="dw-logo" title="Trang chủ">
            <img src="<?= url('/templates' . get_template() . '/public/logo.jpg') ?>" style="height:25px;width:25px">
            <?= config('system.app.name') ?>
        </a>

        <!-- Search (desktop) -->
        <div class="dw-search-wrap d-none d-md-block">
            <form action="<?= url('/search') ?>" method="get">
                <input type="search" name="q" class="form-control"
                    placeholder="&#xf002; Tìm kiếm bài viết..."
                    value="<?= isset($_GET['q']) ? _e($_GET['q']) : '' ?>"
                    autocomplete="off" />
            </form>
        </div>

        <!-- Topbar action buttons -->
        <div class="dw-topbar-actions">

            <?php if ($isLogin): ?>

                <!-- Search toggle (mobile) -->
                <button class="dw-icon-btn d-md-none" id="dw-search-toggle" title="Tìm kiếm" type="button">
                    <i class="fa fa-search"></i>
                </button>

                <!-- Mail notifications -->
                <a href="<?= url('/mail') ?>" class="dw-icon-btn" title="Tin nhắn">
                    <i class="fa fa-envelope-o"></i>
                    <?php if ($new_mail_count > 0): ?>
                        <span class="dw-badge-num"><?= (int) $new_mail_count > 9 ? '9+' : (int) $new_mail_count ?></span>
                    <?php endif ?>
                </a>

                <!-- System notifications -->
                <a href="<?= url('/mail/system') ?>" class="dw-icon-btn" title="Thông báo">
                    <i class="fa fa-bell-o"></i>
                    <?php if ($system_notify_count > 0): ?>
                        <span class="dw-badge-dot"></span>
                    <?php endif ?>
                </a>

                <!-- Dark mode toggle -->
                <button class="dw-icon-btn" id="dw-darkmode-btn" title="Chế độ tối" type="button">
                    <i class="fa fa-moon-o" id="dw-darkmode-icon"></i>
                </button>

                <!-- Avatar / user dropdown (Desktop only) -->
                <div class="dropdown d-none d-md-block">
                    <button class="dw-icon-btn" data-toggle="dropdown" type="button" style="border-radius:50%;overflow:hidden;padding:0;width:34px;height:34px;">
                        <img class="dw-avt lozad" style="width:34px;height:34px;border-radius:50%;"
                            data-src="<?= getAvtUser($user) ?>"
                            src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="
                            alt="Avatar" />
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" style="min-width:200px;border-color:var(--border);">
                        <div class="px-3 py-2" style="border-bottom:1px solid var(--border);">
                            <div style="font-weight:700;font-size:13px;color:var(--fg);"><?= $user['name'] ?: $user['nick'] ?></div>
                            <div style="font-size:11px;color:var(--fg-muted);">@<?= $user['nick'] ?></div>
                        </div>
                        <a class="dropdown-item" href="<?= url('/user/' . _e($user['nick'])) ?>">
                            <i class="fa fa-user-circle-o mr-2"></i>Trang cá nhân
                        </a>
                        <a class="dropdown-item" href="<?= url('/user/' . _e($user['nick']) . '/info') ?>">
                            <i class="fa fa-pencil mr-2"></i>Chỉnh sửa hồ sơ
                        </a>
                        <?php if ($user['level'] >= 120): ?>
                            <a class="dropdown-item" href="<?= url('/manager') ?>">
                                <i class="fa fa-cog mr-2"></i>Quản trị
                            </a>
                        <?php endif ?>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="<?= url('/logout') ?>">
                            <i class="fa fa-sign-out mr-2"></i>Đăng xuất
                        </a>
                    </div>
                </div>

                <!-- Mobile Menu Button -->
                <button class="dw-icon-btn d-md-none" id="mobile-menu-btn" title="Menu" type="button" onclick="document.body.classList.add('show-mobile-sidebar');">
                    <i class="fa fa-bars"></i>
                </button>


            <?php else: ?>

                <!-- Guest actions -->
                <a href="<?= url('/login') ?>" class="dw-btn dw-btn-ghost dw-btn-sm">
                    <i class="fa fa-sign-in"></i> Đăng nhập
                </a>
                <a href="<?= url('/register') ?>" class="dw-btn dw-btn-primary dw-btn-sm">
                    <i class="fa fa-user-plus"></i> Đăng ký
                </a>

                <!-- Dark mode -->
                <button class="dw-icon-btn" id="dw-darkmode-btn" title="Chế độ tối" type="button">
                    <i class="fa fa-moon-o" id="dw-darkmode-icon"></i>
                </button>

            <?php endif ?>
        </div>
    </nav>

    <!-- Mobile search bar (hidden by default) -->
    <div id="dw-mobile-search" style="display:none;background:var(--surface);border-bottom:1px solid var(--border);padding:8px 12px;">
        <form action="<?= url('/search') ?>" method="get">
            <input type="search" name="q" class="form-control form-control-sm"
                placeholder="Tìm kiếm..."
                value="<?= isset($_GET['q']) ? _e($_GET['q']) : '' ?>" />
        </form>
    </div>

    <!-- ═══════════════════════════════════════
     BODY GRID
════════════════════════════════════════ -->
    <div id="dw-body">

        <!-- ── LEFT SIDEBAR ── -->
        <aside id="dw-sidebar">
            <?php if ($isLogin): ?>
                <div class="dw-sidebar-user">
                    <img class="dw-sidebar-avatar lozad"
                        data-src="<?= getAvtUser($user) ?>"
                        src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=="
                        alt="Avatar" />
                    <div style="min-width:0;">
                        <div class="dw-sidebar-user-name"><?= $user['name'] ?: $user['nick'] ?></div>
                        <div class="dw-sidebar-user-role"><?= RoleColor($user, 'position') ?></div>
                    </div>
                </div>
            <?php endif ?>

            <a href="<?= url('/manager') ?>" class="dw-nav-link<?= (!isset($panelActive) || $panelActive === 'dashboard') ? ' active' : '' ?>">
                <span class="dw-nav-icon"><i class="fa fa-th-large"></i></span>
                Bảng điều khiển
            </a>

            <?php if (module_menu_check('user')): ?>
                <a href="<?= url('/manager/users') ?>" class="dw-nav-link<?= (isset($panelActive) && $panelActive === 'users') ? ' active' : '' ?>">
                    <span class="dw-nav-icon"><i class="fa fa-users"></i></span>
                    Thành viên
                </a>
            <?php endif; ?>

            <?php if (module_menu_check('articles')): ?>
                <a href="<?= url('/manager/articles') ?>" class="dw-nav-link<?= (isset($panelActive) && $panelActive === 'articles') ? ' active' : '' ?>">
                    <span class="dw-nav-icon"><i class="fa fa-comments"></i></span>
                    Diễn đàn
                </a>
            <?php endif; ?>

            <?php if (module_menu_check('shoutbox')): ?>
                <a href="<?= url('/manager/shoutbox') ?>" class="dw-nav-link<?= (isset($panelActive) && $panelActive === 'shoutbox') ? ' active' : '' ?>">
                    <span class="dw-nav-icon"><i class="fa fa-commenting"></i></span>
                    Phòng chat
                </a>
            <?php endif; ?>

            <a href="<?= url('/manager/static_pages') ?>" class="dw-nav-link<?= (isset($panelActive) && $panelActive === 'static_pages') ? ' active' : '' ?>">
                <span class="dw-nav-icon"><i class="fa fa-html5"></i></span>
                Trang tĩnh
            </a>

            <a href="<?= url('/manager/templates') ?>" class="dw-nav-link<?= (isset($panelActive) && $panelActive === 'templates') ? ' active' : '' ?>">
                <span class="dw-nav-icon"><i class="fa fa-paint-brush"></i></span>
                Giao diện
            </a>

            <a href="<?= url('/manager/modules') ?>" class="dw-nav-link<?= (isset($panelActive) && $panelActive === 'modules') ? ' active' : '' ?>">
                <span class="dw-nav-icon"><i class="fa fa-cubes"></i></span>
                Quản lý Module
            </a>

            <?php if (module_menu_check('media')): ?>
                <a href="<?= url('/manager/media') ?>" class="dw-nav-link<?= (isset($panelActive) && $panelActive === 'media') ? ' active' : '' ?>">
                    <span class="dw-nav-icon"><i class="fa fa-file-image-o"></i></span>
                    Tập tin
                </a>
            <?php endif; ?>

            <a href="<?= url('/manager/url_rules') ?>" class="dw-nav-link<?= (isset($panelActive) && $panelActive === 'url_rules') ? ' active' : '' ?>">
                <span class="dw-nav-icon"><i class="fa fa-link"></i></span>
                Rewrite/Disable URL
            </a>

            <a href="<?= url('/manager/settings') ?>" class="dw-nav-link<?= (isset($panelActive) && $panelActive === 'settings') ? ' active' : '' ?>">
                <span class="dw-nav-icon"><i class="fa fa-cog"></i></span>
                Cài đặt SEO
            </a>
            <a href="<?= url('/manager/smtp') ?>" class="dw-nav-link<?= (isset($panelActive) && $panelActive === 'smtp') ? ' active' : '' ?>">
                <span class="dw-nav-icon"><i class="fa fa-envelope"></i></span>
                Cấu hình SMTP
            </a>

            <a href="<?= url('/manager/security') ?>" class="dw-nav-link<?= (isset($panelActive) && $panelActive === 'security') ? ' active' : '' ?>">
                <span class="dw-nav-icon"><i class="fa fa-shield"></i></span>
                Vận hành an toàn
            </a>

            <!-- Mobile Sidebar Close Button -->
            <button id="close-mobile-sidebar" class="dw-btn dw-btn-outline w-100 mt-4 d-md-none" style="border-radius: 8px;" onclick="document.body.classList.remove('show-mobile-sidebar');">
                <i class="fa fa-arrow-left"></i> Đóng menu
            </button>
            <div style="height: 80px;" class="d-md-none"></div> <!-- padding bottom for mobile -->

            <div class="dw-nav-section" style="margin-top:12px;">Hệ thống</div>

            <a href="<?= url('/') ?>" class="dw-nav-link">
                <span class="dw-nav-icon"><i class="fa fa-home"></i></span>
                Về Trang chủ
            </a>
        </aside>
        <!-- /#dw-sidebar -->

        <!-- ── MAIN CONTENT ── -->
        <main id="dw-main">
            <?= $this->section('content') ?>
        </main>

    </div><!-- /#dw-body -->

    <!-- ═══════════════════════════════════════
     FOOTER
════════════════════════════════════════ -->
    <footer id="dw-footer">
        &copy; <?= date('Y') ?> &nbsp;<strong><?= config('system.app.name') ?></strong>
        &nbsp;·&nbsp; Powered by SketchCMS
    </footer>

    <!-- ── Mobile Bottom Nav ── -->
    <div class="bottom-nav">
        <a href="<?= url('/') ?>" class="<?= ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/articles') ? 'active' : '' ?>"><i class="fa fa-home"></i>Trang chủ</a>
        <a href="<?= url('/search') ?>" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/search') ? 'active' : '' ?>"><i class="fa fa-search"></i>Tìm kiếm</a>
        <?php if (module_menu_check('shoutbox')): ?>
            <a href="<?= url('/shoutbox') ?>" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/shoutbox') ? 'active' : '' ?>"><i class="fa fa-comments-o" style="color:var(--accent); font-size: 1.8rem; margin-top: -8px;"></i>Trò chuyện</a>
        <?php endif; ?>
        <?php if ($isLogin): ?>
            <a href="<?= url('/mail') ?>" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/mail') ? 'active' : '' ?>"><i class="fa fa-envelope-o"></i>Tin nhắn</a>
            <a href="<?= url('/user/' . _e($user['nick'])) ?>" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/user/' . $user['nick']) ? 'active' : '' ?>">
                <img src="<?= getAvtUser($user) ?>" class="rounded-circle mb-1" width="24" height="24" style="object-fit: cover;">Cá nhân
            </a>
        <?php else: ?>
            <a href="<?= url('/login') ?>"><i class="fa fa-sign-in"></i>Đăng nhập</a>
        <?php endif ?>
    </div>

    <!-- Toast container -->
    <div id="dw-toast-container"></div>

    <!-- Highlight.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css"
        id="hljs-theme-light" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css"
        id="hljs-theme-dark" disabled />

    <!-- Lozad (lazy load) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lozad.js/1.16.0/lozad.min.js"></script>

    <?php
    $telegramUploadMode = defined('TELEGRAM_UPLOAD_MODE') ? TELEGRAM_UPLOAD_MODE : 'cloudflare';
    $cloudflareWorkerUrl = defined('CLOUDFLARE_WORKER_URL') ? CLOUDFLARE_WORKER_URL : 'https://nosineup.stockage.workers.dev';
    $telegramUploadUrl = ($telegramUploadMode === 'direct') ? url('/media/upload') : $cloudflareWorkerUrl . '/upload';
    $telegramDownloadBaseUrl = ($telegramUploadMode === 'direct') ? url('/media/telegram_download') : $cloudflareWorkerUrl . '/download';
    ?>
    <script>
        var TelegramUploadMode = '<?= $telegramUploadMode ?>';
        var TelegramUploadUrl = '<?= $telegramUploadUrl ?>';
        var TelegramDownloadBaseUrl = '<?= $telegramDownloadBaseUrl ?>';
    </script>

    <!-- Admin JS -->
    <script src="<?= $this->asset('/templates' . get_template() . '/public/js/app.js') ?>"></script>

    <!-- Mobile Sidebar Elements -->
    <div class="mobile-sidebar-overlay" onclick="document.body.classList.remove('show-mobile-sidebar');"></div>



</body>

</html>