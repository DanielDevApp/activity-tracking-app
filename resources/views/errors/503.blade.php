@extends('errors.layout')
@section('code', '503')
@section('heading', 'Starting up')
@section('message', 'The server is waking up or being updated. This page refreshes itself, so you can just wait.')
@section('actions')
<a class="btn btn--primary" href="">Refresh now</a>
@endsection
@section('head')<meta http-equiv="refresh" content="8">@endsection
