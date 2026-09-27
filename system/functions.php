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

if (!function_exists('_e')) {
    function _e(string $text)
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        return trim($text);
    }
}

if (!function_exists('captchaSrc')) {
    /**
     * Get src of captcha image
     *
     * @return string
     */
    function captchaSrc()
    {
        return url('captcha') . '?v=' . time();
    }
}

if (!function_exists('config')) {
    /**
     * Get autoload config
     *
     * @param string|null $path
     * @param mixed $default
     * @return Config|mixed
     */
    function config(string $path = null, $default = null)
    {
        $config = Container::get(Config::class);

        if (is_null($path)) {
            return $config;
        }

        return $config->get($path, $default);
    }
}

if (!function_exists('display_error')) {
    function display_error($error)
    {
        if (is_array($error)) {
            if (sizeof($error) === 1) {
                $error = array_pop($error);
            } else {
                $error = '- ' . implode('<br />- ', $error);
            }
        }

        return $error;
    }
}

if (!function_exists('paging')) {
    function paging($url, $currentPage, $totalPages, $numPagesToShow = 5)
    {
        $currentPage = intval($currentPage);
        $totalPages = intval($totalPages);
        $numPagesToShow = intval($numPagesToShow);
        $pagination = '';

        if ($totalPages > 1) {
            $pagination .= '<div class="dw-pagination">';

            // First and Previous Links
            if ($currentPage > 1) {
                $pagination .= '<a href="' . $url . '1" title="First"><i class="fa fa-angle-double-left"></i></a>';
                $pagination .= '<a href="' . $url . ($currentPage - 1) . '" title="Previous"><i class="fa fa-angle-left"></i></a>';
            }

            // Determine the start and end page numbers
            $startPage = max(1, $currentPage - intval($numPagesToShow / 2));
            $endPage = min($totalPages, $startPage + $numPagesToShow - 1);

            // Adjust the start page if we're near the end
            if ($endPage - $startPage < $numPagesToShow) {
                $startPage = max(1, $endPage - $numPagesToShow + 1);
            }

            // Page Numbers
            for ($i = $startPage; $i <= $endPage; $i++) {
                if ($i == $currentPage) {
                    $pagination .= '<span class="active"><span>' . $i . '</span></span>';
                } else {
                    $pagination .= '<a href="' . $url . $i . '">' . $i . '</a>';
                }
            }

            // Next and Last Links
            if ($currentPage < $totalPages) {
                $pagination .= '<a href="' . $url . ($currentPage + 1) . '" title="Next"><i class="fa fa-angle-right"></i></a>';
                $pagination .= '<a href="' . $url . $totalPages . '" title="Last"><i class="fa fa-angle-double-right"></i></a>';
            }

            $pagination .= '</div>';
        }

        return $pagination;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $uri = '/')
    {
        header('Location: ' . SITE_PATH . $uri);
        exit;
    }
}

if (!function_exists('request')) {
    /**
     * Get request instance
     *
     * @return Request
     */
    function request()
    {
        return Container::get(Request::class);
    }
}

if (!function_exists('url')) {
    function url(string $path = '', $absulute = true)
    {
        if ($absulute) {
            return SITE_URL . '/' . ltrim($path, '/');
        }

        return (SITE_PATH ? '/' . ltrim(SITE_PATH, '/') : '')
            . '/' . ltrim($path, '/');
    }
}

if (!function_exists('view')) {
    /**
     * Get template instance or render a view
     *
     * @param string|null $template
     * @param array $data
     * @return Template|string
     */
    function view($template = null, array $data = [])
    {
        /** @var Template $view */
        $view = Container::get(Template::class);

        if ($template === null) {
            return $view;
        }

        return $view->render($template, $data);
    }
}

if (!function_exists('display_layout')) {
    function display_layout()
    {
        $arrUA = mb_strtolower($_SERVER['HTTP_USER_AGENT']);
        if (preg_match('/windows|ipod|ipad|iphone|android|webos|blackberry|midp/', $arrUA) && preg_match('/mobile/', $arrUA)) {
            return 'mobile';
        } elseif (preg_match('/mobile/', $arrUA))
            return 'mobile';
        else
            return 'desktop';
    }
}

