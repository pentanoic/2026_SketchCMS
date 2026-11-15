<?php $this->layout('layout'); ?>

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
        <span itemprop="name">Thông tin tập tin</span>
        <meta itemprop="position" content="3" />
    </span>
</div>

<div class="dw-card mt-3">
    <div class="dw-card-header d-flex align-items-center justify-content-between">
        <h3 class="dw-card-title"><i class="fa fa-file-text-o"></i> Thông tin tập tin</h3>
        <?php if ($CanDelete): ?>
        <a href="?action=delete" class="dw-btn dw-btn-danger dw-btn-sm" onclick="return confirm('XÁC NHẬN XÓA TẬP TIN NÀY?')"><i class="fa fa-trash"></i> Xóa tập tin</a>
        <?php endif ?>
    </div>
    
    <div class="dw-card-body p-0">
        <div class="list-group list-group-flush">
            <div class="list-group-item py-3">
                <div class="d-flex align-items-center mb-2">
                    <div style="width: 48px; height: 48px; border-radius: 8px; background: var(--surface-muted); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--fg-secondary); font-size: 20px; margin-right: 15px;">
                        <i class="fa fa-<?= checkExtension($FileDetail['filename'] ?? '') ?>"></i>
                    </div>
                    <div>
                        <h4 style="font-size: 16px; margin: 0; font-weight: 600; color: var(--fg);"><?= $FileDetail['filename'] ?></h4>
                        <div style="font-size: 12px; color: var(--fg-muted); margin-top: 4px;">
                            <i class="fa fa-info-circle"></i> Dung lượng: <b><?= FileSizeFormat($FileDetail['filesize']) ?></b> &bull; 
                            <i class="fa fa-calendar"></i> Ngày đăng: <b><?= ago($PostDetail['time']) ?></b>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="list-group-item py-3" style="font-size: 14px;">
                <div class="row mb-2">
                    <div class="col-sm-4 text-muted"><i class="fa fa-bars"></i> Danh mục:</div>
                    <div class="col-sm-8"><a href="<?= url('/index.html?category=' . $CategoryDetail['id']) ?>"><b><?= $CategoryDetail['name'] ?></b></a></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-4 text-muted"><i class="fa fa-book"></i> Bài viết:</div>
                    <div class="col-sm-8"><a href="<?= url('/articles/' . $PostDetail['id'] . '-' . $PostDetail['slug'] . '.html') ?>"><b><?= $PostDetail['title'] ?></b></a></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-4 text-muted"><i class="fa fa-user-circle"></i> Tác giả:</div>
                    <div class="col-sm-8"><?= RoleColor($UserDetail) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-4 text-muted"><i class="fa fa-usd"></i> Nhóm tệp:</div>
                    <div class="col-sm-8">
                        <?php if ($FileDetail['status'] == 'public'): ?>
                            <span class="text-success font-weight-bold"><i class="fa fa-unlock-alt"></i> Miễn phí</span>
                        <?php else: ?>
                            <span class="text-warning font-weight-bold"><i class="fa fa-diamond"></i> Trả phí, Giá: <?= number_format($FileDetail['price']) ?> xu</span>
                        <?php endif ?>
                    </div>
                </div>
                <?php if ($BoughtCount > 0): ?>
                <div class="row mt-3 pt-3" style="border-top: 1px dashed var(--border);">
                    <div class="col-sm-4 text-muted"><i class="fa fa-address-book-o"></i> Đã mua (<?= _e($BoughtCount) ?>):</div>
                    <div class="col-sm-8">
                        <div class="d-flex flex-wrap gap-2" style="gap: 5px;">
                            <?php foreach ($PurchaserList as $PurchaserDetail): ?>
                            <a href="/user/<?= $PurchaserDetail['nick'] ?>" title="<?= $PurchaserDetail['name'] ?>">
                                <img src="<?= getAvtUser($PurchaserDetail) ?>" loading="lazy" class="lozad" width="28" height="28" style="border-radius: 50%; object-fit: cover; border: 1px solid var(--border);">
                            </a>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
                <?php endif ?>
            </div>
        </div>
    </div>
    
    <div class="dw-card-body" style="background: var(--surface-muted); border-top: 1px solid var(--border);">
        <?php if ($CanDownload): ?>
            <?php if ($FileDetail['type'] == 'ipfs'): ?>
                <div id="dai"></div>
                <button type="button" class="dw-btn dw-btn-primary dw-btn-block" onclick="downloadURL('<?= $FileDetail['filename'] ?>', '<?= $FileDetail['filecate'] ?>', '<?= $FileDetail['passphrase'] ?>')">
                    <i class="fa fa-download mr-1"></i> Tải xuống tập tin
                </button>
            <?php else: ?>
                <?php 
                    $url = '';
                    if ($FileDetail['id'] < 99) {
                        $url = $Stockage['xtgem'] . '/' . $PostDetail['id'] . '/' . $FileDetail['filecate'] . '/' . $FileDetail['filename'];
                    } elseif ($FileDetail['id'] >= 100 && $FileDetail['id'] <= 174) {
                        $url = $Stockage['gdrive'] . '/F' . $FileDetail['filecate'] . '_' . $FileDetail['filename'];
                    } else {
                        $url = $Stockage['telegram'] . '/' . $FileDetail['filecate'] . '/' . $FileDetail['filename'];
                    }
                ?>
                <a href="<?= _e($url) ?>" class="dw-btn dw-btn-primary dw-btn-block text-center">
                    <i class="fa fa-download mr-1"></i> Tải xuống tập tin
                </a>
            <?php endif ?>
        <?php else: ?>
            <?php if (!$isLogin): ?>
                <div class="dw-alert dw-alert-warning mb-0 text-center"><i class="fa fa-exclamation-triangle"></i> Vui lòng đăng nhập để tải xuống tập tin!</div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="dw-alert dw-alert-danger mb-3"><?= _e($error) ?></div>
                <?php endif ?>
                <form method="POST" class="text-center">
    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <div class="mb-3">Bạn có muốn mua tập tin này không?</div>
                    <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                    <button type="submit" class="dw-btn dw-btn-success dw-btn-block">
                        <i class="fa fa-shopping-basket"></i> Đồng ý mua
                    </button>
                </form>
            <?php endif ?>
        <?php endif ?>
    </div>
