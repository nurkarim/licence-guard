<?php

if (!function_exists('licence_guard')) {
    function licence_guard(string $product, string $basePath): void
    {
        $product = licence_guard_product($product);
        $GLOBALS['__licence_guard_product'] = $product;

        if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            return;
        }

        $host = licence_guard_host();
        if (in_array($host, ['127.0.0.1', '::1', 'localhost'], true)) {
            return;
        }

        if ($product === '') {
            licence_guard_stop('Licence product name is missing.');
        }

        $basePath = rtrim($basePath, '/\\');
        $storage = $basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR;
        if (!is_file($storage . 'installed.lock')) {
            return;
        }

        $path = rawurldecode(strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/');
        if (preg_match('#/(installer|licence)(/|$)#', $path) === 1) {
            return;
        }

        $token = is_file($storage . 'licence.lock') ? (string) file_get_contents($storage . 'licence.lock') : '';
        if (licence_guard_status($token, $host, $product) !== 'ok') {
            header('Location: /licence', true, 302);
            exit;
        }
    }

    function licence_guard_status(string $token, string $host, string $product): string
    {
        $product = licence_guard_product($product);
        $token = preg_replace('/^\xEF\xBB\xBF/', '', $token) ?? $token;
        $token = preg_replace('/\s+/', '', $token) ?? '';
        if ($product === '' || $token === '' || strlen($token) > 8000 || substr_count($token, '.') !== 1) {
            return 'Upload the licence file you received.';
        }

        [$payloadB64, $sigB64] = explode('.', $token, 2);
        $json = licence_guard_b64($payloadB64);
        $sig = licence_guard_b64($sigB64);
        if (!is_string($json) || $json === '' || !is_string($sig) || strlen($sig) < 128 || strlen($sig) > 512) {
            return 'This licence file is not valid. Ask your provider for a new file for ' . $host . '.';
        }

        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            return 'This licence file is not valid. Ask your provider for a new file for ' . $host . '.';
        }

        $authorityFile = __DIR__ . DIRECTORY_SEPARATOR . 'authority.php';
        $authority = is_file($authorityFile) ? include $authorityFile : null;
        if (!is_array($authority) || empty($authority['public_sha256']) || empty($authority['public_pem'])) {
            return 'This copy of the software cannot check licences.';
        }

        $pem = licence_guard_pem((string) $authority['public_pem']);
        if (!hash_equals((string) $authority['public_sha256'], hash('sha256', $pem))) {
            return 'This copy of the software cannot check licences.';
        }

        $key = openssl_pkey_get_public($pem);
        if ($key === false || openssl_verify($payloadB64, $sig, $key, OPENSSL_ALGO_SHA256) !== 1) {
            return 'This licence file is not valid. Ask your provider for a new file for ' . $host . '.';
        }

        if (licence_guard_product((string) ($payload['p'] ?? '')) !== $product) {
            return 'This licence file is for another software.';
        }

        $domain = licence_guard_host((string) ($payload['d'] ?? ''));
        if ($domain === '' || !hash_equals($domain, $host)) {
            return 'This licence is for ' . ($domain !== '' ? $domain : 'another website') . '. This website is ' . $host . '.';
        }

        $exp = (int) ($payload['exp'] ?? 0);
        if ($exp !== 0 && time() > $exp) {
            return 'This licence has expired. Ask your provider for a new file.';
        }

        return 'ok';
    }

    function licence_guard_product(string $product): string
    {
        $product = strtolower(trim($product));

        return preg_match('/^[a-z0-9][a-z0-9_-]{0,40}$/', $product) === 1 ? $product : '';
    }

    function licence_guard_host(string $host = ''): string
    {
        if ($host === '') {
            $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        }
        $host = strtolower(trim($host));
        if (str_contains($host, '://')) {
            $parsed = parse_url($host, PHP_URL_HOST);
            $host = is_string($parsed) ? $parsed : '';
        }
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        $host = trim($host, '[]');

        return preg_replace('/^www\./', '', $host) ?? $host;
    }

    function licence_guard_b64(string $data): string|false
    {
        $data = strtr($data, '-_', '+/');
        $pad = strlen($data) % 4;
        if ($pad !== 0) {
            $data .= str_repeat('=', 4 - $pad);
        }

        return base64_decode($data, true);
    }

    function licence_guard_pem(string $pem): string
    {
        $pem = str_replace("\r\n", "\n", trim($pem));

        return str_ends_with($pem, "\n") ? $pem : $pem . "\n";
    }

    function licence_guard_stop(string $message): void
    {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $message;
        exit;
    }
}
