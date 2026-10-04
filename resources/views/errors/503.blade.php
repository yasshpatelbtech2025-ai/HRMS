@extends('errors.layout')

@section('code', '503')
@section('title', __("Back soon"))
@section('message', __("SRYMPEX is being updated. Please try again in a few minutes."))
@section('action')
    <button type="button" class="btn primary" onclick="location.reload()">{{ __('Try again') }}</button>
@endsection
