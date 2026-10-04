@extends('errors.layout')

@section('code', '419')
@section('title', __("This page expired"))
@section('message', __("For your security, forms expire after a while. Go back, refresh the page and try again."))
@section('action')
    <button type="button" class="btn primary" onclick="history.back()">{{ __('Go back') }}</button>
@endsection
