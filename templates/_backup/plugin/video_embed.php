<?php
// Lấy tham số 'link' từ query string
$fullurl0 = isset($_GET['link']) ? $_GET['link'] : 'https://www.youtube.com/watch?v=mermtj3__zU';
$fullurl0 = _e($fullurl0);
function get_youtube_id($url)
{
  preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user|shorts)\/))([^\?&\"'>]+)/", $url, $matches);
  return $matches[1];
}

// Kiểm tra và set các biến dựa trên giá trị của $fullurl0
if (strpos($fullurl0, 'youtube') !== false || strpos($fullurl0, 'youtu.be/') !== false) {
    $vidUrl = get_youtube_id($fullurl0);
    ?>
    <iframe id="ytplayer" type="text/html" allowfullscreen="" width="100%" height="100%" src="https://www.youtube-nocookie.com/embed/<?= _e($vidUrl) ?>" frameborder="0"></iframe>
    <?php
} elseif (checkExtension($fullurl0) == 'file-audio-o' || checkExtension($fullurl0) == 'file-video-o') {
    ?>
    <div id="dplayer"></div>
    <script src="https://cdn.statically.io/gh/kn007/DPlayer-Lite/00dab19fc8021bdb072034c0415184a638a3e3b2/dist/DPlayer.min.js"></script>
    <script>
    const dp = new DPlayer({
        container: document.getElementById('dplayer'),
        video: {
            url: '<?= _e($fullurl0) ?>',
        },
    });
    </script>
    <?php
} else {
    ?>
    <div id="place"></div>
    <script>
    fetch("https://noembed.com/embed?url=<?= urlencode($fullurl0) ?>")
    .then(response => response.json())
    .then(data => {
        document.getElementById("place").innerHTML = data.html;
        console.log(data.html);
    });
    </script>
    <?php
}
?>
<style>body{margin:0}</style>
