<?php $this->layout('layout'); ?>
<link href="<?= $this->asset('/templates' . get_template() . '/public/css/in-shoutbox.css') ?>" rel="stylesheet" />

<div class="dw-card mb-4">
    <div class="dw-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h3 class="dw-card-title m-0">
            <i class="fa fa-users" style="color:var(--info)"></i>
            <?php if ($UserListConfig['order_by'] == 'level'): ?>
                <?= _e($page_title) ?>
            <?php else: ?>
                <a href="<?= url('/users') ?>" class="text-dark">Thành viên</a> / <span class="text-primary"><?= _e($page_title) ?></span>
            <?php endif ?>
        </h3>

        <div class="d-flex align-items-center">
            <label class="mr-2 mb-0 small font-weight-bold text-muted text-uppercase d-none d-sm-block">Sắp xếp theo:</label>
            <select class="form-control form-control-sm border-0" id="order_by" style="background: var(--surface-muted); border-radius: 20px; font-weight: bold; width: auto; padding: 0 15px;">
                <?php
                foreach ($UserListConfig['allow_order_by'] as $order_by):
                    $array = [
                        'level' => 'Cấp bậc (Level)',
                        'xu' => 'Top Tài Sản (Xu)',
                        'post' => 'Top Hoạt Động (Post)'
                    ];
                ?>
                    <option value="<?= _e($order_by) ?>" <?= $UserListConfig['order_by'] == $order_by ? 'selected' : '' ?>>
                        <?= $array[$order_by] ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
    </div>

    <div class="dw-card-body p-0">
        <?php foreach ($UserList as $index => $UserDetail): ?>
            <div class="d-flex align-items-center p-3 border-bottom" style="border-color: var(--border) !important;">
                <!-- Avatar -->
                <div class="mr-3 position-relative">
                    <a href="<?= url('/user/' . $UserDetail['nick']) ?>">
                        <img src="<?= getAvtUser($UserDetail) ?>" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover; border: 2px solid var(--surface-muted);">
                    </a>
                    <?php if ($index < 3 && $UserListConfig['order_by'] != 'level'): ?>
                        <div class="position-absolute d-flex align-items-center justify-content-center rounded-circle" style="width: 20px; height: 20px; bottom: -5px; right: -5px; background: <?= $index == 0 ? '#ffc107' : ($index == 1 ? '#e0e0e0' : '#cd7f32') ?>; border: 2px solid var(--surface); font-size: 10px; font-weight: bold; color: #fff;">
                            <?= $index + 1 ?>
                        </div>
                    <?php endif ?>
                </div>

                <!-- Info -->
                <div class="flex-grow-1 min-width-0">
                    <div class="d-flex align-items-center mb-1">
                        <h5 class="m-0 font-weight-bold text-truncate" style="font-size: 1.1rem; line-height: 1.2;">
                            <a href="<?= url('/user/' . $UserDetail['nick']) ?>"><?= RoleColor($UserDetail) ?></a>
                        </h5>
                        <?php if ($isLogin && $user['level'] >= 120 && $user['level'] > $UserDetail['level']): ?>
                            <div class="ml-2 dropdown">
                                <button class="btn btn-sm btn-light p-0 text-muted" type="button" data-toggle="dropdown" style="width: 24px; height: 24px; border-radius: 50%;"><i class="fa fa-ellipsis-v"></i></button>
                                <div class="dropdown-menu dropdown-menu-right shadow-sm border-0">
                                    <a class="dropdown-item text-warning" href="/user/<?= $UserDetail['nick'] ?>/ban"><i class="fa fa-ban mr-2"></i> Khóa tài khoản</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="/manager/user/<?= $UserDetail['nick'] ?>/delete" onclick="return confirm('Xác nhận xóa thành viên này?');"><i class="fa fa-trash mr-2"></i> Xóa vĩnh viễn</a>
                                </div>
                            </div>
                        <?php endif ?>
                    </div>
                    <div class="text-muted small">
                        <?= $UserDetail['UserStatsInfo'] ?>
                    </div>
                </div>

                <!-- Rank / Actions -->
                <div class="ml-3 text-right">
                    <div class="font-weight-bold" style="color: var(--accent);">
                        <?= ForumRank($UserDetail['UserStats']['count_post'] + $UserDetail['UserStats']['count_chapter'] + $UserDetail['UserStats']['count_comment']) ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <?php if ($UserListConfig['order_by'] == 'level'): ?>
        <div class="dw-card-footer bg-transparent text-center border-top-0 pt-3">
            <?= $UserListPaging ?>
        </div>
    <?php endif ?>
</div>

<script>
    document.getElementById('order_by').addEventListener('change', function() {
        let url = new URL(window.location.href);
        url.searchParams.set('order_by', this.value);
        window.location.href = url.href;
    });
</script>