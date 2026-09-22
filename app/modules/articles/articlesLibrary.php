<?php

/**
 * Software: SketchCMS
 * Author: valedrat
 * Email: kioku17@protonmail.com
 * GitHub: https://github.com/pentanoic/
 * Website: https://lab302.ovh/
 *
 * Copyright (c) 2026 valedrat. All rights reserved.
 *
 * This file is part of the SketchCMS source code.
 * Please do not remove or modify this copyright notice.
 */


use Symfony\Component\String\Slugger\AsciiSlugger;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use Highlight\Highlighter;

class articlesLibrary extends Library
{
    public function PageDescription($content)
    {
        if (empty($content)) {
            return null;
            exit();
        }
        $content = html_entity_decode($this->bbcode($content));
        $content = strip_tags(html_entity_decode($content));
        $content = str_replace(["\r", "\n"], '', $content);
        $content = mb_substr($content, 0, 150);
        return $content;
    }

    public function PageKeyword($content)
    {
        if (empty($content)) {
            return null;
            exit();
        }
        $content = explode(' ', $content);
        $content = array_filter($content);
        $content = implode(', ', $content);
        return $content;
    }

    public function TrimContent(?string $content): ?string
    {
        // Nếu null hoặc chuỗi rỗng ngay từ đầu
        if ($content === null || $content === '') {
            return null;
        }
        // Chuẩn hóa các loại khoảng trắng đặc biệt (invisible whitespace + NBSP)
        $content = str_replace(["\xE2\x80\x87", "\xC2\xA0", "\xE1\x9A\x80"], ' ', $content);
        // Thay tất cả các khoảng trắng liên tiếp (trừ xuống dòng) bằng 1 dấu cách
        // Đồng thời trim đầu/cuối
        $content = preg_replace('/[^\S\r\n]+/u', ' ', $content);
        $content = trim($content);
        // Nếu sau khi xử lý mà rỗng -> không hợp lệ
        if ($content === '') {
            return null;
        }
        // Kiểm tra các trường hợp không có nội dung thực sự
        $length = mb_strlen($content, 'UTF-8');
        if (
            $length < 3 ||
            count(array_unique(mb_str_split($content))) === 1 ||
            preg_match('/^[\p{P}\p{S}\s]+$/u', $content)
        ) {
            return null;
        }
        // Nếu hợp lệ -> escape và trả về
        return _e($content);
    }

    public function ReadContent(?string $contentFromDB): ?string
    {
        if ($contentFromDB === null) {
            return null;
        }
        // Giải mã các entities HTML trở về ký tự gốc
        $content = htmlspecialchars_decode($contentFromDB, ENT_QUOTES);
        // Chuẩn hóa line endings để textarea hiển thị nhất quán
        // Windows (\r\n) → Unix (\n), cũ (\r) → \n
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        return $content;
    }

    public function ContentLen($string = null)
    {
        return isset($string) ? mb_strlen($string) : 0;
    }

    public function slug($string = null)
    {
        if (empty($string)) {
            return null;
            exit();
        }
        $slugger = new AsciiSlugger();
        $slug = $slugger->slug($string)->lower();
        return $slug;
    }

    public function markdown_to_html($string = null)
    {
        if (empty($string)) {
            return null;
            exit();
        }
        // cấu hình đối tượng
        $converter = new CommonMarkConverter([
            'allow_unsafe_links' => true,
            'allow_unsafe_protocol_links' => true,
            'allow_unsafe_protocols' => ['http', 'https'],
            'allow_unsafe_line_breaks' => true,
            'extensions' => [
                new AutolinkExtension(),
                new HeadingPermalinkExtension(),
                new TableExtension(),
            ],
        ]);
        // chuyển đối tượng sang html
        $html = $converter->convertToHtml($string);
        return $html;
    }

