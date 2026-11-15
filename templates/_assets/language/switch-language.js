// Danh sách ngôn ngữ của một số quốc gia và vùng lãnh thổ
const flags = {
    'vi': 'https://flagcdn.com/w40/vn.png',
    'en': 'https://flagcdn.com/w40/gb.png',
    'zh-CN': 'https://flagcdn.com/w40/cn.png',
    'fr': 'https://flagcdn.com/w40/fr.png',
    'ar': 'https://flagcdn.com/w40/sa.png',
    'he': 'https://flagcdn.com/w40/il.png',
    'ru': 'https://flagcdn.com/w40/ru.png',
    'uk': 'https://flagcdn.com/w40/ua.png',
    'id': 'https://flagcdn.com/w40/id.png',
    'hi': 'https://flagcdn.com/w40/in.png',
    'ja': 'https://flagcdn.com/w40/jp.png',
    'th': 'https://flagcdn.com/w40/th.png'
}

    ;

// Xử lý khi chọn ngôn ngữ
$(document).on('click', '.lang-item', function () {
    var lang = $(this).data('lang');
    var select = document.querySelector(".goog-te-combo");

    if (select) {
        select.value = lang;
        select.dispatchEvent(new Event('change'));
    }

    // Cập nhật cờ hiện tại
    $("#current-flag").attr("src", flags[lang]);
    // Thêm class 'selected' cho ngôn ngữ đã chọn
    $(".lang-item").removeClass("selected"); // Xóa class 'selected' khỏi tất cả
    $(this).addClass("selected"); // Thêm class 'selected' vào ngôn ngữ đang chọn
    // Đóng modal
    $('#languageModal').modal('hide');
});

function updateFlag() {
    // Cập nhật cờ khi ngôn ngữ thay đổi
    let lang = document.documentElement.lang || 'vi';
    $("#current-flag").attr("src", flags[lang] || flags['vi']);
}
// Chạy cập nhật sau khi Google Translate load xong
setTimeout(updateFlag, 3000);