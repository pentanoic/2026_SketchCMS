var captcha_version = 17;
var captcha_done = false;
var captcha_label = document.currentScript.getAttribute('label');
var captcha_enemies = parseInt(document.currentScript.getAttribute('enemies'));
if (!captcha_enemies) {
    captcha_enemies = 4;
}

var captcha_html = '';
if (captcha_label) {
    captcha_html = '<div class="text-center" id="captcha">' + captcha_label + '<br/>';
}

captcha_html += '<iframe id="doom_captcha" src="https://vivirenremoto.github.io/doomcaptcha/captcha.html?version=' + captcha_version + '&countdown=' + document.currentScript.getAttribute('countdown') + '&enemies=' + captcha_enemies + '" style="width:300px;height:150px;border:2px black solid;"></iframe>';

if (captcha_label) {
    captcha_html += '</div>';
}

document.write(captcha_html);

document.addEventListener('DOMContentLoaded', function() {
    var daiElement = document.getElementById('dai');
    var captchaElement = document.getElementById('captcha');
    var daiCheckElement = document.getElementById('dai-check');

    if (daiElement) {
        daiElement.style.display = 'none';
        daiElement.style.border = 'none';
    }

    window.addEventListener('message', function(e) {
        if (e.origin.indexOf('vivirenremoto.github.io') > -1) {
            captcha_done = true;
            document.getElementById('doom_captcha').style.borderColor = 'black';
            if (daiElement) {
                daiElement.style.display = 'block';
                captchaElement.style.display = 'none';
                daiCheckElement.checked = true;
            }
        }
    }, false);

    document.getElementById('doom_captcha').closest('form').addEventListener('submit', function(event) {
        if (!captcha_done) {
            document.getElementById('doom_captcha').style.borderColor = 'red';
            event.preventDefault();
        }
    });
});
