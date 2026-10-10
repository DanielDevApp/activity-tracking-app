@extends('errors.layout')
@section('code', '429')
@section('heading', 'Too many attempts')
@section('message', 'Please wait a minute and try again.')
@section('actions')
<a class="btn btn--primary" href="/login">Back to sign in</a>
@endsection