if (!function_exists('get_template')) {
    function get_template($path = null)
    {
        // Lấy danh sách template hợp lệ
        $valid_templates = [];
        $dirs = scandir(TEMPLATES);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            $full_path = TEMPLATES . $dir . DIRECTORY_SEPARATOR;
            if (is_dir($full_path) && file_exists($full_path . 'desc.json')) {
                $valid_templates[] = $dir;
            }
        }
        // Nếu không có template nào, tránh lỗi
        if (empty($valid_templates)) {
            return 'default';
        }
        // Nếu $path là tên template có tồn tại, thì trả về desc.json
        if ($path !== null && in_array($path, $valid_templates)) {
            $desc_file = TEMPLATES . $path . DIRECTORY_SEPARATOR . 'desc.json';
            if (file_exists($desc_file)) {
                $json = file_get_contents($desc_file);
                return json_decode($json, true);
            }
            return []; // nếu không có file (dù hiếm)
        }
        // Luôn sử dụng template từ config (giao diện được chọn trong manager)
        $configured_template = config('system.app.default_template');
        if (empty($configured_template) || !in_array($configured_template, $valid_templates)) {
            $configured_template = $valid_templates[0] ?? 'default';
        }
        // Trả về tên template (không phải path)
        return '/' . $configured_template;
    }
}

if (!function_exists('ago')) {
    function ago($time_ago)
    {
        $time_ago = intval($time_ago);
        $time_current = date('U');
        $time_seconds = $time_current - $time_ago;
        $time_minutes = floor($time_seconds / 60);
        $time_day = date('z', $time_current) - date('z', $time_ago);
        $fulltime = date('d.m.Y - H:i', $time_ago);
        $minitime = date('H:i', $time_ago);

        if ($time_day == 0) {
            if ($time_seconds <= 60) {
                return $time_seconds . ' seconds ago';
            } elseif ($time_minutes <= 60) {
                return $time_minutes . ' minutes ago';
            } else {
                return 'Today, ' . $minitime;
            }
        } elseif ($time_day == 1) {
            return 'Yesterday, ' . $minitime;
        } else {
            return $fulltime;
        }
    }
}

if (!function_exists('RoleColor')) {
    function RoleColor($UserDetail = [], $get = 'color')
    {
        $user_name = htmlspecialchars($UserDetail['name']);
        $user_name = str_replace('ㅤ', ' ', $user_name);
        $user_name = trim($user_name);
        $user_name = strlen($user_name) >= 3 ? $user_name : htmlspecialchars($UserDetail['nick']);

        $user_level = intval($UserDetail['level']);
        $register_time = isset($UserDetail['reg']) ? $UserDetail['reg'] : time();
        $days = (int) floor((time() - $register_time) / 86400);

        // Xác định position
        if ($user_level >= 120) {
            if ($user_level == 120) {
                $position = 'Contributor';
            } elseif ($user_level == 121) {
                $position = 'Moderator';
            } elseif ($user_level >= 122 && $user_level < 126) {
                $position = 'Super Moderator';
            } elseif ($user_level == 126) {
                $position = 'Administrator';
            } elseif ($user_level >= 127) {
                $position = 'Developer';
            } else {
                $position = 'Contributor';
            }
        } else {
            if ($days >= 365 * 10) {
                $position = 'Legend';
            } elseif ($days >= 365 * 8) {
                $position = 'Veteran';
            } elseif ($days >= 365 * 5) {
                $position = 'Senior';
            } elseif ($days >= 365 * 2) {
                $position = 'Junior';
            } elseif ($days >= 30) {
                $position = 'Member';
            } else {
                $position = 'Newbie';
            }
        }

        // Gán màu theo position
        $colors = [
            'Newbie' => '#2F4F4F',
            'Member' => '#4169E1',
            'Junior' => '#228B22',
            'Senior' => '#663399',
            'Veteran' => '#FF8C00',
            'Legend' => '#DC143C',
            'Contributor' => '#20B2AA',
            'Moderator' => '#00CED1',
            'Super Moderator' => '#8A2BE2',
            'Administrator' => '#FF4500',
            'Developer' => '#e74c3c',
        ];

        $color = $colors[$position] ?? '#000000';

        // Xử lý banned
        if ($user_level < 0) {
            $position = 'Banned';
            $color = '#D3D3D3';
        }

        // Trả về theo yêu cầu
        switch ($get) {
            case 'position':
                return "<b>$position</b>";
                //return $days;
            case 'color':
            default:
                if ($user_level < 0) {
                    return '<a href="/user/' . htmlspecialchars($UserDetail['nick']) . '" style="font-weight:500;color:' . $color . ';text-decoration:line-through;">' . $user_name . '</a>';
                } else {
                    return '<b><a href="/user/' . htmlspecialchars($UserDetail['nick']) . '" style="font-weight:700;color:' . $color . ';border-radius:5px;display:inline-block;margin:1px;text-shadow:0 0 0.7em ' . $color . '">' . $user_name . '</a></b>';
                }
        }
    }
}