</div>

<?php if (isset($FileDetail['type']) && $FileDetail['type'] == 'ipfs'): ?>
<script>
    function readfile(n) {
        return new Promise((e, t) => {
            var o = new FileReader;
            o.onload = () => {
                e(o.result)
            }, o.readAsArrayBuffer(n)
        })
    }
    async function decryptfile(e, t, o) {
        var n = await readfile(e).catch(function(e) { console.error(e) }),
            r = new Uint8Array(n),
            e = new TextEncoder("utf-8").encode(o),
            n = r.slice(8, 16),
            e = await window.crypto.subtle.importKey("raw", e, { name: "PBKDF2" }, !1, ["deriveBits"]).catch(function(e) { console.error(e) });
        e = await window.crypto.subtle.deriveBits({ name: "PBKDF2", salt: n, iterations: 1e4, hash: "SHA-256" }, e, 384).catch(function(e) { console.error(e) });
        e = new Uint8Array(e), keybytes = e.slice(0, 32), ivbytes = e.slice(32), r = r.slice(16);
        e = await window.crypto.subtle.importKey("raw", keybytes, { name: "AES-CBC", length: 256 }, !1, ["decrypt"]).catch(function(e) { console.error(e) });
        r = await window.crypto.subtle.decrypt({ name: "AES-CBC", iv: ivbytes }, e, r).catch(function(e) { console.error(e) });
        r = new Uint8Array(r);
        r = new Blob([r], { type: "application/download" });
        const c = document.createElement("a");
        c.href = URL.createObjectURL(r), c.download = t, c.click()
    }
    async function downloadURL(t, e, o) {
        document.getElementById("dai").insertAdjacentHTML("beforeend", '<center><i class="fa fa-spinner fa-spin fa-2x mb-3 text-primary"></i></center>');
        fetch("<?= $Stockage['ipfs'] ?>" + e).then(e => e.blob()).then(e => {
            decryptfile(e, t, o), document.getElementById("dai").innerHTML = ""
        }).catch(console.error)
    }
</script>
<?php endif ?>