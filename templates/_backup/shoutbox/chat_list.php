<?php foreach ($ChatList as $ChatDetail): ?>
    <div class="dw-post-wrap mb-3 shadow-sm border-0" id="<?= $ChatDetail['id'] ?>" style="border-radius: 12px; background: var(--surface);">
        <div class="dw-post-layout">
            <!-- Cột người đăng -->
            <div class="dw-post-author-col" style="background: transparent;">
                <div class="dw-post-author-sticky text-center">
                    <div class="position-relative d-none d-md-inline-block">
                        <img class="dw-avt rounded-circle shadow-sm" src="<?= getAvtUser($ChatDetail['UserDetail']) ?>" style="width: 50px; height: 50px; object-fit: cover; border: 2px solid white;" />
                        <?php if ($ChatDetail['UserDetail']['online_status'] === 'online'): ?>
                            <span class="position-absolute bottom-0 right-0 rounded-circle border border-white" style="width: 12px; height: 12px; background-color: #28a745; right: 0; bottom: 0;"></span>
                        <?php endif ?>
                    </div>
                    <div class="dw-post-author-name mt-md-2 mt-0" style="font-size: 13px; word-wrap: break-word;">
                        <?= RoleColor($ChatDetail['UserDetail']) ?>
                    </div>
                    <div class="mt-1 ml-md-0 ml-2">
                        <?php if (!isset($user) || (isset($user) && $user['id'] != $ChatDetail['UserDetail']['id'])): ?>
                        <a href="javascript:window.tag('@<?= $ChatDetail['UserDetail']['nick'] ?> ', '')" class="badge badge-light shadow-sm" title="Gắn thẻ" style="color: var(--accent);"><i class="fa fa-at"></i> Tag</a>
                        <a href="javascript:window.tag('@[module=shoutbox;quote=<?= $ChatDetail['id'] ?>] ', '')" class="badge badge-light shadow-sm" title="Trích dẫn" style="color: var(--accent);"><i class="fa fa-quote-left"></i> Quote</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Cột nội dung -->
            <div class="dw-post-content-col">
                <div class="dw-post-time-bar">
                    <span><i class="fa fa-clock-o"></i> <?= ago($ChatDetail['time']) ?></span>
                </div>
                <div class="chat-content text-dark p-3 pt-2" id="content" style="word-wrap: break-word; font-size: 14px; line-height: 1.6;">
                    <?= $ChatDetail['comment'] ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach ?>