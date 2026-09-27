<?php
$is_edit = ($mode === 'edit');
$this->layout('manager/layout', [
    'page_title' => $is_edit ? 'Sửa Trang Tĩnh' : 'Thêm Trang Tĩnh', 
    'panelActive' => 'static_pages'
]);
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/trumbowyg.min.css">
<style>
    .trumbowyg-box, .trumbowyg-editor {
        min-height: 400px;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <?php if (isset($_COOKIE['manager_sp_error'])): ?>
            <div class="alert alert-danger"><?= $_COOKIE['manager_sp_error'] ?></div>
            <?php setcookie('manager_sp_error', '', time() - 3600, '/'); ?>
        <?php endif; ?>

        <div class="dw-card mb-4">
            <div class="dw-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <a href="<?= url('/manager/static_pages') ?>" class="text-decoration-none text-muted"><i class="fa fa-arrow-left"></i> Trang tĩnh</a> / 
                    <?= $is_edit ? 'Sửa' : 'Thêm mới' ?>
                </h5>
            </div>
            
            <div class="dw-card-body">
                <form action="<?= $is_edit ? url('/manager/static_pages/edit?slug=' . urlencode($slug)) : url('/manager/static_pages/add') ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    
                    <div class="form-group mb-3">
                        <label for="title" class="form-label">Tiêu đề trang</label>
                        <input type="text" name="title" id="title" class="form-control" value="<?= $is_edit ? htmlspecialchars($title) : '' ?>" required placeholder="VD: Chính sách bảo mật">
                    </div>

                    <div class="form-group mb-3">
                        <label for="slug" class="form-label">Slug (Tên file)</label>
                        <input type="text" name="slug" id="slug" class="form-control" value="<?= $is_edit ? htmlspecialchars($slug) : '' ?>" <?= $is_edit ? 'readonly' : 'required' ?> placeholder="VD: chinh-sach-bao-mat">
                        <small class="text-muted">Slug sẽ được tự động tạo từ tiêu đề. Tên file sẽ là <code>slug.php</code>.</small>
                    </div>

                    <div class="form-group mb-4">
                        <label for="content" class="form-label">Nội dung</label>
                        <textarea name="content" id="editor" class="form-control"><?= $is_edit ? htmlspecialchars($content) : '' ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Lưu lại</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/trumbowyg.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/langs/vi.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/plugins/upload/trumbowyg.upload.min.js"></script>

<script>
    function string_to_slug(str) {
        str = str.replace(/^\s+|\s+$/g, '');
        str = str.toLowerCase();

        var from = "àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ·/_,:;";
        var to   = "aaaaaaaaaaaaaaaaaeeeeeeeeeeeiiiiiooooooooooooooooouuuuuuuuuuuyyyyyd------";
        for (var i = 0, l = from.length; i < l; i++) {
            str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
        }

        str = str.replace(/[^a-z0-9 -]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        return str;
    }

    function autoSlug() {
        var title = $('#title'), slug = $('#slug');
        if (title.val().trim().length === 0) {
            if(!slug.prop('readonly')) {
                slug.val('');
            }
            return;
        }
        
        if (slug.val().length) return;
        
        if (!slug.prop('readonly')) {
            slug.val(string_to_slug(title.val()));
        }
    }

    jQuery(function($){
        $('#title').blur(autoSlug);
        $('#slug').blur(autoSlug);

        // Khởi tạo Trumbowyg
        $('#editor').trumbowyg({
            lang: 'vi',
            btnsDef: {
                image: {
                    dropdown: ['insertImage', 'upload'],
                    ico: 'insertImage'
                }
            },
            btns: [
                ['viewHTML'],
                ['formatting'],
                ['strong', 'em', 'del'],
                ['superscript', 'subscript'],
                ['link'],
                ['image'], // Plugin upload sẽ xuất hiện trong dropdown này
                ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
                ['unorderedList', 'orderedList'],
                ['horizontalRule'],
                ['removeformat'],
                ['fullscreen']
            ],
            plugins: {
                upload: {
                    serverPath: typeof TelegramUploadUrl !== 'undefined' ? TelegramUploadUrl : '',
                    fileFieldName: 'file',
                    headers: {},
                    urlPropertyName: 'url' // Phụ thuộc vào API trả về, cần điều chỉnh nếu API trả cấu trúc khác
                }
            }
        });
    });
</script>
