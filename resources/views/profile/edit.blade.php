@extends('layouts.app')
@section('title', 'My profile')

@section('content')
<div class="page-head"><div><h1>My profile</h1>
    <p class="muted">Employee ID {{ $user->employee_id ?: 'not set' }}, {{ $user->email }}. Ask an admin to change these two.</p></div></div>

<form method="POST" action="{{ route('profile.update') }}" class="card formcard">
    @csrf @method('PUT')
    <div class="formgrid">
        <div><label class="field__label" for="name">Full name</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required></div>
        <div><label class="field__label" for="job_title">Job title</label><input id="job_title" name="job_title" value="{{ old('job_title', $user->job_title) }}" required></div>
        <div><label class="field__label" for="phone">Phone</label><input id="phone" name="phone" value="{{ old('phone', $user->phone) }}"></div>
    </div>

    <h2 class="formcard__sub"><x-icon name="lock" />{{ $user->must_change_password ? 'Choose your new password' : 'Change password' }}</h2>
    <div class="formgrid">
        <div><label class="field__label" for="current_password">{{ $user->must_change_password ? 'Temporary password you were given' : 'Current password' }}</label><input id="current_password" type="password" name="current_password" autocomplete="current-password"></div>
        <div><label class="field__label" for="password">New password</label><input id="password" type="password" name="password" minlength="8" autocomplete="new-password" @required($user->must_change_password)></div>
        <div><label class="field__label" for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"></div>
    </div>

    <div class="formcard__actions"><button class="btn btn--primary">Save profile</button></div>
</form>
@endsection
