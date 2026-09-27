<?php
$this->layout('manager/layout', ['page_title' => $page_title ?? 'Quản lý Tập tin đính kèm', 'panelActive' => 'media']);
?>

<div class="row mb-3">
    <div class="col-md-12">
        <?php if (isset($error) && !empty($error)): ?>
            <div class="alert alert-danger mb-0 mt-2"><?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($success) && !empty($success)): ?>
            <div class="alert alert-success mb-0 mt-2"><?= $success ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h5 class="font-weight-bold text-uppercase mb-0" style="color:var(--heading); font-size:16px;">
        <i class="fa fa-file-image-o" style="color:var(--accent)"></i> <?= _e($page_title) ?>
    </h5>
    <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#telegramConfigModal">
        <i class="fa fa-cog"></i> Cấu hình Upload
    </button>
</div>

<div class="dw-card">
    <div class="dw-card-body p-0 table-responsive">
        <table class="table table-hover table-striped mb-0 text-nowrap">
            <thead class="bg-light">
                <tr>
                    <th class="border-top-0">ID</th>
                    <th class="border-top-0">Tên Tệp</th>
                    <th class="border-top-0">Nền tảng</th>
                    <th class="border-top-0">Kích thước</th>
                    <th class="border-top-0">Bài viết gốc</th>
                    <th class="border-top-0 text-center">Người đăng</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($files)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="fa fa-folder-open-o fa-3x mb-2" style="opacity: 0.5;"></i>
                        <p class="mb-0">Chưa có tập tin nào trên hệ thống.</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($files as $file): ?>
                    <tr>
                        <td class="align-middle">#<?= $file['id'] ?></td>
                        <td class="align-middle fw-bold text-primary">
                            <?php 
                            $display_name = (mb_strlen($file['filename']) > 32) ? mb_substr($file['filename'], 0, 32) . '...' : $file['filename'];
                            ?>
                            <a href="<?= url('/view-file/' . $file['id']) ?>" target="_blank" class="text-decoration-none">
                                <?= _e($display_name) ?>
                            </a>
                        </td>
                        <td class="align-middle">
                            <span class="badge badge-info text-uppercase"><?= _e($file['type']) ?></span>
                        </td>
                        <td class="align-middle">
                            <?= _e(FileSizeFormat($file['filesize'])) ?>
                        </td>
                        <td class="align-middle">
                            <?php if ($file['blogid'] == 0): ?>
                                <a href="<?= url('/') ?>" target="_blank" class="text-decoration-none">
                                    <i class="fa fa-home"></i> CDE
                                </a>
                            <?php elseif (!empty($file['post_slug'])): ?>
                                <a href="<?= url('/articles/' . $file['blogid'] . '-' . $file['post_slug'] . '.html') ?>" target="_blank" class="text-decoration-none">
                                    <i class="fa fa-external-link"></i> Xem bài viết #<?= $file['blogid'] ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted" title="Bài viết gốc không còn tồn tại">
                                    <i class="fa fa-ban"></i> Đã xoá (#<?= $file['blogid'] ?>)
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="align-middle text-center">
                            <?php 
                            $userDetail = [
                                'name' => !empty($file['user_name']) ? $file['user_name'] : $file['author'],
                                'nick' => !empty($file['user_nick']) ? $file['user_nick'] : $file['author'],
                                'level' => $file['user_level'] ?? 0,
                                'reg' => $file['user_reg'] ?? time()
                            ];
                            echo RoleColor($userDetail, 'html');
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="dw-card-footer bg-white border-top">
        <div class="d-flex justify-content-center mt-3">
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($totalPages, $page + 2);
                    $query = ''; // Add URL parameters here if needed (e.g. search, sort)
                    ?>
                    
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= url('/manager/media?page=' . ($page - 1) . $query) ?>" style="background: var(--surface-muted); color: var(--fg); border: 1px solid var(--border); border-radius: 4px;">Trước</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($start_page > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= url('/manager/media?page=1' . $query) ?>" style="background: var(--surface-muted); color: var(--fg); border: 1px solid var(--border); border-radius: 4px;">1</a></li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled"><span class="page-link" style="background: transparent; border: none; color: var(--fg-muted);">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('/manager/media?page=' . $i . $query) ?>" style="<?= ($i == $page) ? 'background: var(--primary); color: #fff; border-color: var(--primary);' : 'background: var(--surface-muted); color: var(--fg); border: 1px solid var(--border);' ?> border-radius: 4px;"><?= _e($i) ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $totalPages): ?>
                        <?php if ($end_page < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link" style="background: transparent; border: none; color: var(--fg-muted);">...</span></li>
                        <?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?= url('/manager/media?page=' . $totalPages . $query) ?>" style="background: var(--surface-muted); color: var(--fg); border: 1px solid var(--border); border-radius: 4px;"><?= _e($totalPages) ?></a></li>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= url('/manager/media?page=' . ($page + 1) . $query) ?>" style="background: var(--surface-muted); color: var(--fg); border: 1px solid var(--border); border-radius: 4px;">Sau</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST" action="<?= url('/manager/media') ?>">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete_file_id" value="0">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title"><i class="fa fa-exclamation-triangle"></i> Xác nhận xóa</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body py-4 text-center">
                <p class="mb-0" style="font-size:16px;">Bạn có chắc chắn muốn xóa tập tin <strong id="delete_file_name" class="text-danger"></strong> không?</p>
                <small class="text-muted d-block mt-2">Hành động này sẽ xóa dữ liệu trên hệ thống và không thể hoàn tác.</small>
            </div>
            <div class="modal-footer border-top-0 bg-light">
                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-danger px-4"><i class="fa fa-trash"></i> Đồng ý Xóa</button>
            </div>
        </form>
    </div>
</div>

<?php
$systemConfigPath = ROOT . 'system/configs/autoload/system.php';
$systemConfig = require $systemConfigPath;
$mediaConfig = $systemConfig['media'] ?? [];
$telegram_upload_mode = $mediaConfig['telegram_upload_mode'] ?? 'cloudflare';
$telegram_bot_token = $mediaConfig['telegram_bot_token'] ?? '';
$telegram_chat_id = $mediaConfig['telegram_chat_id'] ?? '';
$cloudflare_worker_url = $mediaConfig['cloudflare_worker_url'] ?? '';
?>
<!-- Modal Cấu hình Upload Telegram -->
<div class="modal fade" id="telegramConfigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content" id="telegramConfigForm">
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <div class="modal-header">
                <h5 class="modal-title">Cấu hình Upload Telegram</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body py-4">
                <div class="form-group">
                    <label class="font-weight-bold">Chế độ Upload (Mode)</label>
                    <select class="form-control" name="TELEGRAM_UPLOAD_MODE">
                        <option value="direct" <?= $telegram_upload_mode === 'direct' ? 'selected' : '' ?>>Direct (Server làm Proxy)</option>
                        <option value="cloudflare" <?= $telegram_upload_mode === 'cloudflare' ? 'selected' : '' ?>>Cloudflare (Upload trực tiếp qua Worker)</option>
                    </select>
                    <small class="text-muted">Chọn Cloudflare để giảm tải băng thông cho Server.</small>
                </div>
                
                <div class="form-group">
                    <label class="font-weight-bold">Bot Token</label>
                    <input type="text" class="form-control" name="TELEGRAM_BOT_TOKEN" value="<?= _e($telegram_bot_token) ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="font-weight-bold">Chat ID (Nơi lưu trữ)</label>
                    <input type="text" class="form-control" name="TELEGRAM_CHAT_ID" value="<?= _e($telegram_chat_id) ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="font-weight-bold">Cloudflare Worker URL</label>
                    <input type="text" class="form-control" name="CLOUDFLARE_WORKER_URL" value="<?= _e($cloudflare_worker_url) ?>" placeholder="https://...">
                    <small class="text-muted">Chỉ cần thiết nếu dùng chế độ Cloudflare.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary" id="btnSaveConfig"><i class="fa fa-save"></i> Lưu cấu hình</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('delete_file_id').value = id;
    document.getElementById('delete_file_name').innerText = name;
    $('#deleteModal').modal('show');
}

// Xử lý lưu cấu hình Telegram Upload
document.getElementById('telegramConfigForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveConfig');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang lưu...';
    btn.disabled = true;

    const fd = new FormData(this);

    fetch('<?= url("/manager/media/config") ?>', {
        method: 'POST',
        body: fd
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Lưu cấu hình thành công!');
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi mạng hoặc server!');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
});
</script>
