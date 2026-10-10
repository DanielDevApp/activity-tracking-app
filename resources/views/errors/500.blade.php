@extends('errors.layout')
@section('code', '500')
@section('heading', 'Something went wrong')
@section('message', 'That was on our side, not yours. Nothing you entered was lost. Please try again in a moment.')
@section('actions')
<a class="btn btn--primary" href="/dashboard">Try again</a>
@endsection

