/* ================================================================
   App JS
   Stack: jQuery 3.7.1 · Lozad (lazy) · Vanilla JS effects
   NO Node.js, NO build tools require
   ================================================================ */

(function ($) {
    'use strict';

    /* ── 1. Lazy Load ─────────────────────────────────────── */
    var observer = lozad('.lozad', {
        rootMargin: '200px 0px',
        threshold: 0,
        loaded: function (el) {
            el.classList.add('loaded');
        }
    });
    observer.observe();

    /* ── 2. Dark Mode ─────────────────────────────────────── */
    var isDark = document.cookie.indexOf('darkMode=enabled') !== -1;

    function applyDarkMode(enable) {
        if (enable) {
            document.body.classList.add('dark-mode');
            $('#dw-darkmode-icon').removeClass('fa-moon-o').addClass('fa-sun-o');
            $('#hljs-theme-light').prop('disabled', true);
            $('#hljs-theme-dark').prop('disabled', false);
            document.cookie = 'darkMode=enabled; path=/; max-age=31536000; SameSite=Lax';
        } else {
            document.body.classList.remove('dark-mode');
            $('#dw-darkmode-icon').removeClass('fa-sun-o').addClass('fa-moon-o');
            $('#hljs-theme-light').prop('disabled', false);
            $('#hljs-theme-dark').prop('disabled', true);
            document.cookie = 'darkMode=disabled; path=/; max-age=31536000; SameSite=Lax';
        }
    }

    // Init icon state
    applyDarkMode(isDark);

    $('#dw-darkmode-btn').on('click', function () {
        isDark = !isDark;
        applyDarkMode(isDark);
    });

    /* ── 3. Mobile Search Toggle ──────────────────────────── */
    $('#dw-search-toggle').on('click', function () {
        var $ms = $('#dw-mobile-search');
        $ms.slideToggle(200, function () {
            if ($ms.is(':visible')) {
                $ms.find('input').focus();
            }
        });
    });

    /* ── 4. Fade-in on Scroll ─────────────────────────────── */
    var fadeObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                fadeObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.06 });

    document.querySelectorAll('.dw-fade-in').forEach(function (el) {
        fadeObserver.observe(el);
    });

    /* ── 5. Toast Notifications ───────────────────────────── */
    window.dwToast = function (message, type, duration) {
        type = type || 'info';
        duration = duration || 3500;
        var icons = { success: 'check-circle', danger: 'times-circle', info: 'info-circle', warning: 'exclamation-triangle' };
        var icon = icons[type] || 'info-circle';
        var $toast = $('<div class="dw-toast ' + type + '">' +
            '<i class="fa fa-' + icon + '" style="flex-shrink:0;margin-top:1px;"></i>' +
            '<span>' + message + '</span>' +
            '</div>');
        $('#dw-toast-container').append($toast);
        setTimeout(function () {
            $toast.css({ opacity: 0, transform: 'translateX(20px)', transition: 'opacity .25s, transform .25s' });
            setTimeout(function () { $toast.remove(); }, 300);
        }, duration);
    };

    /* ── 6. Form re-submission guard ─────────────────────── */
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    /* ── 7. Post list filter dropdowns ───────────────────── */
    $('#order_by').on('change', function () {
        var url = new URL(window.location.href);
        url.searchParams.set('order_by', this.value);
        window.location.href = url.toString();
    });
    $('#sort').on('change', function () {
        var url = new URL(window.location.href);
        url.searchParams.set('sort', this.value);
        window.location.href = url.toString();
    });

    /* ── 8. Post tabs (file-list / chapter / share) ───────── */
    $(document).on('click', '[data-dw-tab]', function (e) {
        e.preventDefault();
        var target = $(this).data('dw-tab');
        var $group = $(this).closest('[data-dw-tab-group]');
        $group.find('[data-dw-tab-panel]').hide();
        $group.find('[data-dw-tab]').removeClass('dw-tab-active');
        $group.find('[data-dw-tab-panel="' + target + '"]').fadeIn(180);
        $(this).addClass('dw-tab-active');
    });

    /* ── 9. Like button AJAX ──────────────────────────────── */
    $(document).on('click', '.dw-like-btn', function (e) {
        e.preventDefault();
        var $btn = $(this);
        if ($btn.data('loading')) return;
        $btn.data('loading', true);
        $.get(window.location.pathname + '?mod=like', function (res) {
            $btn.data('loading', false);
            dwToast('Đã thích bài viết!', 'success');
            $btn.addClass('dw-like-btn--liked').prop('disabled', true);
        }).fail(function () {
            $btn.data('loading', false);
            dwToast('Có lỗi xảy ra, thử lại sau.', 'danger');
        });
    });

    /* ── 10. Confirm dangerous actions ───────────────────── */
    $(document).on('click', '[data-confirm]', function (e) {
        var msg = $(this).data('confirm') || 'Bạn chắc chắn muốn thực hiện thao tác này?';
        if (!confirm(msg)) {
            e.preventDefault();
            return false;
        }
    });

    /* ── 11. Toolbar (BBCode) helpers ─────────────────────── */
    window.dwTag = function (open, close, textarea_id) {
        textarea_id = textarea_id || 'postText';
        var el = document.getElementById(textarea_id);
        if (!el) return;
        var start = el.selectionStart;
        var end = el.selectionEnd;
        var sel = el.value.substring(start, end);
        var replacement = open + sel + (close || '');
        el.value = el.value.substring(0, start) + replacement + el.value.substring(end);
        el.selectionStart = el.selectionEnd = start + replacement.length;
        el.focus();
    };

    window.dwToggleDropdown = function (id) {
        var $el = $('#' + id);
        $el.toggleClass('open');
    };

    /* ── 12. Captcha DoomCaptcha checkbox auto-check ──────── */
    // The doomcaptcha.js handles the captcha widget;
    // we just ensure the hidden checkbox gets ticked after completion.
    $(document).on('doomcaptcha:done', function () {
        $('#dai-check').prop('checked', true);
    });

    /* ── 13. Smooth scroll to anchor ─────────────────────── */
    $(document).on('click', 'a[href^="#"]:not([href="#"])', function (e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: target.offset().top - 70 }, 300);
        }
    });

    /* ── 14. Image lightbox (simple) ─────────────────────── */
    $(document).on('click', '.dw-post-body img:not(.dw-avt):not(.dw-sidebar-avatar)', function () {
        var src = $(this).attr('src');
        if (!src || src.indexOf('data:') === 0) return;
        var $overlay = $('<div id="dw-lightbox" style="position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;display:flex;align-items:center;justify-content:center;cursor:zoom-out;">' +
            '<img src="' + src + '" style="max-width:94vw;max-height:90vh;border-radius:6px;box-shadow:0 8px 40px rgba(0,0,0,.6);">' +
            '</div>');
        $('body').append($overlay);
        $overlay.on('click', function () { $(this).remove(); });
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') $('#dw-lightbox').remove();
    });

})(jQuery);