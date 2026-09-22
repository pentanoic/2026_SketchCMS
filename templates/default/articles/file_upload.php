<?php $this->layout('layout'); ?>

<!-- Breadcrumbs -->
<div class="dw-breadcrumb" itemscope="itemscope" itemtype="https://schema.org/BreadcrumbList">
    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="<?= url('/') ?>" itemprop="item"><i class="fa fa-home"></i> <span itemprop="name">Diễn đàn</span></a>
        <meta itemprop="position" content="1" />
    </span>
    <span class="dw-breadcrumb-sep"><i class="fa fa-angle-right"></i></span>
    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="<?= url('/index.html?category=' . $CategoryDetail['id']) ?>" itemprop="item"><span itemprop="name"><?= $CategoryDetail['name'] ?></span></a>
        <meta itemprop="position" content="2" />
    </span>
    <span class="dw-breadcrumb-sep"><i class="fa fa-angle-right"></i></span>
    <span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="<?= url('/articles/' . $PostDetail['id'] . '-' . $PostDetail['slug'] . '.html') ?>" itemprop="item"><span itemprop="name"><?= $PostDetail['title'] ?></span></a>
        <meta itemprop="position" content="3" />
    </span>
    <span class="dw-breadcrumb-sep"><i class="fa fa-angle-right"></i></span>
    <span class="dw-breadcrumb-current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <span itemprop="name">Tải lên tập tin</span>
        <meta itemprop="position" content="4" />
    </span>
</div>

<div class="dw-card mt-3">
    <div class="dw-card-header">
        <h3 class="dw-card-title"><i class="fa fa-cloud-upload"></i> Tải lên tập tin mới</h3>
    </div>
    
    <div class="dw-card-body">
        <?php if ($error): ?>
        <div class="dw-alert dw-alert-warning mb-3"><i class="fa fa-exclamation-triangle"></i> <?= _e($error) ?></div>
        <?php endif ?>
        
        <form id="form" method="post" action="">
    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <div id="dai" class="mb-3 text-center"></div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="dw-form-group">
                        <label class="dw-label">Trạng thái chia sẻ</label>
                        <select name="status" class="dw-input dw-select">
                            <option value="private">Chỉ người dùng đã mua mới có thể tải về</option>
                            <option value="public">Miễn phí (Ai cũng có thể tải về)</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="dw-form-group">
                        <label class="dw-label">ID File bản cập nhật trước (tùy chọn)</label>
                        <input type="number" name="condition" min="0" value="0" class="dw-input" placeholder="Nếu không có thì để 0" />
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="dw-form-group">
                        <label class="dw-label">Giá gốc của file (xu)</label>
                        <input type="number" name="price" min="0" max="50000" value="10" class="dw-input" />
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="dw-form-group">
                        <label class="dw-label">Giá sau khi giảm (xu)</label>
                        <input type="number" name="saleoff" min="0" max="50000" value="10" class="dw-input" />
                        <div class="text-muted mt-1" style="font-size: 11px;">* Áp dụng khi tải xuống bản cập nhật của file ID trước đó.</div>
                    </div>
                </div>
            </div>
            
            <div class="dw-form-group text-center mt-4 p-4" style="border: 2px dashed var(--border); border-radius: var(--radius-md); background: var(--surface-muted);">
                <i class="fa fa-file-archive-o fa-3x text-muted mb-3"></i><br>
                <input id="uploadfile" type="file" name="file" class="d-inline-block" style="max-width: 100%;" />
            </div>
            
            <input id="filename" name="filename" type="hidden" value="" />
            <input id="filesize" name="filesize" type="hidden" value="" />
            <input id="fileidtg" name="filecate" type="hidden" value="" />
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            
            <div class="mt-4">
                <button type="button" id="buttonD" class="dw-btn dw-btn-primary dw-btn-block dw-btn-lg" onclick="frUpload()">
                    <i class="fa fa-cloud-upload mr-2"></i> Bắt đầu tải lên (Tối đa <?= FileSizeFormat($MaxFileSizeAllow) ?>)
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    var maxFileSizeAllow = <?= $MaxFileSizeAllow ?? 0 ?>,
        UrlUpload = '<?= $UploadTo ?? '' ?>';
</script>
<script src="<?= url('/templates' . get_template() . '/public/js/upload-telegram.js') ?>"></script>