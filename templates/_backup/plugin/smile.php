<?php
$path_list = [
    'pepe' => 444,
    'ami' => 48,
    'moew' => 19,
    'qoopepe' => 17,
    'menhera' => 24,
    'dauhanh' => 131,
    'troll' => 132,
    'qoobee' => 127,
    'dora' => 303,
    'aru' => 119,
    'thobaymau' => 98,
    'nam' => 26,
    'le' => 72,
    'anya' => 15,
    'aka' => 24,
    'dui' => 15,
    'firefox' => 11,
    'conan' => 18
];
$smile = [
    'pepe',
    'ami',
    'qoopepe',
    'moew',
    'menhera',
    'dauhanh',
    'troll',
    'qoobee',
    'dora',
    'aru',
    'thobaymau',
    'nam',
    'le',
    'anya',
    'aka',
    'dui',
    'firefox',
    'conan'
];
$path = isset($_POST['path']) ? $_POST['path'] : '';
$path = _e($path);
?>
<div class="smile_container">
    <table width="100%">
        <tr>
            <td width="30px">
                <div class="sleft">
                    <?php foreach ($path_list as $path_img => $total): ?>
                        <div class="sitem"
                            style="background-image:url(https://dorew-site.github.io/assets/smileys/<?= _e($path_img) ?>/<?= _e($path_img) ?>1.png)"
                            path="<?= _e($path_img) ?>"></div>
                    <?php endforeach; ?>
                </div>
            </td>
            <td>
                <div class="sright">
                    <?php foreach ($path_list as $path_img => $total): ?>
                        <div class="ritem"
                            style="background-image:url(https://dorew-site.github.io/assets/smileys/<?= _e($path_img) ?>/<?= _e($path_img) ?>1.png)"
                            smile=" :<?= _e($path_img) ?>1:"></div>
                    <?php endforeach; ?>

                    <?php if (!$path): ?>
                        <?php if (in_array($path, $smile)): ?>
                            <?php for ($i = 1; $i <= $path_list[$path]; $i++): ?>
                                <div class="ritem"
                                    style="background-image: url(https://dorew-site.github.io/assets/smileys/<?= _e($path) ?>/<?= _e($path) ?><?= _e($i) ?>.png)"
                                    smile=" :<?= _e($path) ?><?= _e($i) ?>:"></div>
                            <?php endfor; ?>
                        <?php else: ?>
                            <?php foreach ($path_list as $path_img => $total): ?>
                                <div class="ritem"
                                    style="background-image:url(https://dorew-site.github.io/assets/smileys/<?= _e($path_img) ?>/<?= _e($path_img) ?>1.png)"
                                    smile=" :<?= _e($path_img) ?>1:"></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>
</div>

<style>
    .smile_container {
        width: 296px;
        background-color: #eaeaeabf;
        border: 1px solid #444444e6;
        box-shadow: 1px 1px 3px #444444bd;
        border-radius: 3px;
        height: 198px;
        background-image: linear-gradient(#bbb, white);
        position: absolute;
        z-index: 999
    }

    .sitem {
        width: 35px;
        height: 35px;
        background-color: #fff;
        margin: auto;
        background-size: contain;
        border-bottom: 1px solid #ddd;
        cursor: pointer
    }

    .ritem {
        width: 45px;
        height: 45px;
        background-color: #fff;
        margin: auto;
        background-size: contain;
        border-bottom: 1px solid #ddd;
        cursor: pointer;
        display: inline-block;
        border-right: 1px solid #ddd
    }

    .allbum {
        position: relative;
        width: 75px;
        height: 75px;
        background-color: #fff;
        margin: auto;
        background-size: contain;
        border-bottom: 1px solid #ddd;
        cursor: pointer;
        display: inline-block;
        border-right: 1px solid #ddd
    }

    .sleft {
        height: 194px;
        overflow-y: scroll
    }

    .sright {
        height: 194px;
        overflow-y: scroll
    }
</style>