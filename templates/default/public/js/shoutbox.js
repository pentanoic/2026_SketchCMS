$(document).ready(function () {
    const loadContent = '<div class="list1">Đang tải dữ liệu <i class="fa fa-spin fa-hourglass-half"></i></div>';
    $("#idChat").html(loadContent);

    $.get(chatbox, function (response) {
        $("#idChat").html(response).hide().slideDown("slow");
    });

    const form = $("#form"),
        submit = $("#submit"),
        message = $("#postText");

    form.on("submit", function (event) {
        event.preventDefault();

        if (message.val().trim() === "") {
            alert('Bạn chưa nhập nội dung !!!');
            message.focus();
            return false;
        }

        // Đảm bảo an toàn: Cân nhắc sysRateLimit, disable nút submit trong khoảng thời gian rate limit
        var actualTimeout = (typeof sysRateLimit !== 'undefined' ? sysRateLimit : 1000) + 3000; // Cộng thêm 3s độ trễ mạng ngẫu nhiên từ server

        $.ajax({
            url: chat_send,
            type: "POST",
            timeout: actualTimeout,
            dataType: "json",
            data: {
                msg: message.val(),
                csrf_token: $("input[name='csrf_token']").val()
            },
            beforeSend: function () {
                submit.prop('disabled', true);
                submit.html('Đang gửi <i class="fa fa-spinner fa-spin fa-fw"></i>');
            },
            success: function (response) {
                if (response.status === 'success') {
                    form.trigger("reset");
                    message.focus();
                    submit.html('<i class="fa fa-check" aria-hidden="true"></i> Chat');
                } else {
                    console.log("Shoutbox Error Response: ", response);
                    alert("Error: " + JSON.stringify(response));
                    message.focus();
                    submit.html('Chat');
                }
                
                // Mở khoá nút sau khi hết thời gian Rate Limit để đồng bộ với bảo mật backend
                setTimeout(function() {
                    submit.prop('disabled', false);
                }, typeof sysRateLimit !== 'undefined' ? sysRateLimit : 1000);
            },
            error: function (xhr, status, error) {
                console.log(xhr, status, error);
                submit.html('Chat');
                setTimeout(function() {
                    submit.prop('disabled', false);
                }, typeof sysRateLimit !== 'undefined' ? sysRateLimit : 1000);
                alert("Đã xảy ra lỗi khi gửi tin nhắn.");
            }
        });
    });
});

async function gogoChat() {
    reload_chat = setInterval(async function () {
        fetch(chat_count).then(t => t.json()).then(t => {
            if (t - a > 3) {
                window.location.href = "/";
            }

            for (var a = t; a > totalChat;) {
                totalChat++;
                var m = chat_ele + totalChat;
                $.get(m, function (t) {
                    $("#idChat").prepend(t), $("#idChat .list1:last").remove(), phanTrangChat(totalChat, pageID)
                })
            }
        })
    }, typeof sysRateLimit !== 'undefined' && sysRateLimit > 4000 ? sysRateLimit : 4000)
}

function phanTrangChat(totalChat, pageID) {
    var totalPages = Math.ceil(totalChat / 10);
    var numPagesToShow = 3;
    var paginationContainer = document.querySelector('.pagination');
    paginationContainer.innerHTML = '';

    if (totalPages > 1) {
        // First and Previous Links
        if (pageID > 1) {
            var firstPageItem = createPageItem(1, 'First', false);
            var prevPageItem = createPageItem(pageID - 1, '<i class="fa fa-angle-left"></i>', false);
            paginationContainer.appendChild(firstPageItem);
            paginationContainer.appendChild(prevPageItem);
        }

        // Determine the start and end page numbers
        var startPage = Math.max(1, pageID - Math.floor(numPagesToShow / 2));
        var endPage = Math.min(totalPages, startPage + numPagesToShow - 1);

        // Adjust the start page if we're near the end
        if (endPage - startPage < numPagesToShow) {
            startPage = Math.max(1, endPage - numPagesToShow + 1);
        }

        // Page Numbers
        for (var i = startPage; i <= endPage; i++) {
            var isActive = i === pageID;
            var pageItem = createPageItem(i, i, isActive);
            paginationContainer.appendChild(pageItem);
        }

        // Next and Last Links
        if (pageID < totalPages) {
            var nextPageItem = createPageItem(pageID + 1, '<i class="fa fa-angle-right"></i>', false);
            var lastPageItem = createPageItem(totalPages, 'Last', false);
            paginationContainer.appendChild(nextPageItem);
            paginationContainer.appendChild(lastPageItem);
        }
    }

    function createPageItem(page, content, isActive) {
        var li = document.createElement('li');
        if (isActive) li.classList.add('active');
        var a = document.createElement('a');
        a.href = '#';
        if (!isActive) a.setAttribute('onclick', 'loadTrang(' + totalChat + ',' + page + ')');
        a.innerHTML = content;
        li.appendChild(a);
        return li;
    }
}

function getPage(totalChat) {
    loadTrang(totalChat, $("#ano").val());
}

function loadTrang(totalChat, pageID) {
    $("#idChat").empty();

    var chatli = chat_list + pageID;
    $.get(chatli, function (t) {
        $("#idChat").append(t);
    });

    document
        .getElementById("chat-place")
        .scrollIntoView();
    phanTrangChat(totalChat, pageID);
}

gogoChat();
phanTrangChat(totalChat, pageID);