if (!function_exists('getAvtUser')) {
    function getAvtUser($UserDetail = [])
    {
        $avatarBaseURL = 'https://cdn.jsdelivr.net/gh/jesuisnk/dorew-assets/avatar/';
        $avatar0 = $UserDetail['avatar'];
        $userlv = $UserDetail['level'];

        // Mapping of avatar numbers to filenames
        $avatarMap = [
            '1' => 'badger.png',
            '2' => 'bear.png',
            '3' => 'bull.png',
            '4' => 'camel.png',
            '5' => 'cat.png',
            '6' => 'dog.png',
            '7' => 'dolphin.png',
            '8' => 'duck.png',
            '9' => 'hamster.png',
            '10' => 'hippo.png',
            '11' => 'kangaroo.png',
            '12' => 'koala.png',
            '13' => 'lama.png',
            '14' => 'monkey.png',
            '15' => 'moose.png',
            '16' => 'mouse.png',
            '17' => 'owl.png',
            '18' => 'penguin.png',
            '19' => 'pig.png',
            '20' => 'rabbit.png',
            '21' => 'raven.png',
            '22' => 'rooster.png',
            '23' => 'seal.png',
            '24' => 'sheep.png',
            '25' => 'snake.png',
            '26' => 'turtle.png',
            '27' => 'unicorn.png',
            '28' => 'vulture.png',
            '29' => 'zebra.png',
        ];

        // Check if avatar0 is within range
        if ($avatar0 > '0' && $avatar0 < '30') {
            $avatarFilename = isset($avatarMap[$avatar0]) ? $avatarMap[$avatar0] : '';
            $avatar0 = $avatarBaseURL . $avatarFilename;
        } else {
            $avatar0 = str_replace(['.jpg', '.png'], ['b.jpg', 'b.png'], $avatar0);
        }
        if ($userlv < '0') {
            return 'https://i.imgur.com/COuyZhV.jpg';
        } else {
            return $avatar0;
        }
    }
}

if (!function_exists('getCoverUser')) {
    function getCoverUser($UserDetail = [])
    {
        $coverBaseURL = 'https://dorew-site.github.io/assets/cover/';
        $cover0 = $UserDetail['cover'];
        if ($cover0 > '0' && $cover0 <= '18') {
            $cover0 = $coverBaseURL . $cover0 . '.jpg';
        }
        return $cover0;
    }
}

if (!function_exists('showAvtUser')) {
    function showAvtUser($avatar0)
    {
        $avatars = [
            '1' => 'badger',
            '2' => 'bear',
            '3' => 'bull',
            '4' => 'camel',
            '5' => 'cat',
            '6' => 'dog',
            '7' => 'dolphin',
            '8' => 'duck',
            '9' => 'hamster',
            '10' => 'hippo',
            '11' => 'kangaroo',
            '12' => 'koala',
            '13' => 'lama',
            '14' => 'monkey',
            '15' => 'moose',
            '16' => 'mouse',
            '17' => 'owl',
            '18' => 'penguin',
            '19' => 'pig',
            '20' => 'rabbit',
            '21' => 'raven',
            '22' => 'rooster',
            '23' => 'seal',
            '24' => 'sheep',
            '25' => 'snake',
            '26' => 'turtle',
            '27' => 'unicorn',
            '28' => 'vulture',
            '29' => 'zebra'
        ];

        return isset($avatars[$avatar0])
            ? 'https://cdn.jsdelivr.net/gh/jesuisnk/dorew-assets/avatar/' . $avatars[$avatar0] . '.png'
            : $avatar0;
    }
}

