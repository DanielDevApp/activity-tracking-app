@extends('layouts.app')
@section('title', 'Today')

@section('content')
@php
    $pct = $total ? (int) round($done / $total * 100) : 0;
    $pending = $total - $done;
    $hour = now()->hour;
    $greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $first = Str::before(auth()->user()->name, ' ');
@endphp

<section class="hero">
    <div>
        <h1>{{ $date->isToday() ? $greet . ', ' . $first : $date->format('l') . ' (past day)' }}</h1>
        <p class="hero__date">{{ $date->format('l, j F Y') }}</p>
        <div class="hero__chips">
            <span class="hero__chip"><x-icon name="check-circle" :size="16" />{{ $done }} done</span>
            <span class="hero__chip"><x-icon name="clock" :size="16" />{{ $pending }} pending</span>
            @if($carriedOver->isNotEmpty())<span class="hero__chip"><x-icon name="alert" :size="16" />{{ $carriedOver->count() }} handed over</span>@endif
        </div>
    </div>
    <div class="ring" style="--p: {{ $pct }}" role="img" aria-label="{{ $pct }} percent complete"><span><strong>{{ $pct }}%</strong>complete</span></div>
    <div class="strip" role="img" aria-label="{{ $done }} of {{ $total }} activities done">
        @foreach($logs as $log)
            <span class="strip__seg strip__seg--{{ $log->status }}" title="{{ $log->activity->title }}: {{ $log->status }}"></span>
        @endforeach
    </div>
</section>

<div class="page-head">
    <div class="datenav">
        <a class="btn btn--ghost" href="{{ route('dashboard', ['date' => $date->copy()->subDay()->toDateString()]) }}"><x-icon name="left" :size="16" />Previous day</a>
        <form method="GET" action="{{ route('dashboard') }}">
            <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" data-autosubmit aria-label="Choose a date">
        </form>
        @unless($date->isToday())
            <a class="btn btn--ghost" href="{{ route('dashboard', ['date' => $date->copy()->addDay()->toDateString()]) }}">Next day<x-icon name="right" :size="16" /></a>
            <a class="btn btn--primary" href="{{ route('dashboard') }}">Back to today</a>
        @endunless
    </div>
</div>

@if($carriedOver->isNotEmpty())
<section class="handover" aria-labelledby="handover-h">
    <h2 id="handover-h"><x-icon name="alert" />{{ $carriedOver->count() }} {{ Str::plural('item', $carriedOver->count()) }} handed over from earlier days</h2>
    <p class="muted small">Pending when the last person finished. Pick these up first.</p>
    <ul class="handover__list">
        @foreach($carriedOver as $c)
        @php $age = (int) abs($c->log_date->diffInDays(today())); @endphp
        <li>
            <div>
                <strong>{{ $c->activity->title }}</strong>
                <span class="badge badge--late"><x-icon name="calendar" :size="12" />{{ $c->log_date->format('D j M') }}, {{ $age }} {{ Str::plural('day', $age) }} open</span>
                <p class="remark"><x-icon name="message" :size="14" /><span>{{ $c->remark ?: 'No remark was left.' }}</span></p>
                @if($c->lastUpdater)
                    <p class="byline"><x-avatar :name="$c->lastUpdater->name" />
                        {{ $c->lastUpdater->name }} ({{ $c->lastUpdater->employee_id }}) at {{ $c->last_updated_at->format('j M, H:i') }}</p>
                @endif
            </div>
            <button type="button" class="btn btn--success" data-update
                    data-action="{{ route('logs.update', $c) }}"
                    data-title="{{ $c->activity->title }} ({{ $c->log_date->format('j M') }})"
                    data-status="done" data-remark=""><x-icon name="check" />Resolve</button>
        </li>
        @endforeach
    </ul>
</section>
@endif

@if($logs->isNotEmpty())
<div class="filterbar" role="group" aria-label="Filter activities">
    <button type="button" class="fchip is-active" data-filter="all">All <b>{{ $total }}</b></button>
    <button type="button" class="fchip" data-filter="pending"><x-icon name="clock" :size="14" />Pending <b>{{ $pending }}</b></button>
    <button type="button" class="fchip" data-filter="done"><x-icon name="check" :size="14" />Done <b>{{ $done }}</b></button>
</div>
@endif

<section aria-label="Activities for {{ $date->format('j F') }}">
    @forelse($logs as $log)
    <article class="item item--{{ $log->status }}" data-item-status="{{ $log->status }}">
        <div class="item__main">
            <div class="item__title">
                <h3>{{ $log->activity->title }}</h3>
                <x-status-badge :status="$log->status" />
            </div>
            @if($log->activity->description)<p class="muted small">{{ $log->activity->description }}</p>@endif
            @if($log->remark)<p class="remark"><x-icon name="message" :size="14" /><span>{{ $log->remark }}</span></p>@endif
            @if($log->lastUpdater)
                <p class="byline"><x-avatar :name="$log->lastUpdater->name" />
                    {{ $log->lastUpdater->name }} ({{ $log->lastUpdater->employee_id }}) at <time>{{ $log->last_updated_at->format('H:i') }}</time></p>
            @else
                <p class="byline byline--none">Nobody has touched this yet.</p>
            @endif
        </div>
        <button type="button" class="btn {{ $log->isDone() ? 'btn--ghost' : 'btn--primary' }}" data-update
                data-action="{{ route('logs.update', $log) }}"
                data-title="{{ $log->activity->title }}"
                data-status="done"
                data-remark="{{ $log->remark }}"><x-icon :name="$log->isDone() ? 'edit' : 'check'" :size="16" />{{ $log->isDone() ? 'Edit' : 'Update' }}</button>
    </article>
    @empty
    <div class="empty card">
        <x-icon name="inbox" :size="36" />
        <h3>No checklist for this day</h3>
        <p class="muted">@if(auth()->user()->isAdmin())<a href="{{ route('activities.create') }}">Add the first activity</a> and it will appear on the daily checklist.@else Nothing was logged on this date, or your team lead has not added activities yet.@endif</p>
    </div>
    @endforelse
</section>

@include('partials.update-dialog')
@endsection
