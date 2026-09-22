<?php
$this->layout('manager/layout', ['page_title' => $page_title ?? 'Quản lý Module', 'panelActive' => 'modules']);
?>
<div class="row">
    <div class="col-md-12">
        <?php if (isset($_COOKIE['manager_module_error'])): ?>
            <div class="alert alert-danger"><?= $_COOKIE['manager_module_error'] ?></div>
            <?php setcookie('manager_module_error', '', time() - 3600, '/'); ?>
        <?php endif; ?>
        <?php if (isset($_COOKIE['manager_module_success'])): ?>
            <div class="alert alert-success"><?= $_COOKIE['manager_module_success'] ?></div>
            <?php setcookie('manager_module_success', '', time() - 3600, '/'); ?>
        <?php endif; ?>
        

        <div class="dw-card mb-4">
            <div class="dw-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <a href="<?= url('/manager/modules') ?>" class="text-decoration-none text-muted"><i class="fa fa-arrow-left"></i> Nâng cao</a> / Quản lý Module Gốc
                </h5>
            </div>
            <div class="dw-card-body">
                <div class="alert alert-warning mb-0">
                    <i class="fa fa-exclamation-triangle"></i> <strong>Lưu ý quan trọng:</strong> Hãy cẩn trọng khi chỉnh sửa mã nguồn hoặc thông tin của các module gốc. Nếu bạn không có kiến thức về lập trình website, việc sửa đổi sai cách có thể làm lỗi toàn bộ hệ thống!
                </div>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Phiên bản</th>
                            <th>Tác giả</th>
                            <th>Tình trạng</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        <?php foreach ($core_modules as $m): ?>
                            <?php 
                                $descFile = APP . 'modules' . DS . $m['name'] . DS . 'description.json';
                                $descData = ['title' => $m['name'], 'description' => 'Không có mô tả', 'author' => 'System', 'version' => '1.0.0'];
                                if (file_exists($descFile)) {
                                    $descData = json_decode(file_get_contents($descFile), true) ?: $descData;
                                }
                                $descJson = base64_encode(json_encode($descData));
                            ?>
                            <tr>
                                <td>
                                    <strong><?= $descData['title'] ?? $m['name'] ?></strong><br>
                                    <small class="text-muted"><?= $m['name'] ?></small>
                                </td>
                                <td><?= $descData['version'] ?? '1.0.0' ?></td>
                                <td><?= $descData['author'] ?? 'System' ?></td>
                                <td><span class="badge bg-label-primary">Tích hợp sẵn</span></td>
                                <td>
                                    <a href="<?= url('/manager/modules/template?name=' . $m['name']) ?>" class="btn btn-warning btn-sm">
                                        <i class="fa fa-pencil"></i> Sửa Code
                                    </a>
                                    <button type="button" class="btn btn-info btn-sm" onclick="openEditInfoModal('<?= $m['name'] ?>', '<?= _e($descJson) ?>')">
                                        <i class="fa fa-info-circle"></i> Thông tin
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dw-card mb-4">
            <div class="dw-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    Module Mở Rộng
                </h5>
                <div>
                    <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#uploadModuleModal">
                        <i class="fa fa-upload"></i> Tải lên Module
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModuleModal">
                        <i class="fa fa-plus"></i> Tạo Module
                    </button>
                </div>
            </div>
            <div class="dw-card-body pt-3">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" role="tab" data-toggle="tab" href="#navs-installed" aria-controls="navs-installed" aria-selected="true">
                            Đã cài đặt
                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-toggle="tab" href="#navs-not-installed" aria-controls="navs-not-installed" aria-selected="false">
                            Chưa cài đặt
                        </button>
                    </li>
                </ul>
                <div class="tab-content shadow-none border-0 p-0 mt-3">
                    <!-- TAB ĐÃ CÀI ĐẶT -->
                    <div class="tab-pane fade show active" id="navs-installed" role="tabpanel">
                        <div class="table-responsive text-nowrap border rounded">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Tên</th>
                                        <th>Phiên bản</th>
                                        <th>Tác giả</th>
                                        <th>Tình trạng</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    <!-- Installed Custom Modules -->
                                    <?php foreach ($extended_modules as $m): ?>
                                        <?php 
                                        $systemConfigPath = ROOT . 'system/configs/autoload/system.php';
                                        $systemConfig = require $systemConfigPath;
                                        $installedModules = $systemConfig['app']['installed_modules'] ?? [];
                                        $isInstalled = in_array($m['name'], $installedModules);
                                        
                                        if ($isInstalled):
                                            $descFile = APP . 'modules' . DS . 'custom_module' . DS . $m['name'] . DS . 'description.json';
                                            $descData = ['title' => $m['name'], 'description' => 'Không có mô tả', 'author' => 'Unknown', 'version' => '1.0.0'];
                                            if (file_exists($descFile)) {
                                                $descData = json_decode(file_get_contents($descFile), true) ?: $descData;
                                            }
                                            $descJson = base64_encode(json_encode($descData));
                                            $uninstallSql = APP . 'modules' . DS . 'custom_module' . DS . $m['name'] . DS . $m['name'] . '_uninstall.sql';
                                        ?>
                                            <tr>
                                                <td><strong><?= $descData['title'] ?? $m['name'] ?></strong><br><small class="text-muted"><?= $m['name'] ?></small></td>
                                                <td><?= $descData['version'] ?? '1.0.0' ?></td>
                                                <td><?= $descData['author'] ?? 'Unknown' ?></td>
                                                <td><span class="badge bg-label-success">Đã cài đặt</span></td>
                                                <td>
                                                    <a href="<?= url('/manager/modules/template?name=' . $m['name']) ?>" class="btn btn-warning btn-sm">
                                                        <i class="fa fa-pencil"></i> Sửa Code
                                                    </a>
                                                    <?php if (file_exists($uninstallSql)): ?>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmModuleAction('uninstall', '<?= $m['name'] ?>')">
                                                        <i class="fa fa-trash-o"></i> Gỡ cài đặt
                                                    </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-info btn-sm" onclick="openEditInfoModal('<?= $m['name'] ?>', '<?= _e($descJson) ?>')">
                                                        <i class="fa fa-info-circle"></i> Thông tin
                                                    </button>
                                                    <button type="button" class="btn btn-secondary btn-sm" onclick="openPackageModal('<?= $m['name'] ?>')">
                                                        <i class="fa fa-archive"></i> Đóng gói
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB CHƯA CÀI ĐẶT -->
                    <div class="tab-pane fade" id="navs-not-installed" role="tabpanel">
                        <div class="table-responsive text-nowrap border rounded">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Tên</th>
                                        <th>Phiên bản</th>
                                        <th>Tác giả</th>
                                        <th>Tình trạng</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    <?php 
                                    $hasUninstalled = false;
                                    foreach ($extended_modules as $m): 
                                        $systemConfigPath = ROOT . 'system/configs/autoload/system.php';
                                        $systemConfig = require $systemConfigPath;
                                        $installedModules = $systemConfig['app']['installed_modules'] ?? [];
                                        $isInstalled = in_array($m['name'], $installedModules);
                                        
                                        if (!$isInstalled):
                                            $hasUninstalled = true;
                                            $descFile = APP . 'modules' . DS . 'custom_module' . DS . $m['name'] . DS . 'description.json';
                                            $descData = ['title' => $m['name'], 'description' => 'Không có mô tả', 'author' => 'Unknown', 'version' => '1.0.0'];
                                            if (file_exists($descFile)) {
                                                $descData = json_decode(file_get_contents($descFile), true) ?: $descData;
                                            }
                                            $descJson = base64_encode(json_encode($descData));
                                            $installSql = APP . 'modules' . DS . 'custom_module' . DS . $m['name'] . DS . $m['name'] . '_install.sql';
                                    ?>
                                            <tr>
                                                <td><strong><?= $descData['title'] ?? $m['name'] ?></strong><br><small class="text-muted"><?= $m['name'] ?></small></td>
                                                <td><?= $descData['version'] ?? '1.0.0' ?></td>
                                                <td><?= $descData['author'] ?? 'Unknown' ?></td>
                                                <td><span class="badge bg-label-secondary">Chưa cài đặt</span></td>
                                                <td>
                                                    <a href="<?= url('/manager/modules/template?name=' . $m['name']) ?>" class="btn btn-warning btn-sm">
                                                        <i class="fa fa-pencil"></i> Sửa Code
                                                    </a>
                                                    <?php if (file_exists($installSql)): ?>
                                                    <button type="button" class="btn btn-success btn-sm" onclick="confirmModuleAction('install', '<?= $m['name'] ?>')">
                                                        <i class="fa fa-download"></i> Cài đặt
                                                    </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmModuleAction('delete', '<?= $m['name'] ?>')">
                                                        <i class="fa fa-trash"></i> Xóa
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    
                                    <?php if(!$hasUninstalled): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Không có module nào chưa cài đặt</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Module Modal -->
