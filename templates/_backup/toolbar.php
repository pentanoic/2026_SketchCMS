<?php
$colors = ['bcbcbc', '708090', '6c6c6c', '454545', 'fcc9c9', 'fe8c8c', 'fe5e5e', 'fd5b36', 'f82e00', 'ffe1c6', 'ffc998', 'fcad66', 'ff9331', 'ff810f', 'd8ffe0', '92f9a7', '34ff5d', 'b2fb82', '89f641', 'b7e9ec', '56e5ed', '21cad3', '03939b', '039b80', 'cac8e9', '9690ea', '6a60ec', '4866e7', '173bd3', 'f3cafb', 'e287f4', 'c238dd', 'a476af', 'b53dd2'];
$codes = ['php', 'css', 'js', 'html', 'sql', 'twig', 'lua'];
?>
<div class="bbcode-toolbar mb-2 p-2 rounded" style="background: var(--surface-muted); border: 1px solid var(--border);">
    <div class="d-flex flex-wrap gap-2" style="gap: 5px;">
        <div class="btn-group btn-group-sm shadow-sm">
            <button type="button" class="btn btn-light border-0 bb-btn" data-action="toggle" data-target="colorShow" title="Màu chữ"><i class="fa fa-paint-brush text-muted"></i></button>
            <button type="button" class="btn btn-light border-0 bb-btn" data-tag="[b]" data-end="[/b]" title="In đậm"><i class="fa fa-bold text-muted"></i></button>
            <button type="button" class="btn btn-light border-0 bb-btn" data-tag="[i]" data-end="[/i]" title="In nghiêng"><i class="fa fa-italic text-muted"></i></button>
            <button type="button" class="btn btn-light border-0 bb-btn" data-tag="[u]" data-end="[/u]" title="Gạch chân"><i class="fa fa-underline text-muted"></i></button>
            <button type="button" class="btn btn-light border-0 bb-btn" data-tag="[s]" data-end="[/s]" title="Gạch ngang"><i class="fa fa-strikethrough text-muted"></i></button>
        </div>
        
        <div class="btn-group btn-group-sm shadow-sm">
            <button type="button" class="btn btn-light border-0 bb-btn" data-tag="[center]" data-end="[/center]" title="Căn giữa"><i class="fa fa-align-center text-muted"></i></button>
            <button type="button" class="btn btn-light border-0 bb-btn" data-tag="[right]" data-end="[/right]" title="Căn phải"><i class="fa fa-align-right text-muted"></i></button>
            <button type="button" class="btn btn-light border-0 bb-btn" data-action="toggle" data-target="codeShow" title="Chèn mã code"><i class="fa fa-code text-muted"></i></button>
            <button type="button" class="btn btn-light border-0 bb-btn" data-tag="[url=]" data-end="[/url]" title="Chèn liên kết"><i class="fa fa-link text-muted"></i></button>
            <button type="button" class="btn btn-light border-0 bb-btn" data-tag="[d]" data-end="[/d]" title="Tạo nút tải xuống"><i class="fa fa-download text-muted"></i></button>
        </div>
        
        <div class="btn-group btn-group-sm shadow-sm">
            <button type="button" class="btn btn-light border-0 bb-btn" data-tag="[img]" data-end="[/img]" title="Chèn ảnh bằng link"><i class="fa fa-picture-o text-muted"></i></button>
            <button type="button" class="btn btn-primary font-weight-bold px-3 border-0 bb-upload-btn" title="Tải ảnh lên" style="background: var(--accent-gradient);"><i class="fa fa-cloud-upload"></i></button>
            <button type="button" class="btn btn-light border-0 bb-media-btn" title="Thư viện Media"><i class="fa fa-folder-open text-warning"></i></button>
            <button type="button" class="btn btn-light border-0 bb-btn" data-tag="[vid]" data-end="[/vid]" title="Chèn video"><i class="fa fa-play-circle text-danger"></i></button>
        </div>
    </div>

    <div class="bb-ext codeShow mt-2 pt-2 border-top" style="display:none">
        <div class="d-flex flex-wrap gap-1" style="gap: 5px;">
            <?php foreach ($codes as $c): ?>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-0 bb-btn" data-tag="[code=<?= _e($c) ?>]" data-end="[/code]" data-close="codeShow" style="font-size: 11px; height: 24px; line-height: 24px; padding: 0;"><?= _e($c) ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bb-ext colorShow mt-2 pt-2 border-top" style="display:none">
        <div class="small font-weight-bold text-muted mb-1 text-uppercase">Bảng màu:</div>
        <div class="d-flex flex-wrap" style="gap: 2px;">
            <?php foreach ($colors as $c): ?>
            <button type="button" class="btn p-0 shadow-sm bb-btn" data-tag="[color=#<?= _e($c) ?>]" data-end="[/color]" data-close="colorShow" style="background-color:#<?= _e($c) ?>; width: 20px; height: 20px; border-radius: 4px; border: 1px solid rgba(0,0,0,0.1); display: block;"></button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Media Library Modal (Hidden by default, positioned absolute over toolbar or shown as a modal overlay) -->
    <div class="bb-media-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1050; align-items: center; justify-content: center;">
        <div class="bb-media-content" style="background: var(--surface); width: 90%; max-width: 800px; max-height: 85vh; border-radius: var(--radius-md); display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <div style="padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--surface-muted);">
                <h4 style="margin: 0; font-size: 16px; font-weight: bold;"><i class="fa fa-folder-open text-warning"></i> Thư viện Media</h4>
                <button type="button" class="btn btn-sm btn-light border-0 bb-media-close" style="font-size: 20px; padding: 0 8px;">&times;</button>
            </div>
            <div class="bb-media-grid" style="padding: 15px; overflow-y: auto; flex: 1; display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px;">
                <!-- Items loaded via AJAX -->
                <div class="text-center text-muted" style="grid-column: 1 / -1; padding: 30px;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i><br>Đang tải dữ liệu...
                </div>
            </div>
            <div style="padding: 10px 20px; border-top: 1px solid var(--border); font-size: 12px; color: var(--fg-muted);">
                <i>Bấm vào một tệp để tự động chèn vào khung soạn thảo.</i>
            </div>
        </div>
    </div>

    <input type="file" class="bb-upload-input" style="display:none;" accept="image/*,.txt,.php,.js,.css,.html,.sql,.py,.c,.cpp,.java,.cs">
</div>

<?php
if (!defined('BBCODE_JS_LOADED')) {
    define('BBCODE_JS_LOADED', true);
    echo '<script src="' . url('/templates' . get_template() . '/public/js/bbcode.js?v=' . time()) . '"></script>';
}
?>