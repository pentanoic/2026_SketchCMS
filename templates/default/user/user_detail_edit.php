<?php $this->layout('layout'); ?>

<div class="dw-card mb-4">
    <div class="dw-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h3 class="dw-card-title"><i class="fa fa-cogs"></i> Cài đặt tài khoản</h3>
        <!-- Menu Tabs Navigation -->
        <ul class="nav nav-pills flex-nowrap" style="gap: 5px; overflow-x: auto;">
            <li class="nav-item">
                <a class="nav-link <?= ($action == 'info' || in_array($action, ['avatar', 'cover', 'waifu-header', 'waifu-rleft', 'waifu-rright'])) ? 'active bg-primary font-weight-bold text-white' : 'text-muted' ?>" 
                   href="<?= url('/user/' . $MyDetail['nick'] . '/info') ?>" style="white-space: nowrap; border-radius: 20px; padding: 5px 15px;">
                    <i class="fa fa-user-circle-o mr-1"></i> Hồ sơ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($action == 'password') ? 'active bg-primary font-weight-bold text-white' : 'text-muted' ?>" 
                   href="<?= url('/user/' . $MyDetail['nick'] . '/password') ?>" style="white-space: nowrap; border-radius: 20px; padding: 5px 15px;">
                    <i class="fa fa-lock mr-1"></i> Mật khẩu
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($action == 'blocklist') ? 'active bg-primary font-weight-bold text-white' : 'text-muted' ?>" 
                   href="<?= url('/user/' . $MyDetail['nick'] . '/blocklist') ?>" style="white-space: nowrap; border-radius: 20px; padding: 5px 15px;">
                    <i class="fa fa-ban mr-1"></i> Danh sách chặn
                </a>
            </li>
        </ul>
    </div>
    
    <div class="dw-card-body">
        <?php if ($error): ?>
            <div class="dw-alert dw-alert-danger mb-4"><i class="fa fa-exclamation-triangle mr-1"></i> <?= _e($error) ?></div>
        <?php endif ?>

        <form id="form" method="post" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <?php if ($action == 'info'): ?>
                <!-- Bố cục lưới cho form thông tin -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold text-muted small text-uppercase">Tên hiển thị</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text bg-light border-right-0"><i class="fa fa-id-card-o text-muted"></i></span></div>
                            <input class="form-control border-left-0 pl-2" type="text" name="name" value="<?= _e($name) ?>" placeholder="Nhập tên hiển thị" maxlength="32" />
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold text-muted small text-uppercase">Giới tính</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text bg-light border-right-0"><i class="fa fa-venus-mars text-muted"></i></span></div>
                            <select class="form-control border-left-0 pl-2" name="sex">
                                <option value="boy" <?= $sex == 'boy' ? 'selected' : '' ?>>Nam</option>
                                <option value="girl" <?= $sex == 'girl' ? 'selected' : '' ?>>Nữ</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-12 mb-4">
                        <label class="font-weight-bold text-muted small text-uppercase">Tâm trạng (Status)</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text bg-light border-right-0"><i class="fa fa-quote-left text-muted"></i></span></div>
                            <input class="form-control border-left-0 pl-2" type="text" name="status" value="<?= _e($status) ?>" placeholder="Hôm nay bạn thế nào?" maxlength="100" />
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold text-muted small text-uppercase d-block mb-2">Ảnh đại diện (Avatar mặc định)</label>
                        <div class="d-flex flex-wrap gap-2" style="gap: 5px; height: 120px; overflow-y: auto; padding: 10px; border: 1px solid var(--border); border-radius: var(--radius-md); background: var(--surface-muted);">
                            <a href="<?= url('/user/' . $MyDetail['nick'] . '/avatar') ?>" class="btn btn-outline-primary btn-sm mb-2 w-100"><i class="fa fa-upload"></i> Tải ảnh từ thiết bị</a>
                            <?php for ($i = 1; $i <= 29; $i++): ?>
                                <label class="m-0" style="cursor: pointer;">
                                    <input type="radio" name="avatar" value="<?= _e($i) ?>" <?= trim($avatar) == $i ? 'checked' : '' ?> class="d-none" onchange="$(this).parent().siblings().find('img').css('border','2px solid transparent'); $(this).next('img').css('border','2px solid var(--accent)');">
                                    <img src="<?= showAvtUser($i) ?>" style="width: 40px; height: 40px; border-radius: 5px; border: 2px solid <?= trim($avatar) == $i ? 'var(--accent)' : 'transparent' ?>;">
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold text-muted small text-uppercase d-block mb-2">Tùy biến Background</label>
                        <div class="list-group small">
                            <a href="<?= url('/user/' . $MyDetail['nick'] . '/cover') ?>" class="list-group-item list-group-item-action py-2" style="background: transparent;"><i class="fa fa-picture-o text-muted mr-2"></i> Ảnh bìa (Cover)</a>
                            <a href="<?= url('/user/' . $MyDetail['nick'] . '/waifu-header') ?>" class="list-group-item list-group-item-action py-2" style="background: transparent;"><i class="fa fa-header text-muted mr-2"></i> Ảnh trên header</a>
                            <a href="<?= url('/user/' . $MyDetail['nick'] . '/waifu-rleft') ?>" class="list-group-item list-group-item-action py-2" style="background: transparent;"><i class="fa fa-align-left text-muted mr-2"></i> Background trái</a>
                            <a href="<?= url('/user/' . $MyDetail['nick'] . '/waifu-rright') ?>" class="list-group-item list-group-item-action py-2" style="background: transparent;"><i class="fa fa-align-right text-muted mr-2"></i> Background phải</a>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <button type="submit" class="btn btn-primary px-4 py-2 font-weight-bold" style="background:var(--accent-gradient); border:none; border-radius: 20px;"><i class="fa fa-save mr-1"></i> Lưu thay đổi</button>

            <?php elseif (in_array($action, ['avatar', 'cover', 'waifu-header', 'waifu-rleft', 'waifu-rright'])): ?>
                <div class="text-center p-5 border rounded" style="background: var(--surface-muted); border-style: dashed !important;">
                    <i class="fa fa-cloud-upload fa-3x text-muted mb-3"></i>
                    <h5 class="mb-3">Tải lên <?php 
                        switch ($action) {
                            case 'avatar': echo 'Ảnh đại diện'; break;
                            case 'cover': echo 'Ảnh bìa (Cover)'; break;
                            case 'waifu-header': echo 'Ảnh Header'; break;
                            case 'waifu-rleft': echo 'Background Trái'; break;
                            case 'waifu-rright': echo 'Background Phải'; break;
                        } ?></h5>
                    <input style="display:none" type="file" id="SelectImage" accept="image/*">
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <input type="hidden" name="<?= _e($action) ?>" id="AvtCoverValue" value="" />
                    <button type="button" id="AvtCoverUpload" class="btn btn-primary px-4 py-2 font-weight-bold rounded-pill shadow-sm"><i class="fa fa-folder-open-o mr-1"></i> Chọn tệp ảnh</button>
                    <p class="text-muted small mt-3 mb-0">Hỗ trợ JPG, PNG, GIF. Kích thước tối đa 5MB.</p>
                </div>
                
                <script src="<?= $this->asset('/templates' . get_template() . '/public/js/upload-nosineup.js') ?>"></script>
                <script>
                    document.querySelector("#AvtCoverUpload").onclick = function () { document.querySelector("#SelectImage").click(); };
                    nosineup("#SelectImage", {
                        loading: function (load) {
                            document.querySelector("#AvtCoverUpload").innerHTML = '<i class="fa fa-spin fa-spinner mr-1"></i> Đang tải lên... ' + load;
                        },
                        loaded: function (link, size, type, time) {
                            $("#AvtCoverValue").val(link);
                            document.getElementById("form").submit();
                        }
                    });
                </script>

            <?php elseif ($action == 'password'): ?>
                <div style="max-width: 500px; margin: 0 auto;">
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-muted small text-uppercase">Mật khẩu hiện tại</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text bg-light border-right-0"><i class="fa fa-unlock-alt text-muted"></i></span></div>
                            <input class="form-control border-left-0 pl-2" type="password" name="old_password" placeholder="••••••••" required />
                        </div>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-muted small text-uppercase">Mật khẩu mới</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text bg-light border-right-0"><i class="fa fa-key text-muted"></i></span></div>
                            <input class="form-control border-left-0 pl-2" type="password" name="new_password" placeholder="••••••••" required />
                        </div>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-muted small text-uppercase">Xác nhận mật khẩu mới</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text bg-light border-right-0"><i class="fa fa-check-circle-o text-muted"></i></span></div>
                            <input class="form-control border-left-0 pl-2" type="password" name="re_password" placeholder="••••••••" required />
                        </div>
                    </div>
                    
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold" style="background:var(--accent-gradient); border:none; border-radius: 20px;"><i class="fa fa-shield mr-1"></i> Đổi mật khẩu</button>
                </div>

            <?php else: ?>
                <?php if ($count_blocked > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="background: transparent;">
                            <thead class="bg-light">
                                <tr>
                                    <th width="50" class="text-center border-0"><i class="fa fa-check-square-o"></i></th>
                                    <th class="border-0 text-muted small text-uppercase">Thành viên bị chặn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($blocklist as $UserDetail): ?>
                                <tr>
                                    <td class="text-center">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="chk_<?= $UserDetail['nick'] ?>" name="block[]" value="<?= $UserDetail['nick'] ?>">
                                            <label class="custom-control-label" for="chk_<?= $UserDetail['nick'] ?>"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= getAvtUser($UserDetail) ?>" class="rounded-circle" width="30" height="30">
                                            <div>
                                                <div class="font-weight-bold"><?= RoleColor($UserDetail) ?></div>
                                                <div class="small text-muted">Level: <?= $UserDetail['level'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                        <button type="submit" class="btn btn-success px-4 py-2 font-weight-bold rounded-pill"><i class="fa fa-unlock mr-1"></i> Bỏ chặn các mục đã chọn</button>
                    </div>
                <?php else: ?>
                    <div class="p-5 text-center text-muted">
                        <i class="fa fa-check-circle-o fa-3x mb-3 text-success"></i>
                        <h5>Danh sách chặn trống</h5>
                        <p class="mb-0">Bạn chưa chặn bất kỳ thành viên nào.</p>
                    </div>
                <?php endif ?>
            <?php endif ?>
        </form>
    </div>
</div>