<div class="modal fade" id="addModuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="<?= url('/manager/modules/add') ?>" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo Module mới</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tên Module</label>
                    <input type="text" name="module_name_new" class="form-control" placeholder="custom_xxxx" required />
                    <small class="text-muted">Bắt buộc bắt đầu bằng <strong>custom_</strong> (chỉ chứa a-z, 0-9, gạch dưới)</small>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="form-label text-danger">Tên phân hệ xác nhận</label>
                    <input type="text" name="module_name" class="form-control" placeholder="modules" required />
                </div>
                <div class="mb-3">
                    <label class="form-label text-danger">Mật khẩu Admin</label>
                    <input type="password" name="admin_pass" class="form-control" required />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary">Tạo mới</button>
            </div>
        </form>
    </div>
</div>

<!-- Upload Module Modal -->
<div class="modal fade" id="uploadModuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="<?= url('/manager/modules/upload') ?>" method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tải lên Module (.zip)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Chọn tệp module (.zip)</label>
                    <input type="file" name="module_zip" class="form-control" accept=".zip" required />
                    <small class="text-muted">Hệ thống sẽ tự động quét mã độc và từ chối nếu có shell/hàm nguy hiểm.</small>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="form-label text-danger">Tên phân hệ xác nhận</label>
                    <input type="text" name="module_name" class="form-control" placeholder="modules" required />
                </div>
                <div class="mb-3">
                    <label class="form-label text-danger">Mật khẩu Admin</label>
                    <input type="password" name="admin_pass" class="form-control" required />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-success">Tải lên</button>
            </div>
        </form>
    </div>
