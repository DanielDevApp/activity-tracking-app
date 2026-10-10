@extends('errors.layout')
@section('code', '419')
@section('heading', 'Your session expired')
@section('message', 'For your security the page timed out. Open the sign-in page again and retry.')
@section('actions')
<a class="btn btn--primary" href="/login">Back to sign in</a>
@endsection

