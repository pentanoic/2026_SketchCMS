document.addEventListener("DOMContentLoaded", function () {
    const toolbars = document.querySelectorAll('.bbcode-toolbar');
    if (toolbars.length === 0) return;

    toolbars.forEach(function (toolbar) {
        // Tìm textarea tương ứng. Ưu tiên trong cùng form, nếu không có thì tìm textarea chung
        let form = toolbar.closest('form');
        let textarea = null;
        if (form) {
            textarea = form.querySelector('textarea[name="content"], textarea#postText');
        }
        if (!textarea) {
            textarea = document.querySelector('textarea[name="content"], textarea#postText');
        }
        if (!textarea) return;

        // 1. Chèn Tag
        toolbar.querySelectorAll('.bb-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.dataset.action === 'toggle') {
                    const target = toolbar.querySelector('.' + this.dataset.target);
                    target.style.display = target.style.display === 'none' ? 'block' : 'none';
                    return;
                }

                if (this.dataset.tag) {
                    const startTag = this.dataset.tag;
                    const endTag = this.dataset.end || '';
                    
                    const startPos = textarea.selectionStart;
                    const endPos = textarea.selectionEnd;
                    const oldContent = textarea.value;
                    const selectedText = oldContent.substring(startPos, endPos);
                    
                    const newContent = oldContent.substring(0, startPos) + startTag + selectedText + endTag + oldContent.substring(endPos);
                    textarea.value = newContent;
                    
                    const newCursorPos = startPos + startTag.length + selectedText.length;
                    textarea.focus();
                    textarea.setSelectionRange(newCursorPos, newCursorPos);

                    if (this.dataset.close) {
                        toolbar.querySelector('.' + this.dataset.close).style.display = 'none';
                    }
                }
            });
        });

        // 2. Xử lý Upload Media
        const uploadBtn = toolbar.querySelector('.bb-upload-btn');
        const fileInput = toolbar.querySelector('.bb-upload-input');
        
        if (uploadBtn && fileInput) {
            uploadBtn.addEventListener('click', () => {
                fileInput.click();
            });

            fileInput.addEventListener('change', function() {
                if (!this.files.length) return;
                const file = this.files[0];
                
                // Validate dung lượng 1MB ở phía client
                if (file.size > 1048576) {
                    alert("Dung lượng tệp vượt quá giới hạn 1MB!");
                    this.value = '';
                    return;
                }

                const fd = new FormData();
                fd.append("file", file);
                
                // Lấy CSRF token nếu có
                const csrfToken = document.querySelector('input[name="csrf_token"]');
                if (csrfToken) {
                    fd.append("csrf_token", csrfToken.value);
                }

                const originalHtml = uploadBtn.innerHTML;
                uploadBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                uploadBtn.disabled = true;

                const isDirectMode = typeof TelegramUploadMode !== 'undefined' && TelegramUploadMode === 'direct';

                // Tìm blogid (nếu đang ở trang đăng bài/sửa bài viết)
                let blogid = 0;
                let idInput = null;
                const uploadForm = toolbar.closest('form');
                if (uploadForm) {
                    idInput = uploadForm.querySelector('input[name="id"], input[name="blogid"]');
                    if (idInput && idInput.value) blogid = idInput.value;
                }
                if (!blogid) {
                    const urlParams = new URLSearchParams(window.location.search);
                    blogid = urlParams.get('id') || 0;
                }

                if (isDirectMode) {
                    const fdBackend = new FormData();
                    fdBackend.append("document", file);
                    fdBackend.append("blogid", blogid);
                    if (csrfToken) {
                        fdBackend.append("csrf_token", csrfToken.value);
                    }

                    fetch('/media/upload', {
                        method: 'POST',
                        body: fdBackend,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        uploadBtn.innerHTML = originalHtml;
                        uploadBtn.disabled = false;
                        
                        if (data && data.status === 'success') {
                            let tagStart = data.is_image ? '[img]' : '[d]';
                            let tagEnd = data.is_image ? '[/img]' : '[/d]';
                            
                            const startPos = textarea.selectionStart;
                            const oldContent = textarea.value;
                            const newContent = oldContent.substring(0, startPos) + tagStart + data.url + tagEnd + oldContent.substring(startPos);
                            textarea.value = newContent;
                            textarea.focus();
                        } else {
                            alert(data ? (data.message || 'Lỗi không xác định khi upload!') : 'Lỗi không xác định khi lưu file!');
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        uploadBtn.innerHTML = originalHtml;
                        uploadBtn.disabled = false;
                        alert('Lỗi kết nối khi tải lên!');
                    });
                } else {
                    const workerUrl = typeof UrlUpload !== 'undefined' ? UrlUpload : (typeof TelegramUploadUrl !== 'undefined' ? TelegramUploadUrl : 'https://nosineup.stockage.workers.dev/upload');
                    const fdTelegram = new FormData();
                    fdTelegram.append("document", file);

                    fetch(workerUrl, {
                        method: 'POST',
                        body: fdTelegram
                    })
                    .then(response => response.json())
                    .then(tgData => {
                        if (tgData.file_id) {
                            const fdBackend = new FormData();
                            fdBackend.append("filename", file.name);
                            fdBackend.append("filesize", file.size);
                            fdBackend.append("filecate", tgData.file_id);
                            fdBackend.append("blogid", blogid);
                            if (csrfToken) {
                                fdBackend.append("csrf_token", csrfToken.value);
                            }

                            return fetch('/media/upload', {
                                method: 'POST',
                                body: fdBackend,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            }).then(r => r.json());
                        } else {
                            throw new Error('Không lấy được file_id từ Telegram');
                        }
                    })
                    .then(data => {
                        uploadBtn.innerHTML = originalHtml;
                        uploadBtn.disabled = false;
                        
                        if (data && data.status === 'success') {
                            let tagStart = data.is_image ? '[img]' : '[d]';
                            let tagEnd = data.is_image ? '[/img]' : '[/d]';
                            
                            const startPos = textarea.selectionStart;
                            const oldContent = textarea.value;
                            const newContent = oldContent.substring(0, startPos) + tagStart + data.url + tagEnd + oldContent.substring(startPos);
                            textarea.value = newContent;
                            textarea.focus();
                        } else {
                            alert(data ? (data.message || 'Lỗi không xác định khi upload!') : 'Lỗi không xác định khi lưu file!');
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        uploadBtn.innerHTML = originalHtml;
                        uploadBtn.disabled = false;
                        alert('Lỗi kết nối khi tải lên!');
                    });
                }

                this.value = ''; // Reset input
            });
        }

        // 3. Xử lý Media Library
        const mediaBtn = toolbar.querySelector('.bb-media-btn');
        const mediaModal = toolbar.querySelector('.bb-media-modal');
        if (mediaBtn && mediaModal) {
            const closeBtn = mediaModal.querySelector('.bb-media-close');
            const grid = mediaModal.querySelector('.bb-media-grid');

            function insertMedia(url, isImage) {
                const tagStart = isImage ? '[img]' : '[d]';
                const tagEnd = isImage ? '[/img]' : '[/d]';
                
                const startPos = textarea.selectionStart;
                const oldContent = textarea.value;
                const newContent = oldContent.substring(0, startPos) + tagStart + url + tagEnd + oldContent.substring(startPos);
                textarea.value = newContent;
                textarea.focus();
                mediaModal.style.display = 'none';
            }

            mediaBtn.addEventListener('click', () => {
                mediaModal.style.display = 'flex';
                grid.innerHTML = '<div class="text-center text-muted" style="grid-column: 1 / -1; padding: 30px;"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Đang tải dữ liệu...</div>';
                
                fetch('/media/library')
                    .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        if (data.data.length === 0) {
                            grid.innerHTML = '<div class="text-center text-muted" style="grid-column: 1 / -1; padding: 30px;"><i class="fa fa-folder-open-o fa-3x mb-2"></i><br>Thư viện trống.</div>';
                            return;
                        }
                        
                        grid.innerHTML = '';
                        data.data.forEach(item => {
                            const div = document.createElement('div');
                            div.style.cssText = 'border: 1px solid var(--border); border-radius: 6px; overflow: hidden; cursor: pointer; display: flex; flex-direction: column; background: var(--surface-muted); transition: 0.2s;';
                            div.title = item.filename;
                            
                            // hover effect
                            div.onmouseover = () => div.style.borderColor = 'var(--accent)';
                            div.onmouseout = () => div.style.borderColor = 'var(--border)';
                            
                            let preview = '';
                            if (item.is_image) {
                                preview = `<div style="height: 100px; background-image: url('${item.url}'); background-size: cover; background-position: center; border-bottom: 1px solid var(--border);"></div>`;
                            } else {
                                preview = `<div style="height: 100px; display: flex; align-items: center; justify-content: center; background: var(--surface); border-bottom: 1px solid var(--border); font-size: 30px; color: var(--fg-muted);"><i class="fa fa-file-o"></i></div>`;
                            }
                            
                            div.innerHTML = `
                                ${preview}
                                <div style="padding: 8px; font-size: 11px;">
                                    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: bold; margin-bottom: 4px;">${item.filename}</div>
                                    <div style="color: var(--fg-muted); display: flex; justify-content: space-between;">
                                        <span>${(item.filesize / 1024).toFixed(1)} KB</span>
                                        <span>${item.time.split(' ')[0]}</span>
                                    </div>
                                </div>
                            `;
                            
                            div.addEventListener('click', () => {
                                insertMedia(item.url, item.is_image);
                            });
                            
                            grid.appendChild(div);
                        });
                    } else {
                        grid.innerHTML = `<div class="text-center text-danger" style="grid-column: 1 / -1; padding: 30px;"><i class="fa fa-exclamation-triangle"></i> Lỗi: ${data.message}</div>`;
                    }
                })
                .catch(e => {
                    console.error(e);
                    grid.innerHTML = '<div class="text-center text-danger" style="grid-column: 1 / -1; padding: 30px;"><i class="fa fa-exclamation-triangle"></i> Lỗi tải dữ liệu.</div>';
                });
            });

            closeBtn.addEventListener('click', () => {
                mediaModal.style.display = 'none';
            });
            
            // Close on click outside
            mediaModal.addEventListener('click', (e) => {
                if (e.target === mediaModal) {
                    mediaModal.style.display = 'none';
                }
            });
        }
    });
});