</div>

<!-- Action Module Modal (Delete / Download) -->
<div class="modal fade" id="actionModuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <form action="" id="actionModuleForm" method="POST" class="modal-content">
    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModuleTitle">Xác nhận</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="actionModuleText">Bạn có chắc chắn muốn thực hiện hành động này?</p>
                <input type="hidden" name="name" id="action_module_name" value="">
                <div class="mb-3">
                    <label class="form-label text-danger">Tên phân hệ xác nhận</label>
                    <input type="text" name="module_name" class="form-control" placeholder="modules" required />
                </div>
                <div class="mb-3">
                    <label class="form-label text-danger">Mật khẩu Admin</label>
                    <input type="password" name="admin_pass" class="form-control" required />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary" id="actionModuleBtn">Xác nhận</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="packageModuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="<?= url('/manager/modules/download') ?>" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đóng gói Module</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Nhập thông tin module để tạo file <code>module.json</code> khi đóng gói.</p>
                <input type="hidden" name="name" id="package_module_name" value="">
                
                <div class="mb-3">
                    <label class="form-label">Tên hiển thị (Name)</label>
                    <input type="text" name="module_desc_name" class="form-control" placeholder="Tên module..." required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Chức năng (Function)</label>
                    <input type="text" name="module_desc_func" class="form-control" placeholder="Mô tả chức năng..." />
                </div>
                <div class="mb-3">
                    <label class="form-label">Tác giả (Author)</label>
                    <input type="text" name="module_desc_author" class="form-control" placeholder="Tên tác giả..." />
                </div>
                <div class="mb-3">
                    <label class="form-label">Phiên bản (Version)</label>
                    <input type="text" name="module_desc_version" class="form-control" placeholder="1.0.0" />
                </div>
                
                <hr>
                <div class="mb-3">
                    <label class="form-label text-danger">Tên phân hệ xác nhận</label>
                    <input type="text" name="module_name" class="form-control" placeholder="modules" required />
                </div>
                <div class="mb-3">
                    <label class="form-label text-danger">Mật khẩu Admin</label>
                    <input type="password" name="admin_pass" class="form-control" required />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Đóng gói & Tải xuống</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Module Info Modal -->
