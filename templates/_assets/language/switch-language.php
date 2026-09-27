<link rel="stylesheet" href="<?= url('/templates/_assets/language/switch-language.css?v=' . time()) ?>" rel="stylesheet" />
<script src="<?= url('/templates/_assets/language/switch-language.js') ?>"></script>

<script type="text/javascript">
    function googleTranslateElementInit2() {
        new google.translate.TranslateElement({
            pageLanguage: 'vi',
            autoDisplay: false
        }, 'google_translate_element2');
    }
</script>
<script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit2"></script>

<!-- Modal: Language -->
<div class="modal fade" id="languageModal" tabindex="-1" role="dialog" aria-labelledby="languageModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="languageModalLabel">Chọn ngôn ngữ</h5> <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
            </div>
            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item lang-item d-flex align-items-center" data-lang="vi"> <img src="https://flagcdn.com/w40/vn.png" class="flag-icon mr-2" alt="Tiếng Việt"> <span>Tiếng Việt</span> </li>
                    <li class="list-group-item lang-item d-flex align-items-center" data-lang="en"> <img src="https://flagcdn.com/w40/gb.png" class="flag-icon mr-2" alt="English"> <span>English</span> </li>
                    <li class="list-group-item lang-item d-flex align-items-center" data-lang="zh-CN"> <img src="https://flagcdn.com/w40/cn.png" class="flag-icon mr-2" alt="中文"> <span>中文</span> </li>
                    <li class="list-group-item lang-item d-flex align-items-center" data-lang="fr"> <img src="https://flagcdn.com/w40/fr.png" class="flag-icon mr-2" alt="Français"> <span>Français</span> </li>
                    <li class="list-group-item lang-item d-flex align-items-center" data-lang="ar"> <img src="https://flagcdn.com/w40/sa.png" class="flag-icon mr-2" alt="العربية"> <span>العربية</span> </li>
                    <li class="list-group-item lang-item d-flex align-items-center" data-lang="he"> <img src="https://flagcdn.com/w40/il.png" class="flag-icon mr-2" alt="עברית"> <span>עברית</span> </li>
                    <li class="list-group-item lang-item d-flex align-items-center" data-lang="ru"> <img src="https://flagcdn.com/w40/ru.png" class="flag-icon mr-2" alt="Русский"> <span>Русский</span> </li>
                    <li class="list-group-item lang-item d-flex align-items-center" data-lang="uk"> <img src="https://flagcdn.com/w40/ua.png" class="flag-icon mr-2" alt="Українська"> <span>Українська</span> </li>
                    <li class="list-group-item lang-item d-flex align-items-center" data-lang="id"> <img src="https://flagcdn.com/w40/id.png" class="flag-icon mr-2" alt="Bahasa Indonesia"> <span>Bahasa Indonesia</span> </li>
                    <li class="list-group-item lang-item d-flex align-items-center" data-lang="hi"> <img src="https://flagcdn.com/w40/in.png" class="flag-icon mr-2" alt="हिंदी"> <span>हिंदी</span> </li>
                    <li class="list-group-item lang-item d-flex align-items-center" data-lang="ja"> <img src="https://flagcdn.com/w40/jp.png" class="flag-icon mr-2" alt="日本語"> <span>日本語</span> </li>
                    <li class="list-group-item lang-item d-flex align-items-center" data-lang="th"> <img src="https://flagcdn.com/w40/th.png" class="flag-icon mr-2" alt="ภาษาไทย"> <span>ภาษาไทย</span> </li>
                </ul>
                <div id="google_translate_element2"></div>
            </div>
        </div>
    </div>
</div>