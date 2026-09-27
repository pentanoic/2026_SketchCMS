<?php
$this->layout('manager/layout', ['page_title' => $page_title ?? 'Giao diện Module', 'panelActive' => 'modules']);
?>

<div class="row mb-3">
    <div class="col-md-12">
        <?php if (isset($_COOKIE['manager_module_error'])): ?>
            <div class="alert alert-danger"><?= $_COOKIE['manager_module_error'] ?></div>
            <?php setcookie('manager_module_error', '', time() - 3600, '/'); ?>
        <?php endif; ?>
        <?php if (isset($_COOKIE['manager_module_success'])): ?>
            <div class="alert alert-success"><?= $_COOKIE['manager_module_success'] ?></div>
            <?php setcookie('manager_module_success', '', time() - 3600, '/'); ?>
        <?php endif; ?>
    </div>
</div>

<?php
$isBackend = strpos($current_path, 'backend') === 0 || empty($current_path);
$is_core = $is_core ?? false;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h5 class="font-weight-bold text-uppercase mb-0" style="color:var(--heading); font-size:16px;">
        <a href="<?= url('/manager/modules') ?>" class="text-decoration-none text-muted"><i class="fa fa-arrow-left"></i> Modules</a> / Template: <?= _e($module_name) ?>
    </h5>
    <div>
        <?php if (!$isBackend && !$is_core): ?>
            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createFileModal"><i class="fa fa-file"></i> Tạo File</button>
            <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#createDirModal"><i class="fa fa-folder"></i> Tạo Thư mục</button>
        <?php endif; ?>
    </div>
</div>

<div class="row" id="workspaceRow">
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="fa fa-folder-open text-warning me-2"></i> Cây thư mục
            </div>
            <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                <?php if (!empty($current_path)): ?>
                    <?php
                    $parent = dirname($current_path);
                    if ($parent === '.' || $parent === '\\') $parent = '';
                    ?>
                    <a href="<?= url('/manager/modules/template?name=' . urlencode($module_name) . '&path=' . urlencode($parent)) ?>" class="list-group-item list-group-item-action text-primary">
                        <i class="fa fa-level-up me-2"></i> .. (Lên một cấp)
                    </a>
                <?php endif; ?>

                <?php foreach ($items as $item): ?>
                    <a href="<?= url('/manager/modules/template?name=' . urlencode($module_name) . '&path=' . urlencode($item['path'])) ?>"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= ($current_path === $item['path']) ? 'active' : '' ?>">
                        <span>
                            <?php if ($item['is_dir']): ?>
                                <i class="fa fa-folder text-warning me-2"></i>
                            <?php else: ?>
                                <i class="fa fa-file-code-o text-secondary me-2"></i>
                            <?php endif; ?>
                            <?= $item['name'] ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-md-9 mb-4">
        <div class="card shadow-sm h-100">
            <?php if ($is_file): ?>
                <form id="editorForm" action="<?= url('/manager/modules/template/action') ?>" method="POST" class="h-100 d-flex flex-column">
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <input type="hidden" name="name" value="<?= _e($module_name) ?>">
                    <input type="hidden" name="action" value="save_file">
                    <input type="hidden" name="path" value="<?= _e($current_path) ?>">

                    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="fa <?= $is_core ? 'fa-eye' : 'fa-edit' ?> me-2"></i> <?= $is_core ? 'Xem Code' : 'Chỉnh sửa' ?>: <?= _e($current_file) ?></span>
                        <div>
                            <?php if (!$isBackend && !$is_core): ?>
                                <button type="button" class="btn btn-sm btn-info" onclick="openRenameModal('<?= _e($current_file) ?>')"><i class="fa fa-pencil"></i> Đổi tên</button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteModal()"><i class="fa fa-trash"></i> Xóa</button>
                            <?php endif; ?>
                            <?php if (!$is_core): ?>
                                <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-save"></i> Lưu</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0 flex-grow-1">
                        <textarea id="fileEditor" style="display:none;"><?= _e($file_content) ?></textarea>
                        <input type="hidden" name="file_content" id="hiddenFileContent" value="">
                        <div id="aceEditor" style="height: 500px; width: 100%;"></div>
                    </div>
                </form>
            <?php else: ?>
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-folder-open-o me-2"></i> Thư mục: <?= empty($current_path) ? '/' : _e($current_path) ?></span>
                    <div>
                        <?php if (!empty($current_path) && !$isBackend && !$is_core): ?>
                            <button type="button" class="btn btn-sm btn-info" onclick="openRenameModal('<?= basename($current_path) ?>')"><i class="fa fa-pencil"></i> Đổi tên</button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteModal()"><i class="fa fa-trash"></i> Xóa</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-4 text-center text-muted">
                    <i class="fa fa-hand-pointer-o fa-3x mb-3 text-secondary"></i>
                    <p>Vui lòng chọn một tệp văn bản bên cây thư mục để xem/chỉnh sửa.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="createFileModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="<?= url('/manager/modules/template/action') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <input type="hidden" name="name" value="<?= _e($module_name) ?>">
            <input type="hidden" name="action" value="create_file">
            <input type="hidden" name="path" value="<?= _e($dir_path) ?>">
            <div class="modal-header">
                <h5 class="modal-title">Tạo File mới</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <label>Tên file (vd: style.css, view.php)</label>
                <input type="text" name="filename" class="form-control" required>
                <div class="mt-3">
                    <label>Mật khẩu cấp 2</label>
                    <input type="password" name="second_password" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Tạo</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="createDirModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="<?= url('/manager/modules/template/action') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <input type="hidden" name="name" value="<?= _e($module_name) ?>">
            <input type="hidden" name="action" value="create_dir">
            <input type="hidden" name="path" value="<?= _e($dir_path) ?>">
            <div class="modal-header">
                <h5 class="modal-title">Tạo Thư mục mới</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <label>Tên thư mục</label>
                <input type="text" name="dirname" class="form-control" required>
                <div class="mt-3">
                    <label>Mật khẩu cấp 2</label>
                    <input type="password" name="second_password" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Tạo</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="renameModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="<?= url('/manager/modules/template/action') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <input type="hidden" name="name" value="<?= _e($module_name) ?>">
            <input type="hidden" name="action" value="rename">
            <input type="hidden" name="path" value="<?= _e($current_path) ?>">
            <div class="modal-header">
                <h5 class="modal-title">Đổi tên</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <label>Tên mới</label>
                <input type="text" name="new_name" id="rename_input" class="form-control" required>
                <div class="mt-3">
                    <label>Mật khẩu cấp 2</label>
                    <input type="password" name="second_password" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" action="<?= url('/manager/modules/template/action') ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <input type="hidden" name="name" value="<?= _e($module_name) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="path" value="<?= _e($current_path) ?>">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Xác nhận Xóa</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa <strong id="delete_target_name"><?= basename($current_path) ?></strong>?</p>
                <p class="text-danger small">Hành động này không thể hoàn tác.</p>
                <div class="mt-3">
                    <label>Mật khẩu cấp 2</label>
                    <input type="password" name="second_password" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-danger">Xóa</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.36.2/ace.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.36.2/ext-language_tools.min.js"></script>

