<?php
$this->layout('layout', ['page_title' => $page_title ?? 'Headless API Documentation']);
?>

<div class="dw-card">
    <div class="dw-card-header">
        <h3 class="dw-card-title"><i class="fa fa-book"></i> Tài liệu API Headless CMS</h3>
    </div>
    <div class="dw-card-body">
        <p>Đây là tài liệu hướng dẫn sử dụng Headless API của hệ thống. Tất cả các endpoint đều trả về định dạng <code>JSON</code>.</p>
        
        <h4>1. Cơ chế xác thực (Authentication)</h4>
        <p>Hệ thống tự động sử dụng Cookie (phiên đăng nhập hiện tại trên trình duyệt) để xác thực. Hoặc bạn có thể truyền Token qua HTTP Header đối với các ứng dụng bên thứ ba:</p>
        <pre><code>Authorization: Bearer &lt;TOKEN&gt;</code></pre>
        
        <hr>

        <h4>2. Các Endpoints có sẵn</h4>

        <div class="mb-4">
            <h5><span class="badge badge-primary">GET</span> /api/v1/ping</h5>
            <p class="text-muted">Kiểm tra trạng thái kết nối tới API.</p>
            <h6>Response:</h6>
<pre><code class="language-json">{
    "status": "success",
    "message": "pong",
    "time": 1726830500
}</code></pre>
        </div>

        <div class="mb-4">
            <h5><span class="badge badge-primary">GET</span> /api/v1/articles</h5>
            <p class="text-muted">Lấy danh sách các bài viết mới nhất trên diễn đàn.</p>
            <p><strong>Tham số (Query):</strong> <code>?page={số_trang}</code></p>
            <h6>Response:</h6>
<pre><code class="language-json">{
    "status": "success",
    "data": [
        {
            "id": 10,
            "name": "Tiêu đề bài viết",
            "text": "Nội dung...",
            "time": "1234567890",
            ...
        }
    ],
    "page": 1,
    "limit": 20
}</code></pre>
        </div>

        <div class="mb-4">
            <h5><span class="badge badge-primary">GET</span> /api/v1/articles/{id}</h5>
            <p class="text-muted">Lấy chi tiết một bài viết cụ thể.</p>
        </div>

        <div class="mb-4">
            <h5><span class="badge badge-primary">GET</span> /api/v1/forums</h5>
            <p class="text-muted">Lấy danh sách các chuyên mục, thư mục của diễn đàn.</p>
        </div>
    </div>
</div>
