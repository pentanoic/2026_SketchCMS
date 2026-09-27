<?php
$this->layout('manager/layout', ['page_title' => $page_title ?? 'Quản lý Giao diện', 'panelActive' => 'templates']);
?>

<div class="mb-4">
    <h5 class="font-weight-bold text-uppercase mb-0" style="color:var(--heading); font-size:16px;">
        <i class="fa fa-paint-brush" style="color:var(--accent)"></i> Quản lý Giao diện (Templates)
    </h5>
</div>

<div class="row">
    <?php foreach ($templates as $tpl): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="dw-card h-100" style="<?= ($default_template === $tpl['folder']) ? 'border: 2px solid var(--primary);' : '' ?>">
                <div class="dw-card-body">
                    <h5 class="font-weight-bold" style="color:var(--primary);">
                        <?= $tpl['name'] ?? $tpl['folder'] ?>
                        <?php if ($default_template === $tpl['folder']): ?>
                            <span style="background: var(--primary); color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; vertical-align: top; margin-left: 5px;">Đang Dùng</span>
                        <?php endif; ?>
                    </h5>
                    
                    <p style="color: var(--fg-muted); font-size: 0.85rem; height: 40px; overflow: hidden;">
                        <?= $tpl['description'] ?? 'Không có mô tả.' ?>
                    </p>
                    
                    <ul class="list-unstyled mb-0" style="color: var(--fg); font-size: 0.85rem;">
                        <li class="mb-1"><i class="fa fa-user text-muted mr-1"></i> <strong>Tác giả:</strong> <?= $tpl['author'] ?? 'Ẩn danh' ?></li>
                        <li class="mb-1"><i class="fa fa-tag text-muted mr-1"></i> <strong>Phiên bản:</strong> <?= $tpl['version'] ?? '1.0' ?></li>
                        <li class="mb-1"><i class="fa fa-folder text-muted mr-1"></i> <strong>Thư mục:</strong> <span style="background: var(--surface-muted); padding: 2px 5px; border-radius: 4px; border: 1px solid var(--border);"><?= $tpl['folder'] ?></span></li>
                    </ul>
                </div>
                
                <div class="dw-card-footer" style="background: var(--surface); border-top: 1px solid var(--border); padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 5px;">
                    <div class="d-flex" style="gap: 5px;">
                        <button type="button" class="dw-btn dw-btn-sm" style="background: var(--surface-muted); color: var(--fg); border: 1px solid var(--border);" data-toggle="modal" data-target="#downloadTemplateModal<?= md5($tpl['folder']) ?>" title="Tải về">
                            <i class="fa fa-download"></i>
                        </button>

                        <a href="<?= url('/manager/templates/view?t=' . urlencode($tpl['folder'])) ?>" class="dw-btn dw-btn-sm" style="background: var(--surface-muted); color: var(--info); border: 1px solid var(--border);" title="Xem Code">
                            <i class="fa fa-eye"></i> Code
                        </a>
                    </div>
                    
                    <?php if ($default_template !== $tpl['folder']): ?>
                        <button type="button" class="dw-btn dw-btn-sm" style="background: var(--surface-muted); color: var(--primary); border: 1px solid var(--border);" data-toggle="modal" data-target="#setDefaultTemplateModal<?= md5($tpl['folder']) ?>">
                            <i class="fa fa-check mr-1"></i> Đặt mặc định
                        </button>
                    <?php else: ?>
                        <button class="dw-btn dw-btn-sm dw-btn-primary disabled" disabled style="opacity: 0.7; cursor: not-allowed;">
                            <i class="fa fa-star mr-1"></i> Đang chọn
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Render Modals separately to avoid z-index issues -->
<?php foreach ($templates as $tpl): ?>
    <!-- Modal Download Template -->
    <div class="modal fade text-left" id="downloadTemplateModal<?= md5($tpl['folder']) ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" action="<?= url('/manager/templates/download') ?>" method="post" style="background: var(--surface); color: var(--fg); border-radius: 12px; border: 1px solid var(--border);">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title font-weight-bold text-primary">Tải xuống giao diện: <?= $tpl['folder'] ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:var(--fg);">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="template" value="<?= $tpl['folder'] ?>">
                    <p>Bạn đang chuẩn bị tải xuống giao diện <strong><?= $tpl['folder'] ?></strong>.</p>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Tên phân hệ (Nhập <code>templates</code>)</label>
                        <input type="text" class="form-control" name="module_name" required autocomplete="off" placeholder="templates" style="background:var(--surface-muted); border-radius:8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Mật khẩu Admin của bạn</label>
                        <input type="password" class="form-control" name="admin_pass" required style="background:var(--surface-muted); border-radius:8px;">
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="dw-btn dw-btn-ghost" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="dw-btn dw-btn-primary"><i class="fa fa-download"></i> Xác nhận tải xuống</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Set Default Template -->
    <div class="modal fade text-left" id="setDefaultTemplateModal<?= md5($tpl['folder']) ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" action="<?= url('/manager/templates/set_default') ?>" method="post" style="background: var(--surface); color: var(--fg); border-radius: 12px; border: 1px solid var(--border);">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title font-weight-bold text-success">Đặt làm mặc định: <?= $tpl['folder'] ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:var(--fg);">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="template" value="<?= $tpl['folder'] ?>">
                    <p>Tất cả người dùng sẽ thấy giao diện <strong><?= $tpl['folder'] ?></strong> làm giao diện chính.</p>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Tên phân hệ (Nhập <code>templates</code>)</label>
                        <input type="text" class="form-control" name="module_name" required autocomplete="off" placeholder="templates" style="background:var(--surface-muted); border-radius:8px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Mật khẩu Admin của bạn</label>
                        <input type="password" class="form-control" name="admin_pass" required style="background:var(--surface-muted); border-radius:8px;">
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="dw-btn dw-btn-ghost" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="dw-btn dw-btn-primary" style="background:#28a745; border:none;"><i class="fa fa-check"></i> Xác nhận</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>
