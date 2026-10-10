<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#312e81">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <script>
        (function () {
            var t = localStorage.getItem('theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.dataset.theme = t;
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</head>
<body class="@yield('body-class')" data-wake-url="{{ route('wake', [], false) }}">
@include('partials.icons')

@auth
<header class="topbar">
    <div class="topbar__inner">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="logo" aria-hidden="true"><i></i><i></i><i></i></span>
            <span class="brand__name">{{ config('app.name') }}</span>
        </a>
        <button type="button" class="nav-toggle" data-nav-toggle aria-label="Open menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <div class="usermenu">
            <button type="button" class="iconbtn" data-theme-toggle title="Switch light/dark theme" aria-label="Switch light or dark theme">
                <x-icon name="moon" class="theme-moon" /><x-icon name="sun" class="theme-sun" />
            </button>
            <a href="{{ route('profile.edit') }}" class="usermenu__who" title="Edit your profile">
                <x-avatar :name="auth()->user()->name" />
                <span class="usermenu__text">{{ auth()->user()->name }}<small>{{ auth()->user()->employee_id }}</small></span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="iconbtn" title="Sign out" aria-label="Sign out"><x-icon name="logout" /></button>
            </form>
        </div>
    </div>
</header>
<div class="app-shell">
    <aside class="sidebar" aria-label="Sidebar navigation">
        <nav class="nav" aria-label="Main">
            <a href="{{ route('dashboard') }}" @class(['nav--blue', 'is-active' => request()->routeIs('dashboard')]) data-nav-item @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                <x-icon name="check-circle" />Today
            </a>
            <a href="{{ route('timeline') }}" @class(['nav--violet', 'is-active' => request()->routeIs('timeline')]) data-nav-item @if(request()->routeIs('timeline')) aria-current="page" @endif>
                <x-icon name="handover" />Handover
            </a>
            <a href="{{ route('reports.index') }}" @class(['nav--teal', 'is-active' => request()->routeIs('reports.*')]) data-nav-item @if(request()->routeIs('reports.*')) aria-current="page" @endif>
                <x-icon name="chart" />Reports
            </a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('activities.index') }}" @class(['nav--orange', 'is-active' => request()->routeIs('activities.*')]) data-nav-item @if(request()->routeIs('activities.*')) aria-current="page" @endif>
                    <x-icon name="clipboard" />Activities
                </a>
                <a href="{{ route('users.index') }}" @class(['nav--pink', 'is-active' => request()->routeIs('users.*')]) data-nav-item @if(request()->routeIs('users.*')) aria-current="page" @endif>
                    <x-icon name="users" />Team
                </a>
            @endif
        </nav>
    </aside>
    <main class="container">
@else
<main class="container">
@endauth
    @if(session('status'))
        <div class="alert alert--ok" role="status"><x-icon name="check-circle" />{{ session('status') }}</div>
    @endif
    @if(session('notice'))
        <div class="alert alert--info" role="status"><x-icon name="lock" />{{ session('notice') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert--error" role="alert">
            <x-icon name="alert" />
            <div><strong>Please fix the following:</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    @yield('content')
</main>
@auth
</div>
@endauth

{{-- Shown while a sleeping free-tier server wakes up --}}
<div id="wake" class="wake" hidden role="alertdialog" aria-modal="true" aria-live="polite" aria-labelledby="wake-title">
    <div class="wake__card">
        <div class="wake__logo" aria-hidden="true"><i></i><i></i><i></i></div>
        <h2 id="wake-title">Connecting to server…</h2>
        <p id="wake-msg">Contacting the server…</p>
        <p class="wake__time" id="wake-time"></p>
        <button type="button" class="btn btn--ghost" id="wake-cancel">Cancel</button>
    </div>
</div>
</body>
</html>