<script>
    function openRenameModal(currentName) {
        document.getElementById('rename_input').value = currentName;
        $('#renameModal').modal('show');
    }

    function openDeleteModal() {
        $('#deleteModal').modal('show');
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('aceEditor')) {
            ace.require("ace/ext/language_tools");
            var editor = ace.edit("aceEditor");
            editor.setTheme("ace/theme/monokai");

            var filePath = "<?= _e($current_file) ?>";
            var ext = filePath.split('.').pop().toLowerCase();
            if (ext === 'css') editor.session.setMode("ace/mode/css");
            else if (ext === 'js') editor.session.setMode("ace/mode/javascript");
            else if (ext === 'json') editor.session.setMode("ace/mode/json");
            else if (ext === 'html') editor.session.setMode("ace/mode/html");
            else if (ext === 'sql') editor.session.setMode("ace/mode/sql");
            else editor.session.setMode("ace/mode/php");

            editor.setOptions({
                enableBasicAutocompletion: true,
                enableSnippets: true,
                enableLiveAutocompletion: true,
                fontSize: "14px"
            });

            var hiddenTextarea = document.getElementById('fileEditor');
            editor.setValue(hiddenTextarea.value, -1);

            <?php if ($is_core): ?>
                editor.setReadOnly(true);
            <?php endif; ?>

            document.getElementById('editorForm').addEventListener('submit', function(e) {
                e.preventDefault();
                <?php if ($is_core): ?>
                    return false;
                <?php else: ?>
                    var hiddenInput = document.getElementById('hiddenFileContent');
                    if (hiddenInput) {
                        hiddenInput.value = editor.getValue();
                    }
                    var form = this;
                    var submitBtn = form.querySelector('button[type="submit"]');
                    var originalHtml = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Đang lưu...';
                    submitBtn.disabled = true;

                    fetch(form.getAttribute('action'), {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                submitBtn.innerHTML = '<i class="fa fa-check"></i> Đã lưu';
                            } else {
                                submitBtn.innerHTML = '<i class="fa fa-times"></i> ' + (data.message || 'Lỗi');
                            }
                            setTimeout(() => {
                                submitBtn.innerHTML = originalHtml;
                                submitBtn.disabled = false;
                            }, 2000);
                        })
                        .catch(err => {
                            submitBtn.innerHTML = '<i class="fa fa-times"></i> Lỗi hệ thống';
                            setTimeout(() => {
                                submitBtn.innerHTML = originalHtml;
                                submitBtn.disabled = false;
                            }, 2000);
                        });
                <?php endif; ?>
            });

            // Add Ctrl+S support
            editor.commands.addCommand({
                name: 'save',
                bindKey: {
                    win: "Ctrl-S",
                    "mac": "Cmd-S"
                },
                exec: function(editor) {
                    <?php if (!$is_core): ?>
                        document.getElementById('editorForm').dispatchEvent(new Event('submit', {
                            cancelable: true
                        }));
                    <?php endif; ?>
                }
            });
        }
    });
</script>