if (!function_exists('ForumRank')) {
    function ForumRank($current_point = 0, $get = 'league_image')
    {
        $ranks = [
            ['max' => 9,    'name' => 'Unranked', 'exp' => '0', 'reward' => 2500],
            ['max' => 19,   'name' => 'Bronze',   'exp' => '3', 'reward' => 5000],
            ['max' => 29,   'name' => 'Bronze',   'exp' => '2', 'reward' => 10000],
            ['max' => 39,   'name' => 'Bronze',   'exp' => '1', 'reward' => 15000],
            ['max' => 69,   'name' => 'Silver',   'exp' => '3', 'reward' => 20000],
            ['max' => 99,   'name' => 'Silver',   'exp' => '2', 'reward' => 25000],
            ['max' => 139,  'name' => 'Silver',   'exp' => '1', 'reward' => 30000],
            ['max' => 179,  'name' => 'Gold',     'exp' => '3', 'reward' => 35000],
            ['max' => 219,  'name' => 'Gold',     'exp' => '2', 'reward' => 40000],
            ['max' => 259,  'name' => 'Gold',     'exp' => '1', 'reward' => 45000],
            ['max' => 289,  'name' => 'Crystal',  'exp' => '3', 'reward' => 50000],
            ['max' => 319,  'name' => 'Crystal',  'exp' => '2', 'reward' => 55000],
            ['max' => 369,  'name' => 'Crystal',  'exp' => '1', 'reward' => 60000],
            ['max' => 409,  'name' => 'Master',   'exp' => '3', 'reward' => 65000],
            ['max' => 459,  'name' => 'Master',   'exp' => '2', 'reward' => 70000],
            ['max' => 499,  'name' => 'Master',   'exp' => '1', 'reward' => 75000],
            ['max' => 559,  'name' => 'Champion', 'exp' => '3', 'reward' => 80000],
            ['max' => 609,  'name' => 'Champion', 'exp' => '2', 'reward' => 85000],
            ['max' => 659,  'name' => 'Champion', 'exp' => '1', 'reward' => 90000],
            ['max' => 719,  'name' => 'Titan',    'exp' => '3', 'reward' => 94000],
            ['max' => 789,  'name' => 'Titan',    'exp' => '2', 'reward' => 980000],
            ['max' => 859,  'name' => 'Titan',    'exp' => '1', 'reward' => 102000],
            ['max' => PHP_INT_MAX, 'name' => 'Legend', 'exp' => '0', 'reward' => 106000],
        ];

        foreach ($ranks as $rank) {
            if ($current_point <= $rank['max']) {
                $rank_name = $rank['name'];
                $rank_exp = $rank['exp'];
                $rank_reward = $rank['reward'];
                break;
            }
        }

        switch ($get) {
            case 'reward':
                return $rank_reward;
            case 'league':
                return $rank_name;
            case 'league_image':
                return '<img src="https://dorew-site.github.io/assets/rank/' . $rank_name . '_League.png" alt="' . $rank_name . '" style="max-width:30px;max-height:30px"/>';
            case 'league_icon':
                return '<img src="https://dorew-site.github.io/assets/rank/' . $rank_name . '_League.png" alt="' . $rank_name . '" style="max-width:15px;max-height:15px"/>';
            case 'rank':
                return ($rank_name == 'Unranked' ? 'Không xếp hạng' : $rank_name) . ($rank_exp != '0' ? $rank_exp : '');
            case 'rank_image_url':
                if ($rank_name == 'Unranked') {
                    $get_rank = $rank_name . '_League';
                } elseif ($rank_name == 'Legend') {
                    $get_rank = $rank_name . '/' . $rank_name;
                } else {
                    $get_rank = $rank_name . '/' . $rank_name . $rank_exp;
                }
                return 'https://dorew-site.github.io/assets/rank/' . $get_rank . '.png';
            case 'rank_image':
                if ($rank_name == 'Unranked') {
                    $get_rank = $rank_name . '_League';
                } elseif ($rank_name == 'Legend') {
                    $get_rank = $rank_name . '/' . $rank_name;
                } else {
                    $get_rank = $rank_name . '/' . $rank_name . $rank_exp;
                }
                return '<img src="https://dorew-site.github.io/assets/rank/' . $get_rank . '.png" alt="' . $rank_name . '" style="width:30px;height:30px"/>';
            case 'rank_icon':
                if ($rank_name == 'Unranked') {
                    $get_rank = $rank_name . '_League';
                } elseif ($rank_name == 'Legend') {
                    $get_rank = $rank_name . '/' . $rank_name;
                } else {
                    $get_rank = $rank_name . '/' . $rank_name . $rank_exp;
                }
                return '<img src="https://dorew-site.github.io/assets/rank/' . $get_rank . '.png" alt="' . $rank_name . '" style="width:20px;height:20px"/>';
            default:
                return $rank_name . '|' . $rank_exp;
        }
    }
}

