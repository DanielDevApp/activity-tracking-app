@extends('layouts.app')
@section('title', 'Sign in')
@section('body-class', 'auth-page')

@section('content')
<section class="login">
    <div class="login__intro">
        <div class="login__brand"><span class="logo" aria-hidden="true"><i></i><i></i><i></i></span>{{ config('app.name') }}</div>
        <h1>Nothing gets lost between shifts.</h1>
        <p>Every daily check, who did it, when, and what is still open for the next person.</p>
        <ul class="login__feats">
            <li><x-icon name="check-circle" />Mark checks done or pending with a remark</li>
            <li><x-icon name="handover" />See exactly what to pick up at handover</li>
            <li><x-icon name="chart" />Search the full history by any date range</li>
        </ul>
        <div class="strip strip--demo" aria-hidden="true">
            <span class="strip__seg strip__seg--done"></span><span class="strip__seg strip__seg--done"></span>
            <span class="strip__seg strip__seg--done"></span><span class="strip__seg strip__seg--pending"></span>
            <span class="strip__seg strip__seg--done"></span><span class="strip__seg strip__seg--pending"></span>
        </div>
    </div>

    <form method="POST" action="{{ route('login.attempt') }}" class="login__form card" data-wake="login">
        @csrf
        <h2>Sign in</h2>
        <label class="field__label" for="email">Work email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">

        <label class="field__label" for="password">Password</label>
        <div class="field field--password">
            <input id="password" type="password" name="password" required autocomplete="current-password" aria-label="Password">
            <button type="button" class="field__toggle" data-password-toggle aria-label="Show password" aria-pressed="false">
                <svg class="icon" aria-hidden="true"><use href="#i-eye"></use></svg>
                <svg class="icon icon--off" aria-hidden="true"><use href="#i-eye-off"></use></svg>
            </button>
        </div>

        <label class="check"><input type="checkbox" name="remember"> Keep me signed in on this device</label>

        <button class="btn btn--primary btn--block"><x-icon name="lock" />Sign in</button>
        <p class="serverstate" data-server-state><i></i><span>Checking server…</span></p>
        <p class="hint">Accounts are created by your team lead. On a quiet day the server may take up to a minute to wake up; the page will tell you.</p>
    </form>
</section>
@endsection
