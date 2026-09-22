<?php $this->layout('layout'); ?>

<div class="dw-breadcrumb" itemscope="itemscope" itemtype="https://schema.org/BreadcrumbList">
    <?php if ($HaveCategoryDetail): ?>
        <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="<?= url('/articles') ?>" itemprop="item"><i class="fa fa-home"></i> <span itemprop="name">Diễn đàn</span></a>
            <meta itemprop="position" content="1" />
        </span>
        <span class="dw-breadcrumb-sep"><i class="fa fa-angle-right"></i></span>
        <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="<?= url('/index.html?category=' . $CategoryDetail['id']) ?>" itemprop="item"><span itemprop="name"><?= $CategoryDetail['name'] ?></span></a>
            <meta itemprop="position" content="2" />
        </span>
        <span class="dw-breadcrumb-sep"><i class="fa fa-angle-right"></i></span>
        <span class="dw-breadcrumb-current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span itemprop="name">Tạo chủ đề mới</span>
            <meta itemprop="position" content="3" />
        </span>
    <?php else: ?>
        <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="<?= url('/manager') ?>" itemprop="item"><i class="fa fa-cog"></i> <span itemprop="name">Bảng quản trị</span></a>
            <meta itemprop="position" content="1" />
        </span>
        <span class="dw-breadcrumb-sep"><i class="fa fa-angle-right"></i></span>
        <span class="dw-breadcrumb-current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span itemprop="name">Tạo chủ đề mới</span>
            <meta itemprop="position" content="2" />
        </span>
    <?php endif ?>
</div>

<div class="dw-card mt-3">
    <div class="dw-card-header">
        <h3 class="dw-card-title"><i class="fa fa-pencil-square-o"></i> Tạo Chủ Đề Mới</h3>
    </div>
    
    <div class="dw-card-body">
        <?php if ($error): ?>
            <div class="dw-alert dw-alert-danger mb-3"><?= _e($error) ?></div>
        <?php endif ?>
        
        <form name="form" action method="post">
    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <div class="dw-form-group">
                <label class="dw-label">Tiêu đề bài viết <span class="text-danger">*</span></label>
                <input value="<?= $inputTitle ?? '' ?>" class="dw-input" type="text" name="title" maxlength="300" placeholder="Nhập tiêu đề thật ngắn gọn và súc tích..." required>
            </div>
            
            <?php if (!$HaveCategoryDetail): ?>
            <div class="dw-form-group">
                <label class="dw-label">Chuyên mục <span class="text-danger">*</span></label>
                <select name="category" class="dw-input dw-select">
                    <?php foreach ($CategoryList as $CategoryItem): ?>
                    <option value="<?= $CategoryItem['id'] ?>"><?= $CategoryItem['name'] ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <?php endif ?>
            
            <div class="dw-form-group">
                <label class="dw-label">Nội dung <span class="text-danger">*</span></label>
                <div class="mb-2" style="border: 1px solid var(--border); border-bottom: none; border-radius: var(--radius-sm) var(--radius-sm) 0 0;">
                    <?php include (dirname(dirname(__FILE__)) . '/toolbar.php') ?>
                </div>
                <textarea class="dw-textarea dw-input" name="content" rows="15" placeholder="Nội dung bài viết..." style="border-radius: 0 0 var(--radius-sm) var(--radius-sm);" required><?= $inputContent ?? '' ?></textarea>
            </div>

            <div class="dw-form-group">
                <label class="dw-label"><i class="fa fa-tags"></i> Nhãn bài viết (Tags)</label>
                <input value="<?= $inputTags ?? '' ?>" class="dw-input" type="text" name="tags" placeholder="Nhập các thẻ tags, cách nhau bởi dấu phẩy (vd: php, lập trình, mvc)...">
                <small class="text-muted d-block mt-1">Giúp phân loại nội dung linh hoạt hơn thay vì chỉ dùng Chuyên mục.</small>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white font-weight-bold"><i class="fa fa-search"></i> Tối ưu hoá SEO</div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Meta Title</label>
                        <input type="text" name="meta_title" class="form-control" placeholder="Để trống sẽ lấy Tiêu đề bài viết" value="">
                    </div>
                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea name="meta_desc" class="form-control" rows="2" placeholder="Mô tả ngắn cho công cụ tìm kiếm..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Meta Keywords</label>
                        <input type="text" name="meta_keywords" class="form-control" placeholder="Từ khoá, phân cách bằng dấu phẩy">
                    </div>
                </div>
            </div>
            
            <div class="mt-4 text-right">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <a href="javascript:history.back()" class="dw-btn dw-btn-ghost mr-2">Hủy</a>
                <button type="submit" class="dw-btn dw-btn-primary"><i class="fa fa-paper-plane mr-2"></i> Xuất bản bài viết</button>
            </div>
        </form>
    </div>
</div>