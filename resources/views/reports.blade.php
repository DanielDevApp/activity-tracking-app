@extends('layouts.app')
@section('title', 'Reports')

@section('content')
@php
    $q = fn (array $extra) => route('reports.index', array_filter($extra + request()->except(['from', 'to', 'page'])));
@endphp
<div class="page-head">
    <div>
        <h1><span class="page-head__icon"><x-icon name="chart" :size="22" /></span>Reports</h1>
        <p class="muted">Search the update history by date range, activity, person and status.</p>
    </div>
    <a class="btn btn--primary" href="{{ route('reports.export', request()->query()) }}"><x-icon name="download" :size="16" />Download CSV</a>
</div>

<form method="GET" action="{{ route('reports.index') }}" class="filters card">
    <div class="chips">
        <a class="chip" href="{{ $q(['from' => today()->toDateString(), 'to' => today()->toDateString()]) }}">Today</a>
        <a class="chip" href="{{ $q(['from' => today()->subDays(6)->toDateString(), 'to' => today()->toDateString()]) }}">Last 7 days</a>
        <a class="chip" href="{{ $q(['from' => today()->subDays(29)->toDateString(), 'to' => today()->toDateString()]) }}">Last 30 days</a>
        <a class="chip" href="{{ $q(['from' => today()->startOfMonth()->toDateString(), 'to' => today()->toDateString()]) }}">This month</a>
    </div>
    <div class="filters__grid">
        <div><label class="field__label" for="from">From</label><input id="from" type="date" name="from" value="{{ $f['from'] }}"></div>
        <div><label class="field__label" for="to">To</label><input id="to" type="date" name="to" value="{{ $f['to'] }}"></div>
        <div><label class="field__label" for="activity_id">Activity</label>
            <select id="activity_id" name="activity_id"><option value="">All activities</option>
                @foreach($activities as $a)<option value="{{ $a->id }}" @selected($f['activity_id'] == $a->id)>{{ $a->title }}@if($a->trashed()) (removed)@endif</option>@endforeach
            </select></div>
        <div><label class="field__label" for="user_id">Person</label>
            <select id="user_id" name="user_id"><option value="">Everyone</option>
                @foreach($users as $u)<option value="{{ $u->id }}" @selected($f['user_id'] == $u->id)>{{ $u->name }}</option>@endforeach
            </select></div>
        <div><label class="field__label" for="status">Status</label>
            <select id="status" name="status"><option value="">Any</option>
                <option value="done" @selected($f['status'] === 'done')>Done</option>
                <option value="pending" @selected($f['status'] === 'pending')>Pending</option>
            </select></div>
        <div><button class="btn btn--primary btn--block"><x-icon name="filter" :size="16" />Apply filters</button></div>
    </div>
</form>

<div class="stats">
    <div class="stat"><span class="stat__icon"><x-icon name="pulse" /></span><div><strong>{{ $stats['total'] }}</strong><span class="stat__label">updates</span></div></div>
    <div class="stat stat--done"><span class="stat__icon"><x-icon name="check-circle" /></span><div><strong>{{ $stats['done'] }}</strong><span class="stat__label">set to done</span></div></div>
    <div class="stat stat--pending"><span class="stat__icon"><x-icon name="clock" /></span><div><strong>{{ $stats['pending'] }}</strong><span class="stat__label">set to pending</span></div></div>
    <div class="stat stat--pink"><span class="stat__icon"><x-icon name="users" /></span><div><strong>{{ $stats['people'] }}</strong><span class="stat__label">{{ Str::plural('person', $stats['people']) }}</span></div></div>
</div>

<section class="card chart">
    <h2><x-icon name="chart" />Daily completion</h2>
    <p class="muted small">Share of each day's checklist that is done now. Green is done, amber is still pending.</p>
    @if($days->isEmpty())
        <p class="muted" style="margin-top:1rem">No checklists exist in this range.</p>
    @else
    <div class="bars">
        @foreach($days as $d)
            @php $total = (int) $d->total; $dn = (int) $d->done_count; $pct = $total ? round($dn / $total * 100) : 0; @endphp
            <div class="bar" title="{{ $d->log_date->format('D j M') }}: {{ $dn }} of {{ $total }} done">
                <div class="bar__track"><div class="bar__fill" style="height: {{ $pct }}%"></div></div>
                <span class="bar__pct">{{ $pct }}%</span>
                <span class="bar__label">{{ $d->log_date->format('j M') }}</span>
            </div>
        @endforeach
    </div>
    @endif
</section>

<div class="tablewrap card">
    <table class="table">
        <thead><tr><th>Updated</th><th>Activity (day)</th><th>Status</th><th>Remark</th><th>By</th></tr></thead>
        <tbody>
        @forelse($updates as $u)
            <tr>
                <td class="nowrap"><strong>{{ $u->created_at->format('H:i') }}</strong><br><span class="muted small">{{ $u->created_at->format('D j M Y') }}</span></td>
                <td>{{ $u->log->activity->title }}<br><span class="muted small">{{ $u->log->log_date->format('j M Y') }}</span></td>
                <td><x-status-badge :status="$u->status" /></td>
                <td class="remarkcell">{{ $u->remark ?: '—' }}</td>
                <td class="nowrap"><x-avatar :name="$u->who('name')" /> {{ $u->who('name') }}<br><span class="muted small">{{ $u->who('employee_id', 'no ID') }}, {{ $u->who('job_title') }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5" class="empty-cell">No updates match these filters. Try a wider date range.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $updates->links('pagination.tracker') }}
@endsection
