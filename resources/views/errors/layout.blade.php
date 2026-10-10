<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('heading') · {{ config('app.name') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <script>document.documentElement.dataset.theme = (localStorage.getItem('theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));</script>
    <link rel="stylesheet" href="/css/app.css">
    @yield('head')
</head>
<body>
<main class="errorpage">
    <div class="errorpage__card">
        <span class="logo" aria-hidden="true"><i></i><i></i><i></i></span>
        <div class="errorpage__code">@yield('code')</div>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        @yield('actions')
    </div>
</main>
</body>
</html>
