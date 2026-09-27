<?php $this->layout('layout'); ?>

<!-- Breadcrumbs -->
<div class="dw-breadcrumb" itemscope="itemscope" itemtype="https://schema.org/BreadcrumbList">
    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="<?= url('/manager') ?>" itemprop="item"><i class="fa fa-cog"></i> <span itemprop="name">Bảng quản trị</span></a>
        <meta itemprop="position" content="1" />
    </span>
    <span class="dw-breadcrumb-sep"><i class="fa fa-angle-right"></i></span>
    <span class="dw-breadcrumb-current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <span itemprop="name">Quản lý chuyên mục</span>
        <meta itemprop="position" content="2" />
    </span>
</div>

<div class="row mt-3">
    <!-- Cột bên trái: Thêm chuyên mục mới -->
    <div class="col-md-5 mb-4">
        <div class="dw-card h-100 mb-0">
            <div class="dw-card-header">
                <h3 class="dw-card-title"><i class="fa fa-plus-circle"></i> Thêm chuyên mục mới</h3>
            </div>
            
            <div class="dw-card-body">
                <?php if ($error): ?>
                <div class="dw-alert dw-alert-danger mb-3"><?= _e($error) ?></div>
                <?php endif ?>
                
                <form method="post">
    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <div class="dw-form-group">
                        <label class="dw-label">Tên chuyên mục (Tối đa 50 kí tự) <span class="text-danger">*</span></label>
                        <input class="dw-input" type="text" name="name" maxlength="50" placeholder="VD: Lập trình PHP" required>
                    </div>
                    
                    <div class="dw-form-group">
                        <label class="dw-label">Mô tả <span class="text-danger">*</span></label>
                        <textarea class="dw-input" name="content" rows="3" placeholder="Mô tả ngắn gọn về chuyên mục..." required></textarea>
                    </div>
                    
                    <div class="dw-form-group">
                        <label class="dw-label">Từ khoá (Keywords) <span class="text-danger">*</span></label>
                        <textarea class="dw-input" name="keyword" rows="2" placeholder="VD: php, lap-trinh, web..." required></textarea>
                    </div>
                    
                    <div class="mt-4">
                        <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                        <button type="submit" class="dw-btn dw-btn-primary dw-btn-block" name="submit" value="create"><i class="fa fa-check"></i> Tạo chuyên mục</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cột bên phải: Danh sách & Xóa chuyên mục -->
    <div class="col-md-7 mb-4">
        <div class="dw-card h-100 mb-0">
            <div class="dw-card-header">
                <h3 class="dw-card-title"><i class="fa fa-list-ul"></i> Danh sách chuyên mục</h3>
            </div>
            
            <div class="dw-card-body">
                <?php if ($CategoryCount > 0): ?>
                <form method="post">
    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <div class="list-group mb-4" style="border-radius: var(--radius-sm); overflow: hidden;">
                        <?php foreach ($CategoryList as $CategoryItem): ?>
                        <label class="list-group-item list-group-item-action d-flex align-items-center py-2" style="cursor: pointer; background: var(--surface-muted); border-color: var(--border); margin-bottom: 4px; border-radius: var(--radius-sm);">
                            <div class="custom-control custom-radio mr-3">
                                <input type="radio" name="category_id" value="<?= $CategoryItem['id'] ?>" class="custom-control-input" required>
                                <span class="custom-control-label"></span>
                            </div>
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-size: 13.5px; font-weight: 600; color: var(--fg);">
                                    <?= $CategoryItem['name'] ?>
                                </div>
                            </div>
                            <span class="dw-badge dw-badge-accent mr-2" title="Số lượng bài viết"><?= $CategoryItem['count_post'] ?? 0 ?> bài</span>
                            <a href="<?= url('/index.html?category=' . $CategoryItem['id']) ?>" class="dw-btn dw-btn-ghost dw-btn-sm px-2" target="_blank" title="Xem chuyên mục"><i class="fa fa-external-link"></i></a>
                        </label>
                        <?php endforeach ?>
                    </div>
                    
                    <div class="dw-alert dw-alert-warning mb-3">
                        <div>
                            <i class="fa fa-exclamation-triangle"></i> 
                            <strong>Lưu ý quan trọng:</strong> Thao tác xóa sẽ dọn sạch vĩnh viễn toàn bộ bài viết, chương, bình luận và tập tin đính kèm bên trong chuyên mục. Không thể hoàn tác!
                        </div>
                    </div>
                    
                    <div>
                        <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                        <button type="submit" class="dw-btn dw-btn-danger dw-btn-block" name="submit" value="delete" onclick="return confirm('Bạn có CHẮC CHẮN muốn xóa chuyên mục này cùng toàn bộ dữ liệu bên trong?');">
                            <i class="fa fa-trash"></i> Xóa chuyên mục đã chọn
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="dw-empty-state">
                    <i class="fa fa-folder-open-o"></i>
                    <p>Chưa có chuyên mục nào. Hãy tạo chuyên mục đầu tiên ở bên trái.</p>
                </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>