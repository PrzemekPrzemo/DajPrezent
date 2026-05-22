<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — DajPrezent.pl</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #1f2937; background: #fdf2f8; }
        .container { max-width: 720px; margin: 0 auto; padding: 4rem 1.5rem; }
        .card { background: #fff; border-radius: 1rem; padding: 2.5rem; box-shadow: 0 10px 30px rgba(0,0,0,.06); text-align: center; }
        h1 { margin-top: 0; font-size: 1.75rem; }
        p { color: #4b5563; line-height: 1.6; }
        a.button { display:inline-block; margin-top:1rem; padding:.75rem 1.5rem; background:#ec4899; color:#fff; border-radius:.5rem; text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
    <main class="container">
        @yield('content')
    </main>
</body>
</html>
