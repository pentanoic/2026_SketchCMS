<?php
$this->layout('manager/layout', ['page_title' => $page_title ?? 'Xem Giao diện', 'panelActive' => 'templates']);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h5 class="font-weight-bold text-uppercase mb-0" style="color:var(--heading); font-size:16px;">
        <a href="<?= url('/manager/templates') ?>" class="text-decoration-none text-muted"><i class="fa fa-arrow-left"></i> Giao diện</a> / <?= _e($template) ?>
    </h5>
</div>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="fa fa-folder-open text-warning me-2"></i> Cây thư mục
            </div>
            <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                <?php if (isset($_GET['f']) && !empty($_GET['f'])): ?>
                    <?php
                    $parent = dirname($_GET['f']);
                    if ($parent === '.' || $parent === '\\') $parent = '';
                    ?>
                    <a href="<?= url('/manager/templates/view?t=' . urlencode($template) . '&f=' . urlencode($parent)) ?>" class="list-group-item list-group-item-action text-primary">
                        <i class="fa fa-level-up me-2"></i> .. (Lên một cấp)
                    </a>
                <?php endif; ?>

                <?php foreach ($items as $item): ?>
                    <a href="<?= url('/manager/templates/view?t=' . urlencode($template) . '&f=' . urlencode($item['path'])) ?>" 
                       class="list-group-item list-group-item-action <?= (isset($_GET['f']) && $_GET['f'] === $item['path']) ? 'active' : '' ?>">
                        <?php if ($item['is_dir']): ?>
                            <i class="fa fa-folder text-warning me-2"></i> 
                        <?php else: ?>
                            <i class="fa fa-file-code-o text-secondary me-2"></i> 
                        <?php endif; ?>
                        <?= $item['name'] ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-md-9 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                <span>
                    <i class="fa fa-file-text-o me-2"></i> 
                    <?= $is_file ? _e($current_file) : 'Chọn một tệp để xem' ?>
                </span>
                <?php if ($is_file && $file_content !== null): ?>
                    <span class="badge bg-secondary"><?= strlen($file_content) ?> bytes</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if ($is_file): ?>
                    <?php if ($file_content !== null): ?>
                        <pre class="m-0 p-3" style="background: #1e1e1e; color: #d4d4d4; max-height: 600px; overflow-y: auto; font-family: monospace; font-size: 13px;"><code><?= _e($file_content) ?></code></pre>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fa fa-ban fa-3x mb-3 text-secondary"></i>
                            <p>Định dạng tệp không được hỗ trợ để xem trước.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fa fa-hand-pointer-o fa-3x mb-3 text-secondary"></i>
                        <p>Vui lòng chọn một tệp văn bản bên cây thư mục để xem nội dung.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
