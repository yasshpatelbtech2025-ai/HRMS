@extends('errors.layout')

@section('code', '500')
@section('title', __('Something went wrong'))
@section('message', __('An unexpected error stopped this page. It has been logged; please try again in a moment.'))
@section('reference', __('If it keeps happening, tell an admin the time: :time', ['time' => now()->format('d M Y H:i:s')]))
