<?php
$this->layout('manager/layout', ['page_title' => $page_title ?? 'Quản lý Người dùng', 'panelActive' => 'users']);
$my_level = $user['level'] ?? 0;
$my_id = $user['id'] ?? 0;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h5 class="font-weight-bold text-uppercase mb-0" style="color:var(--heading); font-size:16px;">
        <i class="fa fa-users" style="color:var(--accent)"></i> Quản lý Người dùng
    </h5>
    
    <div class="btn-toolbar mb-2 mb-md-0">
        <form action="<?= url('/manager/users') ?>" method="get" class="d-flex" style="gap: 10px;">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <select name="sort" class="form-control form-control-sm" style="width: auto; background: var(--surface-muted); border-radius: 20px;">
                <option value="id" <?= ($sort_by === 'id') ? 'selected' : '' ?>>Mới nhất</option>
                <option value="level" <?= ($sort_by === 'level') ? 'selected' : '' ?>>Level cao nhất</option>
                <option value="status" <?= ($sort_by === 'status') ? 'selected' : '' ?>>Bị khóa</option>
                <option value="xu" <?= ($sort_by === 'xu') ? 'selected' : '' ?>>Nhiều Xu nhất</option>
            </select>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Tìm theo ID hoặc Tên..." value="<?= _e($search) ?>" style="width: 200px; background: var(--surface-muted); border-radius: 20px;">
            <button type="submit" class="dw-btn dw-btn-sm dw-btn-primary" style="border-radius: 20px;"><i class="fa fa-search"></i> Lọc</button>
            <?php if (!empty($search) || $sort_by !== 'id'): ?>
                <a href="<?= url('/manager/users') ?>" class="dw-btn dw-btn-sm dw-btn-ghost" style="border-radius: 20px;"><i class="fa fa-times"></i> Hủy</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (isset($_COOKIE['manager_users_success'])): ?>
    <div class="alert alert-success">
        <i class="fa fa-check-circle"></i> <?= $_COOKIE['manager_users_success'] ?>
    </div>
    <?php setcookie('manager_users_success', '', time() - 3600, '/'); ?>
<?php endif; ?>

<?php if (isset($_COOKIE['manager_users_error'])): ?>
    <div class="alert alert-danger">
        <i class="fa fa-times-circle"></i> <?= $_COOKIE['manager_users_error'] ?>
    </div>
    <?php setcookie('manager_users_error', '', time() - 3600, '/'); ?>
<?php endif; ?>

