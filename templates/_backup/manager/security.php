<?php
$this->layout('manager/layout', [
    'title' => $page_title ?? 'Vận hành an toàn'
]);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h5 class="font-weight-bold text-uppercase mb-0" style="color:var(--heading); font-size:16px;">
        <i class="fa fa-shield" style="color:var(--accent)"></i> Vận hành an toàn
    </h5>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="dw-card">
            <div class="dw-card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= _e($error) ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?= _e($success) ?></div>
                <?php endif; ?>

                <div class="mb-4">
                    <h6 class="border-bottom pb-2 mb-3" style="border-color: var(--border) !important;">1. Mã hóa tin nhắn (E2E)</h6>
                    <?php if ($unencrypted_count > 0): ?>
                        <div class="alert alert-warning d-flex align-items-center">
                            <i class="fa fa-exclamation-triangle fa-2x me-3 mr-3 text-warning"></i>
                            <div>
                                <strong>Cảnh báo bảo mật:</strong> Hệ thống phát hiện có <b><?= _e($unencrypted_count) ?></b> tin nhắn (Mail & Shoutbox) dưới dạng văn bản thuần chưa được mã hóa. Điều này tạo ra nguy cơ rò rỉ dữ liệu nghiêm trọng nếu cơ sở dữ liệu bị hacker xâm nhập!
                            </div>
                        </div>
                        
                        <form id="e2e-form" action="" method="POST">
                            <input type="hidden" name="csrf_token" id="csrf_token" value="<?= getCSRFToken() ?>">
                            <div class="form-group" style="max-width: 300px;">
                                <label for="admin_pass" class="font-weight-bold">Mật khẩu xác nhận:</label>
                                <input type="password" name="admin_pass" id="admin_pass" class="form-control" placeholder="Nhập mật khẩu của bạn" required>
                            </div>
                            <button type="submit" id="btn-run-e2e" class="dw-btn dw-btn-danger">
                                <i class="fa fa-lock"></i> Tiến hành Mã hóa E2E toàn bộ ngay
                            </button>
                        </form>

                        <!-- Progress Bar (Ẩn mặc định) -->
                        <div id="progress-container" class="mt-4" style="display: none;">
                            <h6 id="progress-text" class="font-weight-bold mb-2">Đang xử lý: 0 / <?= _e($total_unencrypted) ?></h6>
                            <div class="progress" style="height: 20px; border-radius: 10px; background-color: var(--surface-muted);">
                                <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                            <small class="text-muted mt-2 d-block">Vui lòng không đóng tab hoặc tải lại trang trong quá trình này...</small>
                        </div>

                    <?php else: ?>
                        <div class="alert alert-success d-flex align-items-center">
                            <i class="fa fa-shield fa-2x me-3 mr-3 text-success"></i>
                            <div>
                                <strong>Đã an toàn!</strong> 100% dữ liệu tin nhắn (Mail/Shoutbox) của hệ thống đã được mã hóa đầu cuối chuẩn E2E.
                            </div>
                        </div>
                        <button class="dw-btn dw-btn-ghost" disabled>
                            <i class="fa fa-lock"></i> Đã mã hóa E2E hoàn tất
                        </button>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h6 class="border-bottom pb-2 mb-3" style="border-color: var(--border) !important;">2. Quản lý lưu lượng (Rate Limiting)</h6>
                    <form action="" method="POST">
                        <input type="hidden" name="update_security" value="1">
                        <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                        <input type="hidden" name="module_name" value="security">
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Thời gian tối thiểu giữa các thao tác (giây):</label>
                            <input type="number" name="rate_limit" class="form-control" style="max-width: 150px;" min="0" value="<?= $systemConfig['security']['rate_limit'] ?? 1 ?>">
                            <small class="text-muted d-block mt-1">Hệ thống sẽ từ chối các thao tác liên tiếp nếu nhỏ hơn số giây này. Đặt là <b>0</b> để tắt tính năng này.</small>
                        </div>
                        <div class="form-group mb-3" style="max-width: 300px;">
                            <label for="rate_limit_admin_pass" class="font-weight-bold">Mật khẩu xác nhận:</label>
                            <input type="password" name="admin_pass" id="rate_limit_admin_pass" class="form-control" placeholder="Nhập mật khẩu của bạn" required>
                        </div>
                        <button type="submit" class="dw-btn dw-btn-primary">
                            <i class="fa fa-save"></i> Lưu cấu hình
                        </button>
                    </form>
                    
                    <h6 class="border-bottom pb-2 mt-4 mb-3" style="border-color: var(--border) !important;">3. Kiểm tra Cấu trúc Cơ sở Dữ liệu</h6>
                    <?php if (empty($missing_tables) && empty($missing_columns)): ?>
                        <div class="alert alert-success d-flex align-items-center">
                            <i class="fa fa-database fa-2x me-3 mr-3 text-success"></i>
                            <div>
                                <strong>Tuyệt vời!</strong> Cấu trúc cơ sở dữ liệu của bạn hoàn toàn hợp lệ và đồng bộ với mã nguồn gốc.
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <h6 class="font-weight-bold mb-2"><i class="fa fa-exclamation-circle"></i> Phát hiện thiếu sót trong CSDL:</h6>
                            <?php if (!empty($missing_tables)): ?>
                                <p class="mb-1"><strong>Bảng bị thiếu:</strong></p>
                                <ul>
                                    <?php foreach ($missing_tables as $tb): ?>
                                        <li><code><?= _e($tb) ?></code></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            
                            <?php if (!empty($missing_columns)): ?>
                                <p class="mb-1"><strong>Cột bị thiếu:</strong></p>
                                <ul>
                                    <?php foreach ($missing_columns as $tb => $cols): ?>
                                        <li>Bảng <code><?= _e($tb) ?></code> thiếu: 
                                            <?php foreach ($cols as $col): ?>
                                                <span class="badge badge-secondary" style="background:var(--surface-muted); color:var(--text)"><?= _e($col) ?></span>
                                            <?php endforeach; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            
                            <hr style="border-color: rgba(0,0,0,0.1)">
                            <form action="" method="POST" class="mt-3">
                                <input type="hidden" name="fix_db_schema" value="1">
                                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                <input type="hidden" name="module_name" value="security">
                                
                                <div class="form-group mb-3" style="max-width: 300px;">
                                    <label class="font-weight-bold">Mật khẩu xác nhận:</label>
                                    <input type="password" name="admin_pass" class="form-control" placeholder="Nhập mật khẩu" required>
                                </div>
                                <button type="submit" class="dw-btn dw-btn-danger">
                                    <i class="fa fa-wrench"></i> Khắc phục tự động
                                </button>
                                <small class="text-muted d-block mt-2">Hệ thống sẽ thêm các bảng/cột còn thiếu. Dữ liệu hiện tại KHÔNG bị ảnh hưởng.</small>
                            </form>
                        </div>
                    <?php endif; ?>

                    <h6 class="border-bottom pb-2 mt-4 mb-3" style="border-color: var(--border) !important;">4. Khác</h6>
                    <p class="text-muted"><small><i>Các công cụ quét SQL Injection và phân quyền sẽ được cập nhật trong các phiên bản tới...</i></small></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#e2e-form').on('submit', function(e) {
        e.preventDefault();
        
        var adminPass = $('#admin_pass').val();
        if (!adminPass) return;
        
        if (!confirm('Hệ thống sẽ bóc tách và mã hóa dần các tin nhắn chưa an toàn bằng khóa AES-256-CBC.\n\nQuá trình này không thể hoàn tác. Bạn đã sẵn sàng?')) {
            return;
        }

        // Ẩn form, hiện progress
        $('#e2e-form').slideUp();
        $('#progress-container').slideDown();
        
        var total = <?= _e($total_unencrypted) ?>;
        var processed = 0;
        
        function processChunk() {
            $.ajax({
                url: '<?= url('/manager/security') ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    ajax_e2e: 1,
                    csrf_token: $('#csrf_token').val(),
                    admin_pass: adminPass
                },
                success: function(res) {
                    if (res.status === 'error') {
                        alert('Lỗi: ' + res.message);
                        window.location.reload();
                        return;
                    }
                    
                    processed += res.migrated;
                    var remaining = res.remaining;
                    var percent = Math.min(100, Math.round((processed / total) * 100));
                    
                    $('#progress-bar').css('width', percent + '%').text(percent + '%');
                    $('#progress-text').text('Đang xử lý: ' + processed + ' / ' + total);
                    
                    if (remaining > 0 && res.migrated > 0) {
                        // Tiếp tục gọi
                        setTimeout(processChunk, 200); // nghỉ 200ms tránh spam
                    } else {
                        // Đã xong
                        $('#progress-bar').removeClass('progress-bar-animated').text('Hoàn tất!');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    }
                },
                error: function() {
                    alert('Lỗi kết nối tới máy chủ. Vui lòng thử lại!');
                    window.location.reload();
                }
            });
        }
        
        // Khởi chạy vòng lặp
        processChunk();
    });
});
</script>