if (!function_exists('e_pass')) {
    function e_pass($string = null, $type = 0)
    {
        $encrypt = null;
        $e1_map = array_merge(
            array_combine(range('0', '4'), array_reverse(range('5', '9'))), // 0-4 <-> 5-9
            array_combine(range('5', '9'), array_reverse(range('0', '4'))), // 5-9 <-> 0-4
            array_combine(range('a', 'm'), range('z', 'n')),               // a-m -> z-n
            array_combine(range('z', 'n'), range('a', 'm')),               // z-n -> a-m
            array_combine(range('A', 'M'), range('Z', 'N')),               // A-M -> Z-N
            array_combine(range('Z', 'N'), range('A', 'M'))                // Z-N -> A-M
        );
        $e1 = strtr($string, $e1_map);
        $e2 = md5($e1);
        if ($type == 1) {
            $encrypt = $e1;
        } else {
            $encrypt = $e2;
        }
        return $encrypt;
    }
}

if (!function_exists('verify_password_seamless')) {
    function verify_password_seamless($input_pass, $db_pass, $user_id, $pdo)
    {
        // Kiểm tra Bcrypt (Mật khẩu đã được nâng cấp)
        if (password_verify($input_pass, $db_pass)) {
            return true;
        }

        // Kiểm tra Fallback MD5 cũ (Seamless Migration)
        $old_hash = e_pass($input_pass);
        if ($old_hash === $db_pass) {
            // Nâng cấp tự động sang Bcrypt
            $new_hash = password_hash($input_pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET pass = ? WHERE id = ?");
            $stmt->execute([$new_hash, $user_id]);
            return true;
        }

        return false;
    }
}

if (!function_exists('e_pass_decode')) {
    function e_pass_decode($e1_string)
    {
        $e1_map = array_merge(
            array_combine(range('0', '4'), array_reverse(range('5', '9'))),
            array_combine(range('5', '9'), array_reverse(range('0', '4'))),
            array_combine(range('a', 'm'), range('z', 'n')),
            array_combine(range('z', 'n'), range('a', 'm')),
            array_combine(range('A', 'M'), range('Z', 'N')),
            array_combine(range('Z', 'N'), range('A', 'M'))
        );
        $e1_map_reverse = array_flip($e1_map);
        return strtr($e1_string, $e1_map_reverse);
    }
}

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken()
    {
        $encrypt = bin2hex(random_bytes(32));
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = $encrypt;
        }
    }
}

if (!function_exists('isCSRFTokenValid')) {
    function isCSRFTokenValid($token)
    {
        $output = null;
        $isCSRFTokenValid = isset($_SESSION['csrf_token']) && $token === $_SESSION['csrf_token'];
        if (!$isCSRFTokenValid) {
            $output = 'error';
        }
        return $output;
    }
}

if (!function_exists('unsetCSRFToken')) {
    function unsetCSRFToken()
    {
        if (isset($_SESSION['csrf_token'])) {
            unset($_SESSION['csrf_token']);
        }
        return true;
    }
}