<div class="dw-card">
    <div class="dw-card-header">
        <h5 class="m-0 font-weight-bold" style="color:var(--heading);"><i class="fa fa-users mr-2" style="color:var(--info);"></i> Danh sách thành viên (Tổng: <?= number_format($totalItems) ?>)</h5>
    </div>
    <div class="table-responsive">
        <table class="table mb-0" style="color: var(--fg);">
            <thead style="background: var(--surface-muted); color: var(--fg-muted);">
                <tr>
                    <th scope="col" class="text-center border-top-0" style="width: 60px;">ID</th>
                    <th scope="col" class="border-top-0">Tài khoản</th>
                    <th scope="col" class="border-top-0">Level</th>
                    <th scope="col" class="border-top-0">Tài sản (Xu)</th>
                    <th scope="col" class="border-top-0">Trạng thái</th>
                    <th scope="col" class="text-right border-top-0">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4 border-top-0">Không tìm thấy người dùng nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <?php 
                        $is_banned = ($u['level'] < 0); 
                        $can_modify = ($my_level > $u['level']); // Không thể xử lý người có level cao hơn hoặc bằng
                        if ($u['id'] == $my_id) $can_modify = true; // Nhưng có thể tự xử lý chính mình (VD tự mở khóa nếu có thể)
                        ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td class="text-center font-weight-bold text-muted align-middle border-top-0"><?= $u['id'] ?></td>
                            <td class="align-middle border-top-0">
                                <a href="<?= url('/user/' . _e($u['nick'])) ?>" class="font-weight-bold <?= $is_banned ? 'text-danger' : '' ?>" target="_blank" style="<?= $is_banned ? 'text-decoration: line-through;' : 'color:var(--heading); text-decoration: none;' ?>">
                                    <?= $u['nick'] ?>
                                </a>
                                <?php if (!empty($u['name'])): ?>
                                    <div class="small text-muted"><?= $u['name'] ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle border-top-0">
                                <span style="background: <?= $u['level'] >= 126 ? '#ff4d4f' : ($u['level'] >= 120 ? 'var(--primary)' : 'var(--surface-muted)') ?>; color: <?= $u['level'] >= 120 ? '#fff' : 'var(--fg)' ?>; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; border: <?= $u['level'] < 120 ? '1px solid var(--border)' : 'none' ?>;">
                                    Level <?= $u['level'] ?>
                                </span>
                            </td>
                            <td class="font-weight-bold align-middle border-top-0" style="color: #faad14;">
                                <?= number_format($u['xu'] ?? 0) ?>
                            </td>
                            <td class="align-middle border-top-0">
                                <?php if ($is_banned): ?>
                                    <span style="background: rgba(255, 77, 79, 0.1); color: #ff4d4f; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;"><i class="fa fa-ban"></i> Bị khóa</span>
                                <?php else: ?>
                                    <span style="background: rgba(40, 167, 69, 0.1); color: #28a745; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;"><i class="fa fa-check"></i> Hoạt động</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right align-middle border-top-0">
                                <?php if ($can_modify): ?>
                                    <div class="d-inline-flex" style="gap: 5px;">
                                        <?php if ($can_modify): ?>
                                            <button type="button" class="dw-btn dw-btn-sm" style="background: <?= $is_banned ? 'rgba(40, 167, 69, 0.1)' : 'rgba(255, 77, 79, 0.1)' ?>; color: <?= $is_banned ? '#28a745' : '#ff4d4f' ?>; border: 1px solid <?= $is_banned ? 'rgba(40, 167, 69, 0.2)' : 'rgba(255, 77, 79, 0.2)' ?>;" data-toggle="modal" data-target="#banUserModal<?= $u['id'] ?>" title="<?= $is_banned ? 'Mở khóa' : 'Khóa' ?>">
                                                <i class="fa <?= $is_banned ? 'fa-unlock' : 'fa-lock' ?>"></i>
                                            </button>
                                        
                                            <button type="button" class="dw-btn dw-btn-sm" style="background: var(--surface-muted); color: var(--primary); border: 1px solid var(--border);" data-toggle="modal" data-target="#editUserModal<?= $u['id'] ?>" title="Chỉnh sửa thông tin">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($u['id'] != $my_id): // Không cho phép tự reset pass ?>
                                        <button type="button" class="dw-btn dw-btn-sm" style="background: var(--surface-muted); color: #faad14; border: 1px solid var(--border);" data-toggle="modal" data-target="#resetPassModal<?= $u['id'] ?>" title="Reset mật khẩu">
                                            <i class="fa fa-key"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($can_modify): ?>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <button class="dw-btn dw-btn-sm" style="background: var(--surface-muted); color: var(--fg-muted); border: 1px solid var(--border); opacity:0.6; cursor:not-allowed;" title="Không có quyền" disabled><i class="fa fa-shield"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Phân trang -->
    <?php if ($totalPages > 1): ?>
        <div class="dw-card-footer" style="background: var(--surface); border-top: 1px solid var(--border); display: flex; justify-content: center; padding: 15px 0;">
            <nav>
                <ul class="pagination m-0" style="gap: 5px;">
                    <?php 
                    $query = empty($search) ? '' : '&q=' . urlencode($search);
                    $query .= ($sort_by !== 'id') ? '&sort=' . urlencode($sort_by) : '';
                    $start_page = max(1, $page - 2);
                    $end_page = min($totalPages, $page + 2);
                    ?>
                    
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= url('/manager/users?page=' . ($page - 1) . $query) ?>" style="background: var(--surface-muted); color: var(--fg); border: 1px solid var(--border); border-radius: 4px;">Trước</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($start_page > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= url('/manager/users?page=1' . $query) ?>" style="background: var(--surface-muted); color: var(--fg); border: 1px solid var(--border); border-radius: 4px;">1</a></li>
                        <?php if ($start_page > 2): ?>
                            <li class="page-item disabled"><span class="page-link" style="background: transparent; border: none; color: var(--fg-muted);">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('/manager/users?page=' . $i . $query) ?>" style="<?= ($i == $page) ? 'background: var(--primary); color: #fff; border-color: var(--primary);' : 'background: var(--surface-muted); color: var(--fg); border: 1px solid var(--border);' ?> border-radius: 4px;"><?= _e($i) ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $totalPages): ?>
                        <?php if ($end_page < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link" style="background: transparent; border: none; color: var(--fg-muted);">...</span></li>
                        <?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?= url('/manager/users?page=' . $totalPages . $query) ?>" style="background: var(--surface-muted); color: var(--fg); border: 1px solid var(--border); border-radius: 4px;"><?= _e($totalPages) ?></a></li>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= url('/manager/users?page=' . ($page + 1) . $query) ?>" style="background: var(--surface-muted); color: var(--fg); border: 1px solid var(--border); border-radius: 4px;">Sau</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Nơi render các modals để tránh lỗi z-index -->
<?php if (!empty($users)): ?>
    <?php foreach ($users as $u): ?>
        <?php
        $is_banned = ($u['level'] < 0);
        $can_modify = ($my_level >= 126 || $u['level'] < $my_level);
        ?>
        <?php if ($can_modify): ?>
        <!-- Modal Ban/Unban User -->
        <div class="modal fade text-left" id="banUserModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" action="<?= url('/manager/users/ban') ?>" method="post" style="background: var(--surface); color: var(--fg); border-radius: 12px; border: 1px solid var(--border);">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <div class="modal-header border-bottom-0">
                        <h5 class="modal-title font-weight-bold text-<?= $is_banned ? 'success' : 'danger' ?>"><?= $is_banned ? 'Mở khóa' : 'Khóa' ?>: <?= $u['nick'] ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:var(--fg);">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <p>Bạn đang thực hiện thao tác <strong><?= $is_banned ? 'Mở khóa' : 'Khóa' ?></strong> đối với tài khoản <strong><?= $u['nick'] ?></strong>.</p>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Tên phân hệ (Nhập <code>users</code>)</label>
                            <input type="text" class="form-control" name="module_name" required autocomplete="off" placeholder="users" style="background:var(--surface-muted); border-radius:8px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Mật khẩu Admin của bạn</label>
                            <input type="password" class="form-control" name="admin_pass" required style="background:var(--surface-muted); border-radius:8px;">
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="dw-btn dw-btn-ghost" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="dw-btn dw-btn-<?= $is_banned ? 'primary' : 'primary' ?>" style="background:<?= $is_banned ? '#28a745' : '#ff4d4f' ?>; border:none;"><i class="fa fa-check"></i> Xác nhận</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit User -->
        <div class="modal fade text-left" id="editUserModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" action="<?= url('/manager/users/edit') ?>" method="post" style="background: var(--surface); color: var(--fg); border-radius: 12px; border: 1px solid var(--border);">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <div class="modal-header border-bottom-0">
                        <h5 class="modal-title font-weight-bold text-primary">Chỉnh sửa: <?= $u['nick'] ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:var(--fg);">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Tên hiển thị (Name)</label>
                            <input type="text" class="form-control" name="user_name" value="<?= $u['name'] ?? '' ?>" placeholder="Tên hiển thị" style="background:var(--surface-muted); border-radius:8px;">
                        </div>
                        
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label font-weight-bold">Chức vụ (Level)</label>
                                <?php if ($u['id'] == $my_id): ?>
                                    <input type="text" class="form-control" value="<?= $u['level'] ?>" readonly style="background:var(--surface-muted); border-radius:8px; opacity:0.7;">
                                    <input type="hidden" name="user_level" value="<?= $u['level'] ?>">
                                    <small class="text-muted mt-1 d-block">Không thể tự sửa chức vụ.</small>
                                <?php else: ?>
                                    <select class="form-control" name="user_level" required style="background:var(--surface-muted); border-radius:8px;">
                                        <?php
                                        $roles = [
                                            0 => 'Thành viên (0)',
                                            120 => 'Contributor (120)',
                                            121 => 'Moderator (121)',
                                            122 => 'Super Moderator (122)',
                                            126 => 'Administrator (126)',
                                        ];
                                        if (!isset($roles[$u['level']])) {
                                            $roles[$u['level']] = 'Khác (' . $u['level'] . ')';
                                        }
                                        ksort($roles);
                                        foreach ($roles as $lvl => $name): 
                                            if ($lvl >= $my_level && $my_level < 126 && $lvl != $u['level']) continue;
                                        ?>
                                            <option value="<?= _e($lvl) ?>" <?= ($u['level'] == $lvl) ? 'selected' : '' ?>><?= _e($name) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label font-weight-bold">Tài sản (Xu)</label>
                                <input type="number" class="form-control" name="user_xu" value="<?= $u['xu'] ?? 0 ?>" required style="background:var(--surface-muted); border-radius:8px;">
                            </div>
                        </div>
                        <hr style="border-color:var(--border);">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold text-danger">Xác thực thao tác</label>
                            <p class="small text-muted mb-2">Nhập <code>users</code> và mật khẩu Admin của bạn để lưu thay đổi.</p>
                            <input type="text" class="form-control mb-2" name="module_name" required autocomplete="off" placeholder="Tên phân hệ (users)" style="background:var(--surface-muted); border-radius:8px;">
                            <input type="password" class="form-control" name="admin_pass" required placeholder="Mật khẩu Admin" style="background:var(--surface-muted); border-radius:8px;">
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="dw-btn dw-btn-ghost" data-dismiss="modal">Đóng</button>
                        <button type="submit" class="dw-btn dw-btn-primary"><i class="fa fa-save"></i> Lưu</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Modal Reset Password -->
        <?php if ($u['id'] != $my_id): ?>
        <div class="modal fade text-left" id="resetPassModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" action="<?= url('/manager/users/reset_pass') ?>" method="post" style="background: var(--surface); color: var(--fg); border-radius: 12px; border: 1px solid var(--border);">
<input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <div class="modal-header" style="background-color: #faad14; color: #fff; border-radius: 12px 12px 0 0;">
                        <h5 class="modal-title font-weight-bold">Reset mật khẩu cho <?= $u['nick'] ?></h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert" style="background-color: rgba(250, 173, 20, 0.1); color: #faad14; border: 1px solid rgba(250, 173, 20, 0.2);">
                            <i class="fa fa-exclamation-triangle"></i> Mật khẩu của <strong><?= $u['nick'] ?></strong> sẽ được đổi thành: <br><code class="font-weight-bold" style="font-size:1.1rem; color:#faad14;"><?= strtolower($u['nick']) ?>123000</code>.
                        </div>
                        <p class="mt-3">Để thực hiện, vui lòng xác nhận thao tác.</p>
                        <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Tên phân hệ (Nhập <code>users</code>)</label>
                            <input type="text" class="form-control" name="module_name" required autocomplete="off" placeholder="users" style="background:var(--surface-muted); border-radius:8px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Mật khẩu Admin của bạn</label>
                            <input type="password" class="form-control" name="admin_pass" required style="background:var(--surface-muted); border-radius:8px;">
                        </div>
                    </div>
                    <div class="modal-footer border-top-0">
                        <button type="button" class="dw-btn dw-btn-ghost" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="dw-btn" style="background-color: #faad14; color: #fff;">Xác nhận</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
