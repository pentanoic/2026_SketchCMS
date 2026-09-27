<?php
$this->layout('layout');
?>
<style>
    .org-chart {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
        padding: 20px;
    }

    .org-node {
        background: #3498db;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: bold;
        text-align: center;
        width: 200px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    .org-node::after {
        content: '';
        position: absolute;
        bottom: -20px;
        left: 50%;
        width: 2px;
        height: 20px;
        background: #3498db;
        transform: translateX(-50%);
    }

    .org-node:last-child::after {
        display: none;
    }

    .org-dev {
        background: #e74c3c;
    }

    .org-dev::after {
        background: #e74c3c;
    }

    .org-admin {
        background: #e67e22;
    }

    .org-admin::after {
        background: #e67e22;
    }

    .org-smod {
        background: #9b59b6;
    }

    .org-smod::after {
        background: #9b59b6;
    }

    .org-mod {
        background: #1abc9c;
    }

    .org-mod::after {
        background: #1abc9c;
    }

    .org-contrib {
        background: #2ecc71;
    }

    .org-contrib::after {
        background: #2ecc71;
    }

    .org-member {
        background: #95a5a6;
    }
</style>

<div class="dw-card mb-3">
    <div class="dw-card-header">
        <h3 class="dw-card-title"><i class="fa fa-info-circle" aria-hidden="true"></i> <?= $this->e($page_title) ?></h3>
    </div>
    <div class="dw-card-body" style="line-height: 1.6;">
        <strong style="display: block; margin-top: 15px; margin-bottom: 5px; font-size: 1rem;">Giới thiệu Cộng đồng</strong>
        <p>Chào mừng bạn đến với <strong>Cộng đồng Web Developer - Dorew.ovh</strong>. Đây là nơi hội tụ, kết nối và chia sẻ kiến thức về lập trình, phát triển web cũng như các công nghệ liên quan.</p>

        <strong style="display: block; margin-top: 15px; margin-bottom: 5px; font-size: 1rem;">Cam kết của chúng tôi</strong>
        <p>Chúng tôi hoạt động với <strong>cam kết bảo vệ không gian an toàn mạng</strong>, đảm bảo tính minh bạch, sạch sẽ và an toàn tuyệt đối cho người tham gia, mang lại một môi trường văn minh nhất.</p>

        <strong style="display: block; margin-top: 15px; margin-bottom: 5px; font-size: 1rem;">Cơ cấu Tổ chức</strong>
        <div class="org-chart">
            <div class="org-node org-dev">Developer<br><small>(Level 127)</small></div>
            <div class="org-node org-admin">Administrator<br><small>(Level 126)</small></div>
            <div class="org-node org-smod">Super Moderator<br><small>(Level 122)</small></div>
            <div class="org-node org-mod">Moderator<br><small>(Level 121)</small></div>
            <div class="org-node org-contrib">Contributor<br><small>(Level 120)</small></div>
            <div class="org-node org-member">Member<br><small>(Level 0-100)</small></div>
        </div>
    </div>
</div>