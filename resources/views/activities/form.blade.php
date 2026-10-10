@extends('layouts.app')
@section('title', $activity->exists ? 'Edit activity' : 'Add activity')

@section('content')
<div class="page-head"><h1>{{ $activity->exists ? 'Edit activity' : 'Add activity' }}</h1></div>

<form method="POST" class="card formcard"
      action="{{ $activity->exists ? route('activities.update', $activity) : route('activities.store') }}">
    @csrf
    @if($activity->exists) @method('PUT') @endif

    <label class="field__label" for="title">Title</label>
    <input id="title" name="title" value="{{ old('title', $activity->title) }}" required maxlength="255"
           placeholder="Daily SMS count vs SMS count from logs">

    <label class="field__label" for="description">What should be checked? (optional)</label>
    <textarea id="description" name="description" rows="3" maxlength="2000">{{ old('description', $activity->description) }}</textarea>

    <label class="field__label" for="sort_order">Position on the checklist</label>
    <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $activity->sort_order) }}">

    <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $activity->is_active))> Include on the daily checklist</label>

    <div class="formcard__actions">
        <a class="btn btn--ghost" href="{{ route('activities.index') }}">Cancel</a>
        <button class="btn btn--primary">{{ $activity->exists ? 'Save changes' : 'Add activity' }}</button>
    </div>
</form>
@endsection