<div class="modal fade" id="editModuleInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="<?= url('/manager/modules/edit_info') ?>" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sửa thông tin Module</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="name" id="edit_info_module_name" value="">
                
                <div class="mb-3">
                    <label class="form-label">Mã Module (Bắt đầu bằng custom_)</label>
                    <input type="text" name="module_new_name" id="edit_info_new_name" class="form-control" required pattern="^custom_[a-zA-Z0-9_]+$" title="Phải bắt đầu bằng custom_" />
                    <small class="text-muted">Khi đổi mã này, thư mục và các file lõi bên trong sẽ tự động được đổi tên theo.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tên hiển thị (Title)</label>
                    <input type="text" name="module_desc_title" id="edit_info_title" class="form-control" placeholder="Tên module..." required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Mô tả (Description)</label>
                    <textarea name="module_desc_desc" id="edit_info_desc" class="form-control" placeholder="Mô tả chức năng..." rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tác giả (Author)</label>
                    <input type="text" name="module_desc_author" id="edit_info_author" class="form-control" placeholder="Tên tác giả..." />
                </div>
                <div class="mb-3">
                    <label class="form-label">Phiên bản (Version)</label>
                    <input type="text" name="module_desc_version" id="edit_info_version" class="form-control" placeholder="1.0.0" />
                </div>
                
                <hr>
                <div class="mb-3">
                    <label class="form-label text-danger">Mật khẩu Admin</label>
                    <input type="password" name="admin_pass" class="form-control" required />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmModuleAction(action, moduleName) {
    document.getElementById('action_module_name').value = moduleName;
    var form = document.getElementById('actionModuleForm');
    var title = document.getElementById('actionModuleTitle');
    var text = document.getElementById('actionModuleText');
    var btn = document.getElementById('actionModuleBtn');
    
    if (action === 'delete') {
        form.action = '<?= url('/manager/modules/delete') ?>';
        title.innerText = 'Xác nhận Xóa Module';
        text.innerText = 'Bạn có chắc chắn muốn xóa vĩnh viễn module "' + moduleName + '"? LƯU Ý: Thao tác này sẽ xóa cả thư mục code và template!';
        btn.className = 'btn btn-danger';
        btn.innerText = 'Xóa';
    } else if (action === 'install') {
        form.action = '<?= url('/manager/modules/install') ?>';
        title.innerText = 'Xác nhận Cài đặt Module';
        text.innerText = 'Bạn muốn chạy file cài đặt cho module "' + moduleName + '"? LƯU Ý: Nếu module đã cài, dữ liệu có thể bị ghi đè!';
        btn.className = 'btn btn-success';
        btn.innerText = 'Cài đặt';
    } else if (action === 'uninstall') {
        form.action = '<?= url('/manager/modules/uninstall') ?>';
        title.innerText = 'Xác nhận Gỡ cài đặt Module';
        text.innerText = 'Bạn muốn chạy lệnh gỡ cài đặt (xoá bảng CSDL) cho module "' + moduleName + '"? LƯU Ý: Dữ liệu của module sẽ bị mất vĩnh viễn!';
        btn.className = 'btn btn-danger';
        btn.innerText = 'Gỡ cài đặt';
    }
    
    $('#actionModuleModal').modal('show');
}

function openPackageModal(moduleName) {
    document.getElementById('package_module_name').value = moduleName;
    $('#packageModuleModal').modal('show');
}

function openEditInfoModal(moduleName, descJsonStr) {
    document.getElementById('edit_info_module_name').value = moduleName;
    try {
        var jsonStr = decodeURIComponent(escape(window.atob(descJsonStr)));
        var data = JSON.parse(jsonStr);
        document.getElementById('edit_info_new_name').value = moduleName;
        document.getElementById('edit_info_title').value = data.title || '';
        document.getElementById('edit_info_desc').value = data.description || '';
        document.getElementById('edit_info_author').value = data.author || '';
        document.getElementById('edit_info_version').value = data.version || '';
    } catch(e) {}
    $('#editModuleInfoModal').modal('show');
}
</script>

