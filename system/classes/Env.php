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

class Env
{
    private static bool $loaded = false;
    private static array $vars = [];

    /**
     * Load environment variables from .env file
     */
    public static function load(?string $filePath = null): void
    {
        $path = $filePath ?: (defined('ROOT') ? ROOT . '.env' : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');
        if (!file_exists($path) || !is_readable($path)) {
            self::$loaded = true;
            return;
        }

        $cacheDir = (defined('SYSTEM') ? SYSTEM : dirname(__DIR__) . DIRECTORY_SEPARATOR) . 'cache';
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'env.cache.php';
        $envMtime = @filemtime($path);
        if ($filePath === null && is_file($cacheFile) && $envMtime !== false && @filemtime($cacheFile) >= $envMtime) {
            $cached = @include $cacheFile;
            if (is_array($cached)) {
                self::$vars = $cached;
                foreach ($cached as $k => $v) {
                    $_ENV[$k] = $v;
                }
                self::$loaded = true;
                return;
            }
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            self::$loaded = true;
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '#') === 0) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));

            if ($key === '') {
                continue;
            }

            // Strip surrounding quotes
            if (strlen($val) >= 2) {
                $first = $val[0];
                $last = $val[strlen($val) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $val = substr($val, 1, -1);
                    if ($first === '"') {
                        $val = str_replace(['\\"', '\\n', '\\r', '\\\\'], ['"', "\n", "\r", '\\'], $val);
                    }
                }
            }

            self::$vars[$key] = $val;
            $_ENV[$key] = $val;
        }

        self::$loaded = true;

        if ($filePath === null) {
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            if (is_dir($cacheDir) && is_writable($cacheDir)) {
                @file_put_contents($cacheFile, "<?php\nreturn " . var_export(self::$vars, true) . ";\n", LOCK_EX);
                if (function_exists('opcache_invalidate')) {
                    @opcache_invalidate($cacheFile, true);
                }
            }
        }
    }

    /**
     * Get an environment variable with type casting support
     */
    public static function get(string $key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }

        $val = self::$vars[$key] ?? $_ENV[$key] ?? getenv($key);
        if ($val === false || $val === null) {
            return $default;
        }

        if (is_string($val)) {
            $lower = strtolower($val);
            if ($lower === 'true' || $lower === '(true)') {
                return true;
            }
            if ($lower === 'false' || $lower === '(false)') {
                return false;
            }
            if ($lower === 'null' || $lower === '(null)') {
                return null;
            }
        }

        return $val;
    }

    /**
     * Parse comma-separated string from .env into an array
     */
    public static function getList(string $key, array $default = []): array
    {
        $val = self::get($key, null);
        if ($val === null) {
            return $default;
        }
        if (is_array($val)) {
            return $val;
        }
        $trimmed = trim((string) $val);
        if ($trimmed === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $trimmed)), fn($v) => $v !== ''));
    }

    /**
     * Parse JSON string from .env into an array
     */
    public static function getJson(string $key, array $default = []): array
    {
        $val = self::get($key, null);
        if ($val === null || $val === '') {
            return $default;
        }
        if (is_array($val)) {
            return $val;
        }
        $decoded = json_decode((string) $val, true);
        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * Update key-value pairs in the .env file while preserving comments and structure
     */
    public static function set(array $updates, ?string $filePath = null): bool
    {
        $path = $filePath ?: (defined('ROOT') ? ROOT . '.env' : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');
        $lines = file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
        if (!is_array($lines)) {
            $lines = [];
        }

        $updatedKeys = [];

        foreach ($lines as $idx => $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '#') === 0) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            if (array_key_exists($key, $updates)) {
                $formattedVal = self::formatValue($updates[$key]);
                $lines[$idx] = $key . '=' . $formattedVal;
                $updatedKeys[$key] = true;

                $rawStr = self::rawString($updates[$key]);
                self::$vars[$key] = $rawStr;
                $_ENV[$key] = $rawStr;
            }
        }

        foreach ($updates as $key => $value) {
            if (!isset($updatedKeys[$key])) {
                $formattedVal = self::formatValue($value);
                $lines[] = $key . '=' . $formattedVal;

                $rawStr = self::rawString($value);
                self::$vars[$key] = $rawStr;
                $_ENV[$key] = $rawStr;
            }
        }

        $content = implode("\n", $lines) . "\n";
        return file_put_contents($path, $content) !== false;
    }

    /**
     * Synchronize the system configuration array back to .env (and optionally system.php)
     */
    public static function saveSystemConfig(array $systemConfig): bool
    {
        $updates = [];

        if (isset($systemConfig['app'])) {
            $app = $systemConfig['app'];
            if (isset($app['name'])) {
                $updates['APP_NAME'] = (string) $app['name'];
            }
            if (isset($app['description'])) {
                $updates['APP_DESCRIPTION'] = (string) $app['description'];
            }
            if (isset($app['keyword'])) {
                $updates['APP_KEYWORD'] = (string) $app['keyword'];
            }
            if (isset($app['site']) && is_array($app['site'])) {
                if (isset($app['site']['scheme'])) {
                    $updates['SITE_SCHEME'] = (string) $app['site']['scheme'];
                }
                if (isset($app['site']['host'])) {
                    $updates['SITE_HOST'] = (string) $app['site']['host'];
                }
                if (isset($app['site']['path'])) {
                    $updates['SITE_PATH'] = (string) $app['site']['path'];
                }
            }
            if (isset($app['default_template'])) {
                $updates['DEFAULT_TEMPLATE'] = (string) $app['default_template'];
            }
            if (isset($app['installed_modules']) && is_array($app['installed_modules'])) {
                $updates['INSTALLED_MODULES'] = implode(',', $app['installed_modules']);
            }
        }

        if (isset($systemConfig['PostList']) && is_array($systemConfig['PostList'])) {
            if (isset($systemConfig['PostList']['order_by']) && is_array($systemConfig['PostList']['order_by'])) {
                $updates['POST_LIST_ORDER_BY'] = implode(',', $systemConfig['PostList']['order_by']);
            }
            if (isset($systemConfig['PostList']['sort']) && is_array($systemConfig['PostList']['sort'])) {
                $updates['POST_LIST_SORT'] = implode(',', $systemConfig['PostList']['sort']);
            }
        }

        if (isset($systemConfig['UserList']) && is_array($systemConfig['UserList'])) {
            if (isset($systemConfig['UserList']['order_by']) && is_array($systemConfig['UserList']['order_by'])) {
                $updates['USER_LIST_ORDER_BY'] = implode(',', $systemConfig['UserList']['order_by']);
            }
            if (isset($systemConfig['UserList']['sort']) && is_array($systemConfig['UserList']['sort'])) {
                $updates['USER_LIST_SORT'] = implode(',', $systemConfig['UserList']['sort']);
            }
        }

        if (isset($systemConfig['Manager']) && is_array($systemConfig['Manager'])) {
            if (isset($systemConfig['Manager']['allow_country']) && is_array($systemConfig['Manager']['allow_country'])) {
                $updates['MANAGER_ALLOW_COUNTRY'] = implode(',', $systemConfig['Manager']['allow_country']);
            }
            if (isset($systemConfig['Manager']['assets'])) {
                $updates['MANAGER_ASSETS'] = (string) $systemConfig['Manager']['assets'];
            }
        }

        if (isset($systemConfig['url_rules']) && is_array($systemConfig['url_rules'])) {
            if (isset($systemConfig['url_rules']['rewrite']) && is_array($systemConfig['url_rules']['rewrite'])) {
                $updates['URL_RULES_REWRITE'] = json_encode($systemConfig['url_rules']['rewrite'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            if (isset($systemConfig['url_rules']['disable']) && is_array($systemConfig['url_rules']['disable'])) {
                $updates['URL_RULES_DISABLE'] = implode(',', $systemConfig['url_rules']['disable']);
            }
        }

        if (isset($systemConfig['security']) && is_array($systemConfig['security'])) {
            if (isset($systemConfig['security']['rate_limit'])) {
                $updates['SECURITY_RATE_LIMIT'] = (string) (int) $systemConfig['security']['rate_limit'];
            }
        }

        if (isset($systemConfig['smtp']) && is_array($systemConfig['smtp'])) {
            $smtp = $systemConfig['smtp'];
            if (isset($smtp['host'])) {
                $updates['SMTP_HOST'] = (string) $smtp['host'];
            }
            if (isset($smtp['port'])) {
                $updates['SMTP_PORT'] = (string) (int) $smtp['port'];
            }
            if (isset($smtp['user'])) {
                $updates['SMTP_USER'] = (string) $smtp['user'];
            }
            if (isset($smtp['pass'])) {
                $updates['SMTP_PASS'] = (string) $smtp['pass'];
            }
            if (isset($smtp['encrypt'])) {
                $updates['SMTP_ENCRYPT'] = (string) $smtp['encrypt'];
            }
            if (isset($smtp['from_email'])) {
                $updates['SMTP_FROM_EMAIL'] = (string) $smtp['from_email'];
            }
            if (isset($smtp['from_name'])) {
                $updates['SMTP_FROM_NAME'] = (string) $smtp['from_name'];
            }
        }

        if (isset($systemConfig['media']) && is_array($systemConfig['media'])) {
            $media = $systemConfig['media'];
            if (isset($media['telegram_upload_mode'])) {
                $updates['TELEGRAM_UPLOAD_MODE'] = (string) $media['telegram_upload_mode'];
            }
            if (isset($media['telegram_bot_token'])) {
                $updates['TELEGRAM_BOT_TOKEN'] = (string) $media['telegram_bot_token'];
            }
            if (isset($media['telegram_chat_id'])) {
                $updates['TELEGRAM_CHAT_ID'] = (string) $media['telegram_chat_id'];
            }
            if (isset($media['cloudflare_worker_url'])) {
                $updates['CLOUDFLARE_WORKER_URL'] = (string) $media['cloudflare_worker_url'];
            }
        }

        $saved = self::set($updates);

        if (defined('SYSTEM')) {
            $systemPath = SYSTEM . 'configs/autoload/system.php';
            $content = "<?php\n\nreturn " . var_export($systemConfig, true) . ";\n";
            @file_put_contents($systemPath, $content, LOCK_EX);
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($systemPath, true);
            }
        }

        // Reload Config singleton if already initialized in Container
        if ($saved && class_exists('Container') && class_exists('Config')) {
            try {
                $cfg = Container::get(Config::class);
                if (method_exists($cfg, 'reload')) {
                    $cfg->reload();
                }
            } catch (\Throwable $e) {
            }
        }

        return $saved;
    }

    private static function rawString($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return (string) $value;
    }

    private static function formatValue($value): string
    {
        $str = self::rawString($value);
        // Use single quotes for JSON strings to keep double quotes clean
        if (strlen($str) > 0 && ($str[0] === '{' || $str[0] === '[') && strpos($str, "'") === false) {
            return "'" . $str . "'";
        }
        $escaped = str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], $str);
        return '"' . $escaped . '"';
    }
}
