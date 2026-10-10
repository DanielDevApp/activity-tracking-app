@extends('errors.layout')
@section('code', '403')
@section('heading', 'You do not have access')
@section('message', 'This area is for administrators. If you think you should have access, ask your team lead.')
@section('actions')
<a class="btn btn--primary" href="/dashboard">Back to Today</a>
@endsection

