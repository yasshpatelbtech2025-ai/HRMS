@extends('errors.layout')

@section('code', '401')
@section('title', __("Please sign in"))
@section('message', __("Your session has ended. Sign in again to continue."))
@section('action')
    <a href="{{ url('/login') }}" class="btn primary">{{ __('Sign in') }}</a>
@endsection