if (!function_exists('getCSRFToken')) {
    function getCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            generateCSRFToken();
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('checkExtension')) {
    function checkExtension($one)
    {
        static $lookup = null;
        if ($lookup === null) {
            $lookup = array_merge(
                array_fill_keys(['jpg', 'png', 'webp', 'psd', 'heic'], 'file-image-o'),
                array_fill_keys(['mp4', 'mkv', 'webm', 'flv', '3gp'], 'file-video-o'),
                array_fill_keys(['mp3', 'mkv', 'm4a', 'flac', 'wav'], 'file-audio-o'),
                array_fill_keys(['txt', 'md'], 'file-text-o'),
                array_fill_keys(['docx', 'doc', 'odt'], 'file-word-o'),
                array_fill_keys(['xls', 'xlsx'], 'file-excel-o'),
                array_fill_keys(['ppt', 'pptx'], 'file-powerpoint-o'),
                array_fill_keys(['pdf'], 'file-pdf-o'),
                array_fill_keys(['zip', 'rar', '7z', 'tar'], 'file-archive-o'),
                array_fill_keys(['cpp', 'cs', 'php', 'html', 'js', 'py'], 'file-code-o'),
                array_fill_keys(['sql'], 'database')
            );
        }

        if (isset($lookup[$one])) {
            return $lookup[$one];
        }
        $ext = strtolower(pathinfo($one, PATHINFO_EXTENSION));
        return $lookup[$ext] ?? 'file-o';
    }
}

if (!function_exists('checkRateLimit')) {
    function checkRateLimit($key, $seconds = 3)
    {
        $session_key = 'rate_limit_' . $key;
        if (isset($_SESSION[$session_key])) {
            if (time() - $_SESSION[$session_key] < $seconds) {
                return false;
            }
        }
        $_SESSION[$session_key] = time();
        return true;
    }
}

if (!function_exists('FileSizeFormat')) {
    function FileSizeFormat($byte)
    {
        if ($byte >= 1073741824) {
            $show = round($byte / 1073741824, 2, PHP_ROUND_HALF_DOWN) . ' GB';
        } elseif ($byte >= 1048576) {
            $show = round($byte / 1048576, 2, PHP_ROUND_HALF_DOWN) . ' MB';
        } elseif ($byte >= 1024) {
            $show = round($byte / 1024, 2, PHP_ROUND_HALF_DOWN) . ' Kb';
        } else {
            $show = $byte . ' byte';
        }
        return $show;
    }
}

if (!function_exists('checkDarkMode')) {
    function checkDarkMode()
    {
        if (isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled') {
            echo ' class="dark-mode"';
        }
    }
}

if (!function_exists('checkIpFlood')) {
    function checkIpFlood($ip, $router)
    {
        $url = "https://ipinfo.io/{$ip}/json";
        $response = @file_get_contents($url);
        if ($response === FALSE) {
            die("Không thể kết nối tới ipinfo.io.");
        }
        $data = json_decode($response, true);
        if (isset($data['country'])) {
            $southeastAsiaCountries = ['ID', 'MY', 'SG', 'TH', 'PH', 'BN', 'KH', 'LA', 'MM', 'TL', 'VN'];
            if (in_array($data['country'], $southeastAsiaCountries)) {
                $router->add('/register', 'UserController@register', 'GET|POST');
                #} else {
                #    die("Welcome to Dorew!");
            }
            #} else {
            #    die("Welcome to Dorew!");
        }
    }
}
if (!function_exists('atob')) {
    function atob($encodedInput = null)
    {
        if (!$encodedInput) {
            return 'There is not encodedInput parameter in atob()';
        } else {
            $decodedData = base64_decode($encodedInput);
            return utf8_decode($decodedData);
        }
    }
}
if (!function_exists('btoa')) {
    function btoa($input = null)
    {
        if (!$input) {
            return 'There is not input parameter in btoa()';
        } else {
            $data = utf8_encode($input);
            return base64_encode($data);
        }
    }
}

if (!function_exists('GetRoleColorStr')) {
    function GetRoleColorStr($UserDetail = [])
    {
        $user_level = intval($UserDetail['level']);
        $register_time = isset($UserDetail['reg']) ? $UserDetail['reg'] : time();
        $days = (int) floor((time() - $register_time) / 86400);

        if ($user_level >= 120) {
            if ($user_level == 120) {
                $position = 'Contributor';
            } elseif ($user_level == 121) {
                $position = 'Moderator';
            } elseif ($user_level >= 122 && $user_level < 126) {
                $position = 'Super Moderator';
            } elseif ($user_level == 126) {
                $position = 'Administrator';
            } elseif ($user_level >= 127) {
                $position = 'Developer';
            } else {
                $position = 'Contributor';
            }
        } else {
            if ($days >= 365 * 10) {
                $position = 'Legend';
            } elseif ($days >= 365 * 8) {
                $position = 'Veteran';
            } elseif ($days >= 365 * 5) {
                $position = 'Senior';
            } elseif ($days >= 365 * 2) {
                $position = 'Junior';
            } elseif ($days >= 30) {
                $position = 'Member';
            } else {
                $position = 'Newbie';
            }
        }

        $colors = [
            'Newbie' => '#2F4F4F',
            'Member' => '#4169E1',
            'Junior' => '#228B22',
            'Senior' => '#663399',
            'Veteran' => '#FF8C00',
            'Legend' => '#DC143C',
            'Contributor' => '#20B2AA',
            'Moderator' => '#00CED1',
            'Super Moderator' => '#8A2BE2',
            'Administrator' => '#FF4500',
            'Developer' => '#e74c3c',
        ];

        $color = $colors[$position] ?? '#000000';

        if ($user_level < 0) {
            $color = '#D3D3D3';
        }

        return $color;
    }
}

