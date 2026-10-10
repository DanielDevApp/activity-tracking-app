@extends('layouts.app')
@section('title', 'Team')

@section('content')
<div class="page-head">
    <div><h1><span class="page-head__icon" style="background:linear-gradient(135deg,#db2777,#7c3aed)"><x-icon name="users" :size="22" /></span>Team</h1>
        <p class="muted">These bio details are recorded with every update a person makes.</p></div>
    <a class="btn btn--primary" href="{{ route('users.create') }}"><x-icon name="plus" :size="16" />Add team member</a>
</div>

<div class="tablewrap card">
<table class="table">
    <thead><tr><th>Name</th><th>Employee ID</th><th>Job title</th><th>Phone</th><th>Role</th><th></th></tr></thead>
    <tbody>
    @foreach($users as $u)
        <tr>
            <td><x-avatar :name="$u->name" /> <strong>{{ $u->name }}</strong><br><span class="muted small">{{ $u->email }}</span></td>
            <td>{{ $u->employee_id ?: '—' }}</td>
            <td>{{ $u->job_title ?: '—' }}</td>
            <td>{{ $u->phone ?: '—' }}</td>
            <td><x-role-badge :role="$u->role" />
                @unless($u->is_active)<span class="badge badge--off">Deactivated</span>@endunless
                @if($u->must_change_password)<span class="badge badge--pending"><x-icon name="lock" :size="12" />Temp password</span>@endif</td>
            <td class="actions"><a class="btn btn--ghost" href="{{ route('users.edit', $u) }}"><x-icon name="edit" :size="15" />Edit</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection
