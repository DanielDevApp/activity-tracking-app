@extends('errors.layout')
@section('code', '404')
@section('heading', 'Page not found')
@section('message', 'That page does not exist or was moved. Check the address, or go back to your checklist.')
@section('actions')
<a class="btn btn--primary" href="/dashboard">Go to Today</a>
@endsection

