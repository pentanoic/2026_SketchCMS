function nosineup(f, ob) {
    var files = document.querySelector(f); 
    files.onchange = function () {
        var file = this.files[0]; 
        if (file && file.type.match(/image.*/)) {
            var fd = new FormData(); 
            fd.append("document", file); 

            if(typeof ob.loading === 'function') ob.loading('...');

            var upUrl = typeof TelegramUploadUrl !== 'undefined' ? TelegramUploadUrl : 'https://nosineup.stockage.workers.dev/upload';
            var downUrl = typeof TelegramDownloadBaseUrl !== 'undefined' ? TelegramDownloadBaseUrl : 'https://nosineup.stockage.workers.dev/download';

            fetch(upUrl, {
                method: 'POST',
                body: fd
            })
            .then(response => response.json())
            .then(data => {
                if (data && data.status === 'success' && data.url) { 
                    // Support direct mode output which returns exact URL
                    if(typeof ob.loaded === 'function') ob.loaded(data.url);
                } else if (data && data.file_id) { 
                    // Cloudflare worker output
                    var link = downUrl + "/" + data.file_id + "/" + encodeURIComponent(file.name);
                    if(typeof ob.loaded === 'function') ob.loaded(link);
                } else {
                    window.alert('Lỗi: Tải lên thất bại, không nhận được dữ liệu trả về');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.alert('Lỗi kết nối mạng hoặc bị chặn (CORS)');
            });
        } else { 
            window.alert('Chỉ cho phép chọn ảnh');
        }
    }
}