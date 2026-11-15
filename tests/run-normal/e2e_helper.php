<?php

class E2EClient
{
    private string $baseUrl;
    private string $cookieFile;
    public int $passed = 0;
    public int $failed = 0;

    private array $errorPatterns = [
        'Fatal error',
        'Uncaught ',
        'SQLSTATE[',
        'Parse error',
        'Warning:',
        'Notice:',
    ];

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sketchcms_e2e_' . getmypid() . '.cookies';
    }

    public function __destruct()
    {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    public function request(string $method, string $path, array $postData = []): array
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_USERAGENT      => 'SketchCMS-E2E/1.0',
        ];
        if (strtoupper($method) === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($postData);
        }
        curl_setopt_array($ch, $opts);
        $body = (string) curl_exec($ch);
        $err = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effectiveUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        return [
            'status' => $status,
            'body'   => $body,
            'url'    => $effectiveUrl,
            'error'  => $err,
        ];
    }

    public function extractCsrf(string $html): ?string
    {
        if (preg_match('/name=["\'](?:csrf_token|_token)["\']\s+value=["\']([^"\']+)["\']/i', $html, $m)) {
            return $m[1];
        }
        if (preg_match('/value=["\']([^"\']+)["\']\s+name=["\'](?:csrf_token|_token)["\']/i', $html, $m)) {
            return $m[1];
        }
        return null;
    }

    public function check(string $label, array $res, ?string $expectText = null): bool
    {
        if ($res['error'] !== '') {
            $this->failed++;
            echo "  [FAIL] {$label} -> cURL error: {$res['error']}\n";
            return false;
        }

        foreach ($this->errorPatterns as $pattern) {
            if (stripos($res['body'], $pattern) !== false) {
                $this->failed++;
                echo "  [FAIL] {$label} -> Found '{$pattern}' in response (HTTP {$res['status']})\n";
                return false;
            }
        }

        if ($res['status'] >= 500 || $res['status'] === 0) {
            $this->failed++;
            echo "  [FAIL] {$label} -> HTTP {$res['status']}\n";
            return false;
        }

        if ($expectText !== null && stripos($res['body'], $expectText) === false) {
            $this->failed++;
            echo "  [FAIL] {$label} -> Expected text '{$expectText}' not found (HTTP {$res['status']})\n";
            return false;
        }

        $this->passed++;
        echo "  [PASS] {$label} (HTTP {$res['status']})\n";
        return true;
    }
}
