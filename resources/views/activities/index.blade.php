@extends('layouts.app')
@section('title', 'Activities')

@section('content')
<div class="page-head">
    <div><h1><span class="page-head__icon" style="background:linear-gradient(135deg,#ea580c,#db2777)"><x-icon name="clipboard" :size="22" /></span>Activities</h1>
        <p class="muted">The daily checklist. Every active activity appears each day, in this order.</p></div>
    <a class="btn btn--primary" href="{{ route('activities.create') }}"><x-icon name="plus" :size="16" />Add activity</a>
</div>

<div class="tablewrap card">
<table class="table">
    <thead><tr><th>Order</th><th>Activity</th><th>Status</th><th>Days logged</th><th></th></tr></thead>
    <tbody>
    @forelse($activities as $a)
        <tr>
            <td>{{ $a->sort_order }}</td>
            <td><strong>{{ $a->title }}</strong>@if($a->description)<br><span class="muted small">{{ $a->description }}</span>@endif</td>
            <td>@if($a->is_active)<span class="badge badge--done"><x-icon name="check" :size="12" />Active</span>@else<span class="badge badge--off">Paused</span>@endif</td>
            <td>{{ $a->logs_count }}</td>
            <td class="actions">
                <a class="btn btn--ghost" href="{{ route('activities.edit', $a) }}"><x-icon name="edit" :size="15" />Edit</a>
                <form method="POST" action="{{ route('activities.destroy', $a) }}" data-confirm="Remove “{{ $a->title }}”? Its history is kept.">
                    @csrf @method('DELETE')
                    <button class="btn btn--ghost btn--danger"><x-icon name="trash" :size="15" />Remove</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="empty-cell">No activities yet. Add the first one, for example “Daily SMS count vs SMS count from logs”.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@endsection
