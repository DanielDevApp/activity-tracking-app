@extends('layouts.app')
@section('title', 'Handover')

@section('content')
<div class="page-head">
    <div>
        <h1><span class="page-head__icon"><x-icon name="handover" :size="22" /></span>Handover</h1>
        <p class="muted">{{ $date->format('l, j F Y') }}: every update made that day, who made it and when.</p>
    </div>
    <div class="datenav">
        <a class="btn btn--ghost" href="{{ route('timeline', ['date' => $date->copy()->subDay()->toDateString(), 'view' => $view]) }}"><x-icon name="left" :size="16" />Previous day</a>
        <form method="GET" action="{{ route('timeline') }}">
            <input type="hidden" name="view" value="{{ $view }}">
            <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" data-autosubmit aria-label="Choose a date">
        </form>
        @unless($date->isToday())
            <a class="btn btn--ghost" href="{{ route('timeline', ['date' => $date->copy()->addDay()->toDateString(), 'view' => $view]) }}">Next day<x-icon name="right" :size="16" /></a>
        @endunless
    </div>
</div>

<div class="stats">
    <div class="stat stat--violet"><span class="stat__icon"><x-icon name="pulse" /></span><div><strong>{{ $updates->count() }}</strong><span class="stat__label">updates made</span></div></div>
    <div class="stat stat--teal"><span class="stat__icon"><x-icon name="users" /></span><div><strong>{{ $people }}</strong><span class="stat__label">{{ Str::plural('person', $people) }} involved</span></div></div>
    <div class="stat stat--done"><span class="stat__icon"><x-icon name="check-circle" /></span><div><strong>{{ $doneCount }}</strong><span class="stat__label">marked done</span></div></div>
    <div class="stat stat--pending"><span class="stat__icon"><x-icon name="clock" /></span><div><strong>{{ $outstanding->count() }}</strong><span class="stat__label">still pending</span></div></div>
</div>

<section class="handover {{ $outstanding->isEmpty() ? 'handover--clear' : '' }}" aria-labelledby="out-h">
    <h2 id="out-h"><x-icon :name="$outstanding->isEmpty() ? 'check-circle' : 'alert'" />What the next person inherits</h2>
    @if($outstanding->isEmpty())
        <p class="small" style="margin-top:.4rem">Nothing is pending. Clean handover.</p>
    @else
    <ul class="handover__list">
        @foreach($outstanding as $o)
        <li>
            <div>
                <strong>{{ $o->activity->title }}</strong>
                @if(! $o->log_date->isSameDay($date))<span class="badge badge--late"><x-icon name="calendar" :size="12" />from {{ $o->log_date->format('D j M') }}</span>@endif
                <p class="remark"><x-icon name="message" :size="14" /><span>{{ $o->remark ?: 'No remark was left.' }}</span></p>
                @if($o->lastUpdater)<p class="byline"><x-avatar :name="$o->lastUpdater->name" />Last touched by {{ $o->lastUpdater->name }} ({{ $o->lastUpdater->employee_id }}) at {{ $o->last_updated_at->format('j M, H:i') }}</p>@endif
            </div>
            <button type="button" class="btn btn--success" data-update
                    data-action="{{ route('logs.update', $o) }}"
                    data-title="{{ $o->activity->title }} ({{ $o->log_date->format('j M') }})"
                    data-status="done" data-remark=""><x-icon name="check" />Resolve</button>
        </li>
        @endforeach
    </ul>
    @endif
</section>

<div class="tabs" role="tablist">
    <a role="tab" aria-selected="{{ $view === 'activity' ? 'true' : 'false' }}" @class(['is-active' => $view === 'activity']) href="{{ route('timeline', ['date' => $date->toDateString(), 'view' => 'activity']) }}">By activity</a>
    <a role="tab" aria-selected="{{ $view === 'time' ? 'true' : 'false' }}" @class(['is-active' => $view === 'time']) href="{{ route('timeline', ['date' => $date->toDateString(), 'view' => 'time']) }}">By time</a>
</div>

@if($view === 'time')
    @if($updates->isEmpty())
        <div class="empty card"><x-icon name="inbox" :size="36" /><h3>No updates on this day</h3><p class="muted">Updates made on the Today page appear here with the time and person.</p></div>
    @else
    <ol class="tl">
        @foreach($updates as $u)
        <li class="tl__row">
            <time class="tl__time" datetime="{{ $u->created_at->toIso8601String() }}">{{ $u->created_at->format('H:i') }}<small>:{{ $u->created_at->format('s') }}</small></time>
            <span class="tl__dot tl__dot--{{ $u->status }}"></span>
            <div class="tl__card">
                <div class="item__title">
                    <h3>{{ $u->log->activity->title }}</h3>
                    <x-status-badge :status="$u->status" />
                    @if(! $u->log->log_date->isSameDay($date))<span class="badge badge--late"><x-icon name="calendar" :size="12" />item from {{ $u->log->log_date->format('D j M') }}</span>@endif
                </div>
                @if($u->remark)<p class="remark"><x-icon name="message" :size="14" /><span>{{ $u->remark }}</span></p>@endif
                <p class="byline"><x-avatar :name="$u->who('name')" />
                    {{ $u->who('name') }} ({{ $u->who('employee_id', 'no ID') }}), {{ $u->who('job_title') }}@if($u->who('phone')), {{ $u->who('phone') }}@endif</p>
            </div>
        </li>
        @endforeach
    </ol>
    @endif
@else
    @forelse($logs as $log)
        @php $day = $byLog->get($log->id, collect()); @endphp
        <article class="item item--{{ $log->status }}">
            <div class="item__main">
                <div class="item__title">
                    <h3>{{ $log->activity->title }}</h3>
                    <x-status-badge :status="$log->status" />
                    @if(! $log->log_date->isSameDay($date))<span class="badge badge--late"><x-icon name="calendar" :size="12" />item from {{ $log->log_date->format('D j M') }}</span>@endif
                </div>
                @if($day->isEmpty())
                    <p class="byline byline--none">No update on this day.</p>
                @else
                    <ol class="trail">
                        @foreach($day as $u)
                        <li>
                            <time>{{ $u->created_at->format('H:i') }}</time>
                            <div>
                                <x-status-badge :status="$u->status" />
                                <span class="byline" style="display:inline-flex"><x-avatar :name="$u->who('name')" />{{ $u->who('name') }} ({{ $u->who('employee_id', 'no ID') }})</span>
                                @if($u->remark)<p class="remark"><x-icon name="message" :size="14" /><span>{{ $u->remark }}</span></p>@endif
                            </div>
                        </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </article>
    @empty
        <div class="empty card"><x-icon name="inbox" :size="36" /><h3>Nothing recorded for this day</h3><p class="muted">No checklist was generated and no updates were made.</p></div>
    @endforelse
@endif

@include('partials.update-dialog')
@endsection
