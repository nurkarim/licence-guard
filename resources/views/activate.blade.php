<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Licence</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;font-family:"Segoe UI",sans-serif;background:linear-gradient(160deg,#172033,#24344d 55%,#1f6f6a);color:#132033}
        .card{width:100%;max-width:460px;background:#fff;border-radius:18px;padding:28px;box-shadow:0 24px 50px rgba(0,0,0,.25)}
        h1{margin:0 0 6px;font-size:22px}
        p{margin:0;color:#5c6b7a;font-size:14px;line-height:1.5}
        label{display:block;margin:16px 0 6px;font-size:13px;font-weight:700}
        input[type=file],textarea{width:100%;border:1px solid #d5deE6;border-radius:10px;padding:10px 12px;font:13px/1.4 ui-monospace,Consolas,monospace}
        textarea{min-height:90px;resize:vertical}
        button{width:100%;margin-top:18px;border:0;border-radius:10px;padding:12px;background:#1f6f6a;color:#fff;font-weight:700;cursor:pointer}
        .note{margin-top:14px;background:#f4f7f8;border-radius:10px;padding:10px 12px;font-size:13px;color:#405060}
        .error{margin-bottom:12px;background:#fdecec;color:#8d1d1d;border-radius:10px;padding:10px 12px;font-size:14px}
        .ok{margin-bottom:12px;background:#e8f6ef;color:#0f6b3c;border-radius:10px;padding:10px 12px;font-size:14px}
        code{background:#e7eef2;padding:1px 6px;border-radius:6px}
    </style>
</head>
<body>
    <form class="card" method="POST" action="{{ route('licence-guard.store') }}" enctype="multipart/form-data">
        @csrf
        <h1>Activate this software</h1>
        <p>Upload the licence file you received for this website.</p>

        @if(session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        @if(!$productReady)
            <div class="error">The licence guard is not connected yet. The public/index.php line must run before this page can accept a file.</div>
        @endif

        @if($isLicensed)
            <div class="ok">This website is already licensed. {{ $activeKey }}</div>
        @endif

        <label for="licence_file">Licence file</label>
        <input id="licence_file" type="file" name="licence_file" accept=".lic,.txt,.key">

        <label for="licence_key">Or paste the licence</label>
        <textarea id="licence_key" name="licence_key" placeholder="Paste the licence text">{{ old('licence_key') }}</textarea>

        <div class="note">This website is <code>{{ $domain }}</code>. The file must be made for this address.</div>
        <button type="submit">Activate</button>
    </form>
</body>
</html>
