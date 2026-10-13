@extends('errors.layout')

@section('code', '503')
@section('title', __("Back soon"))
@section('message', __(':name is being updated. Please try again in a few minutes.', ['name' => config('branding.name')]))
@section('action')
    <button type="button" class="btn primary" onclick="location.reload()">{{ __('Try again') }}</button>
@endsection