    public function smileys($string = null)
    {
        if (empty($string)) {
            return null;
            exit();
        }
        $arr_emo_name = ['ami', 'anya', 'aru', 'aka', 'dauhanh', 'dora', 'le', 'menhera', 'moew', 'nam', 'pepe', 'qoobee', 'qoopepe', 'thobaymau', 'troll', 'dui', 'firefox', 'conan'];
        foreach ($arr_emo_name as $emo_name) {
            if (strpos($string, ':' . $emo_name) !== false) {
                $parttern = '/[:]' . $emo_name . '([0-9]*):/';
                $image_url = 'https://dorew-site.github.io/assets/smileys/' . $emo_name . '/' . $emo_name . '$1.png';
                if (@getimagesize($image_url)) {
                    $replacement = '<img loading="lazy" src="' . $image_url . '" alt="$1"/>';
                    $string = preg_replace($parttern, $replacement, $string);
                }
            }
        }
        return $string;
    }
    public function bbcode($string)
    {
        $code_blocks = [];

        $string = preg_replace_callback(
            '#\[code(?:=(.+?))?\](.+?)\[\/code\]#is',
            function ($matches) use (&$code_blocks) {
                $highlighter = new \Highlight\Highlighter();
                $supportedLanguages = $highlighter->listLanguages();

                $aliases = [
                    'js' => 'javascript',
                    'ts' => 'typescript',
                    'py' => 'python',
                    'html' => 'xml',
                    'sh' => 'bash',
                    'c#' => 'csharp',
                    'c++' => 'cpp',
                    'vue' => 'xml',
                    'json' => 'json'
                ];

                $input_lang = isset($matches[1]) ? mb_strtolower(trim($matches[1])) : 'php';
                if (isset($aliases[$input_lang])) {
                    $input_lang = $aliases[$input_lang];
                }

                $lang = in_array($input_lang, $supportedLanguages) ? $input_lang : 'php';

                $code = html_entity_decode($matches[2]);
                $highlighted = $highlighter->highlight($lang, $code);

                $placeholder = '<!-- CODE_BLOCK_' . count($code_blocks) . ' -->';

                $html = '<div class="dw-code-wrapper position-relative mb-3">';
                $html .= '<button class="dw-copy-btn btn btn-sm btn-dark position-absolute" title="Sao chép"><i class="fa fa-copy"></i></button>';
                $html .= "<pre><code class=\"hljs {$highlighted->language}\">{$highlighted->value}</code></pre>";
                $html .= '</div>';

                $code_blocks[$placeholder] = $html;
                return $placeholder;
            },
            $string
        );

        // Các thẻ bb đơn giản
        $type1 = 'b|u|s|i|strong|em';
        $string = preg_replace('/\[(' . $type1 . ')\](.*?)\[\/\1\]/is', '<$1>$2</$1>', $string);

        // Các thẻ bb nhiều tham số
        $type2 = [
            '/\[color=(.*?)\](.*?)\[\/color\]/is' => '<span style="color:$1">$2</span>',
            '/\[bcolor=(.*?)\](.*?)\[\/bcolor\]/is' => '<span style="color:$1;font-weight:700">$2</span>',
            '/\[quote=(.*?)\](.*?)\[\/quote\]/is' => '<div class="quote"><div class="quote-title">Quote from <b>$1</b></div><div class="quote-content">$2</div></div>',
            '/\[size=(.*?)\](.*?)\[\/size\]/is' => '<span style="font-size:$1px">$2</span>',
        ];
        $type3 = [
            '/\[red\](.*?)\[\/red\]/is' => '<span style="color:red">$1</span>',
            '/\[blue\](.*?)\[\/blue\]/is' => '<span style="color:blue">$1</span>',
            '/\[green\](.*?)\[\/green\]/is' => '<span style="color:green">$1</span>',
            '/\[yellow\](.*?)\[\/yellow\]/is' => '<span style="color:yellow">$1</span>',
            '/\[orange\](.*?)\[\/orange\]/is' => '<span style="color:orange">$1</span>',
            '/\[purple\](.*?)\[\/purple\]/is' => '<span style="color:purple">$1</span>',
            '/\[pink\](.*?)\[\/pink\]/is' => '<span style="color:pink">$1</span>',
            '/\[brown\](.*?)\[\/brown\]/is' => '<span style="color:brown">$1</span>',
            '/\[gray\](.*?)\[\/gray\]/is' => '<span style="color:gray">$1</span>',

            '/\[center\](.*?)\[\/center\]/is' => '<div style="text-align:center">$1</div>',
            '/\[right\](.*?)\[\/right\]/is' => '<div style="text-align:right">$1</div>',
            '/\[left\](.*?)\[\/left\]/is' => '<div style="text-align:left">$1</div>',
            '/\[justify\](.*?)\[\/justify\]/is' => '<div style="text-align:justify">$1</div>',
        ];
        $sim = array_merge($type2, $type3);
        $string = preg_replace(array_keys($sim), array_values($sim), $string);

        // tag @nick
        $string = preg_replace_callback('/@([a-zA-Z0-9_]+)/', function ($matches) {
            $UserModel = $this->load->model('user');
            $user = mb_strtolower($matches[1]);
            $UserDetail = $UserModel->UserDetailWithFields('nick', $user);
            if ($UserDetail) {
                $string = '<span class="tagnick">@' . RoleColor($UserDetail) . '</span>';
            } else {
                $string = '@' . $matches[1];
            }
            //$string = '@' . $matches[1];
            return $string;
        }, $string);

        // tag2 @[module=...;quote=id]
        $string = preg_replace_callback('/@\[module=([a-zA-Z0-9_]+);quote=(\d+)\]/', function ($matches) {
            $module = mb_strtolower($matches[1]);
            $quoteId = (int)$matches[2];
            $db = Container::get(DB::class);
            $quoteTitle = 'Quote';
            $quoteContent = 'Nội dung không tồn tại hoặc đã bị xóa.';

            $e2e = new E2E();
            $e2e_key = defined('E2E_SECRET_KEY') ? E2E_SECRET_KEY : '';

            try {
                if ($module === 'shoutbox') {
                    $stmt = $db->prepare('SELECT `name`, `comment` FROM `chat` WHERE `id` = :id');
                    $stmt->execute(['id' => $quoteId]);
                    if ($data = $stmt->fetch()) {
                        $quoteTitle = 'Trích dẫn từ <b>' . _e($data['name']) . '</b>';

                        $raw_comment = $data['comment'];
                        $salt = 'shoutbox' . $data['name'];
                        if ($e2e->isEncrypted($raw_comment, $e2e_key, $salt)) {
                            $raw_comment = $e2e->decrypt($raw_comment, $e2e_key, $salt);
                        }

                        // strip nested quotes to avoid loop if we ever decide to parse them, and basic clean
                        $quoteContent = preg_replace('/@\[module=.*?;quote=.*?\]/', '[quote]', $raw_comment);
                    }
                } elseif ($module === 'articles') {
                    $stmt = $db->prepare('SELECT `author`, `comment` FROM `articles_comments` WHERE `id` = :id');
                    $stmt->execute(['id' => $quoteId]);
                    if ($data = $stmt->fetch()) {
                        $quoteTitle = 'Trích dẫn từ <b>' . _e($data['author']) . '</b>';

                        $raw_comment = $data['comment'];
                        $salt = 'articles' . $data['author'];
                        if ($e2e->isEncrypted($raw_comment, $e2e_key, $salt)) {
                            $raw_comment = $e2e->decrypt($raw_comment, $e2e_key, $salt);
                        }

                        $quoteContent = preg_replace('/@\[module=.*?;quote=.*?\]/', '[quote]', $raw_comment);
                    }
                }
            } catch (Exception $e) {
            }

            return '<div class="quote"><div class="quote-title">' . $quoteTitle . '</div><div class="quote-content">' . $quoteContent . '</div></div>';
        }, $string);

        // smiley
        $arr_emo_name = ['ami', 'anya', 'aru', 'aka', 'dauhanh', 'dora', 'le', 'menhera', 'moew', 'nam', 'pepe', 'qoobee', 'qoopepe', 'thobaymau', 'troll', 'dui', 'firefox', 'conan'];
        foreach ($arr_emo_name as $emo_name) {
            if (strpos($string, ':' . $emo_name) !== false) {
                $parttern = '/[:]' . $emo_name . '([0-9]*):/';
                $replacement = '<img loading="lazy" src="https://dorew-site.github.io/assets/smileys/' . $emo_name . '/' . $emo_name . '$1.png" alt="$1"/>';
                $string = preg_replace($parttern, $replacement, $string);
            }
        }

        // xử lý hình ảnh
        /*
        $parttern = '/\[img\](.*?)\[\/img\]/';
        $loaderror = 'https://i.imgur.com/806SpRu.png';
        $replacement = '<center><a href="$1" class="swipebox"><img loading="lazy" class="bb_img LoadImage" src="$1" border="2" onerror="this.onerror=null;this.src=' . $loaderror . '" style="border-radius:1%;display:block;margin:0 auto;max-width:70%;max-height:70%"/></a></center>';
        $string = preg_replace($parttern, $replacement, $string);
		*/
        $loaderror = 'https://i.imgur.com/806SpRu.png';
        $string = preg_replace_callback(
            '/(\[img\].*?\[\/img\])(\s*\[img\].*?\[\/img\])*/s',
            function ($matches) use ($loaderror) {
                // XSS Filter
                $inner = preg_replace_callback('/\[img\](.*?)\[\/img\]/', function ($m) use ($loaderror) {
                    $src = trim($m[1]);
                    if (preg_match('/^(javascript|vbscript|data):/i', $src)) {
                        return '[Cảnh báo: Hình ảnh chứa mã độc]';
                    }
                    return '<a href="' . htmlspecialchars($src) . '" class="swipebox">'
                        . '<img loading="lazy" class="bb_img LoadImage" src="' . htmlspecialchars($src) . '" border="2" '
                        . 'onerror="this.onerror=null;this.src=\'' . $loaderror . '\'" '
                        . 'style="border-radius:1%;display:block;margin:0 auto;max-width:70%;max-height:70%"/>'
                        . '</a>';
                }, $matches[0]);

                return '<center>' . $inner . '</center>';
            },
            $string
        );

        // xử lý video
        $parttern = '/\[vid\](.*?)\[\/vid\]/';
        $replacement = '<div class="video-wrapper" style="text-align:center;"><iframe loading="lazy" src="/plugin/video_embed?link=$1" height="315" width="560" scrolling="no" allowfullscreen="" frameborder="0"></iframe></div>';
        $string = preg_replace($parttern, $replacement, $string);

        // xử lý thẻ download
        $parttern = '/\[d\](.*?)\[\/d\]/';
        $replacement = '<center><a href="$1"><button class="btn btn-primary"><i class="fa fa-download"></i> Download</button></a></center>';
        $string = preg_replace($parttern, $replacement, $string);

        // xử lý link
        $string = preg_replace_callback('/\[url=(.*?)\](.*?)\[\/url\]/', function ($matches) {
            $url = trim($matches[1]);
            if (preg_match('/^(javascript|vbscript|data):/i', $url)) {
                return '[Cảnh báo: Liên kết chứa mã độc]';
            }
            return '<i class="fa fa-link fa-spin"></i><a rel="nofollow" target="_blank" href="' . htmlspecialchars($url) . '">' . $matches[2] . '</a>';
        }, $string);

        # url trực tiếp
        $string = preg_replace_callback('/(?:^|\s)(https?:\/\/\S+)/i', function ($matches) {
            $url = htmlspecialchars($matches[1]);
            return "<i class='fa fa-link fa-spin'></i><a rel='nofollow' target='_blank' href='$url'>$url</a>";
        }, $string);

        $string = preg_replace('/\[hr\]/i', '<hr />', $string);
        // Xử lý list lồng nhau (đệ quy)
        while (preg_match('/\[list(?:=(\d))?\](.*?)\[\/list\]/is', $string)) {
            $string = preg_replace_callback('/\[list(?:=(\d))?\](.*?)\[\/list\]/is', function ($matches) {
                $isOrdered = isset($matches[1]) && $matches[1] === '1';
                $tag = $isOrdered ? 'ol' : 'ul';

                // Phân tách các mục trong danh sách
                $items = preg_split('/\[\*\]/', $matches[2], -1, PREG_SPLIT_NO_EMPTY);
                $html = "<$tag>\n";
                foreach ($items as $item) {
                    $html .= '<li>' . trim($item) . "</li>\n";
                }
                $html .= "</$tag>";

                return $html;
            }, $string);
        }

        // xử lý xuống dòng
        $string = nl2br($string);
        $string = str_replace(array_keys($code_blocks), array_values($code_blocks), $string);

        // trả về kết quả sau khi biên dịch từ bbcode sang html
        return $string;
    }
}
