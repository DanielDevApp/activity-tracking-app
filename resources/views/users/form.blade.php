@extends('layouts.app')
@section('title', $user->exists ? 'Edit team member' : 'Add team member')

@section('content')
<div class="page-head"><h1>{{ $user->exists ? 'Edit team member' : 'Add team member' }}</h1></div>

<form method="POST" class="card formcard" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
    @csrf
    @if($user->exists) @method('PUT') @endif

    <div class="formgrid">
        <div><label class="field__label" for="name">Full name</label>
            <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="120"></div>
        <div><label class="field__label" for="employee_id">Employee ID</label>
            <input id="employee_id" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}" required maxlength="30"></div>
        <div><label class="field__label" for="job_title">Job title</label>
            <input id="job_title" name="job_title" value="{{ old('job_title', $user->job_title) }}" required maxlength="120"></div>
        <div><label class="field__label" for="phone">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30"></div>
        <div><label class="field__label" for="email">Work email (used to sign in)</label>
            <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
        <div><label class="field__label" for="password">{{ $user->exists ? 'Reset password (leave blank to keep)' : 'Temporary password' }}</label>
            <input id="password" type="password" name="password" minlength="8" autocomplete="new-password" @required(! $user->exists)>
            <p class="hint">They must choose their own password at first sign-in.</p></div>
        <div><label class="field__label" for="role">Role</label>
            <select id="role" name="role">
                <option value="member" @selected(old('role', $user->role) === 'member')>Member: updates activities</option>
                <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin: also manages activities and team</option>
            </select></div>
    </div>

    <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))> Account is active (can sign in)</label>

    <div class="formcard__actions">
        <a class="btn btn--ghost" href="{{ route('users.index') }}">Cancel</a>
        <button class="btn btn--primary">{{ $user->exists ? 'Save changes' : 'Add team member' }}</button>
    </div>
</form>
@endsection
