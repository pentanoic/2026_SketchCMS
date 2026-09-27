<?php $this->layout('layout'); ?>
<div class="dw-card mb-4">
    <div class="dw-card-header">
        <h3 class="dw-card-title"><?= $page_title ?></h3>
    </div>
    <div class="dw-card-body" id="content"> <?= $content ?> </div>
</div>
<div class="d-flex justify-content-between align-items-center px-2">
    <div class="nav-btn-wrapper"> <span class="btn btn-secondary"><?php $this->thePrev('%s', '<i class="fa fa-angle-left" aria-hidden="true"></i> END'); ?></span> </div>
    <div class="nav-btn-wrapper"> <span class="btn btn-secondary"><?php $this->theNext('%s', 'END <i class="fa fa-angle-right" aria-hidden="true"></i>'); ?></span> </div>
</div>