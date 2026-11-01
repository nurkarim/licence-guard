<?php

namespace Epixel\LicenceGuard;

use Illuminate\Http\Request;

class LicenceChecker
{
    public static function product(): string
    {
        return licence_guard_product((string) ($GLOBALS['__licence_guard_product'] ?? ''));
    }

    public static function check(string $product): void
    {
        $request = request();
        if (self::isLocal($request) || self::isOpenPath($request) || !is_file(storage_path('installed.lock'))) {
            return;
        }

        if (licence_guard_product($product) === '' || !self::licensed($product)) {
            self::block();
        }
    }

    public static function licensed(string $product): bool
    {
        if (!is_file(storage_path('licence.lock'))) {
            return false;
        }

        $token = (string) file_get_contents(storage_path('licence.lock'));

        return licence_guard_status($token, self::host(request()), licence_guard_product($product)) === 'ok';
    }

    public static function activate(string $product, string $token): array
    {
        $product = self::product() !== '' ? self::product() : licence_guard_product($product);
        if ($product === '') {
            return ['ok' => false, 'message' => 'Add the licence guard line to public/index.php before uploading a file.'];
        }

        $status = licence_guard_status($token, self::host(request()), $product);
        if ($status !== 'ok') {
            return ['ok' => false, 'message' => $status];
        }

        $clean = preg_replace('/\s+/', '', preg_replace('/^\xEF\xBB\xBF/', '', $token) ?? $token) ?? '';
        if (file_put_contents(storage_path('licence.lock'), $clean, LOCK_EX) === false) {
            return ['ok' => false, 'message' => 'The licence could not be saved on this server.'];
        }

        return ['ok' => true, 'message' => 'Licence activated.'];
    }

    public static function summary(string $product): ?string
    {
        $token = is_file(storage_path('licence.lock')) ? (string) file_get_contents(storage_path('licence.lock')) : '';
        if (licence_guard_status($token, self::host(request()), licence_guard_product($product)) !== 'ok') {
            return null;
        }

        $token = preg_replace('/\s+/', '', $token) ?? '';
        $json = licence_guard_b64(explode('.', $token, 2)[0]);
        $payload = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($payload)) {
            return null;
        }

        $exp = (int) ($payload['exp'] ?? 0);

        return (string) ($payload['d'] ?? '') . ' · ' . ($exp === 0 ? 'no expiry' : date('Y-m-d', $exp));
    }

    private static function block(): void
    {
        $response = request()->expectsJson()
            ? response()->json(['success' => false, 'message' => 'A valid licence file is required.'], 403)
            : redirect('/licence');

        $response->send();
        exit;
    }

    private static function isLocal(Request $request): bool
    {
        return in_array(self::host($request), ['127.0.0.1', '::1', 'localhost'], true);
    }

    private static function isOpenPath(Request $request): bool
    {
        return $request->is('licence')
            || $request->is('licence/*')
            || $request->is('installer')
            || $request->is('installer/*');
    }

    private static function host(Request $request): string
    {
        return licence_guard_host($request->getHost());
    }
}