if (!function_exists('module_menu')) {
    function module_menu($module_path, $content)
    {
        // Chống Path Traversal (chỉ cho phép chữ, số, gạch dưới, gạch ngang, và dấu gạch chéo / cho thư mục con)
        $module_path = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $module_path);
        if (empty($module_path) || strpos($module_path, '//') !== false) {
            return '';
        }

        $full_path = dirname(__DIR__) . '/app/modules/' . $module_path;

        if (is_dir($full_path)) {
            // Lấy tên module từ phần cuối của path (vd: custom_module/custom_abc -> custom_abc)
            $parts = explode('/', $module_path);
            $modulename = end($parts);

            // Danh sách các file bắt buộc phải có cho mọi module
            $required_files = [
                $modulename . 'Controller.php',
                $modulename . 'Model.php',
                $modulename . 'Library.php',
                $modulename . 'Router.php',
                'description.json'
            ];

            // Nếu là custom_module thì yêu cầu thêm 2 file sql
            $is_custom_module = strpos($module_path, 'custom_module/') === 0;
            if ($is_custom_module) {
                $required_files[] = $modulename . '_install.sql';
                $required_files[] = $modulename . '_uninstall.sql';
            }

            // Kiểm tra từng file
            foreach ($required_files as $file) {
                if (!file_exists($full_path . '/' . $file)) {
                    return ''; // Nếu thiếu bất kỳ file nào, coi như module không hợp lệ
                }
            }

            // Kiểm tra thư mục tpl (chỉ bắt buộc với custom_module)
            if ($is_custom_module && !is_dir($full_path . '/tpl')) {
                return '';
            }

            // Lọc XSS cho content nhưng vẫn cho phép các thẻ HTML an toàn dùng trong menu
            $allowed_tags = '<a><i><span><div><button><ul><li><b><strong><em><p><img>';
            return strip_tags($content, $allowed_tags);
        }

        return '';
    }
}

if (!function_exists('module_menu_check')) {
    function module_menu_check($module_path)
    {
        static $checked_modules = []; // Bộ nhớ đệm tĩnh

        // Chống Path Traversal
        $module_path = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $module_path);
        if (empty($module_path) || strpos($module_path, '//') !== false) {
            return false;
        }

        // Nếu đã kiểm tra, trả về ngay lập tức
        if (isset($checked_modules[$module_path])) {
            return $checked_modules[$module_path];
        }

        $full_path = dirname(__DIR__) . '/app/modules/' . $module_path;

        if (is_dir($full_path)) {
            $parts = explode('/', $module_path);
            $modulename = end($parts);

            $required_files = [
                $modulename . 'Controller.php',
                $modulename . 'Model.php',
                $modulename . 'Library.php',
                $modulename . 'Router.php',
                'description.json'
            ];

            $is_custom_module = strpos($module_path, 'custom_module/') === 0;
            if ($is_custom_module) {
                $required_files[] = $modulename . '_install.sql';
                $required_files[] = $modulename . '_uninstall.sql';
            }

            foreach ($required_files as $file) {
                if (!file_exists($full_path . '/' . $file)) {
                    $checked_modules[$module_path] = false;
                    return false;
                }
            }

            if ($is_custom_module && !is_dir($full_path . '/tpl')) {
                $checked_modules[$module_path] = false;
                return false;
            }

            $checked_modules[$module_path] = true;
            return true;
        }

        $checked_modules[$module_path] = false;
        return false;
    }
}
