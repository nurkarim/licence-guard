<?php

namespace Epixel\LicenceGuard\Http\Controllers;

use Epixel\LicenceGuard\LicenceChecker;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LicenceController extends Controller
{
    public function show(Request $request)
    {
        if (in_array(licence_guard_host($request->getHost()), ['127.0.0.1', '::1', 'localhost'], true)) {
            return redirect('/');
        }

        $product = LicenceChecker::product();

        return view('licence-guard::activate', [
            'productReady' => $product !== '',
            'domain' => licence_guard_host($request->getHost()),
            'isLicensed' => $product !== '' && LicenceChecker::licensed($product),
            'activeKey' => $product !== '' ? LicenceChecker::summary($product) : null,
        ]);
    }

    public function store(Request $request)
    {
        if (in_array(licence_guard_host($request->getHost()), ['127.0.0.1', '::1', 'localhost'], true)) {
            return redirect('/');
        }

        $token = trim((string) $request->input('licence_key', ''));
        if ($request->hasFile('licence_file')) {
            $request->validate(['licence_file' => ['file', 'max:32']]);
            $uploaded = file_get_contents($request->file('licence_file')->getRealPath());
            $token = is_string($uploaded) ? trim($uploaded) : '';
        }

        if ($token === '') {
            return back()->with('error', 'Paste the licence or upload the .lic file.');
        }

        $result = LicenceChecker::activate(LicenceChecker::product(), $token);
        if (!$result['ok']) {
            return back()->with('error', $result['message']);
        }

        return redirect('/')->with('success', 'Licence activated.');
    }
}
