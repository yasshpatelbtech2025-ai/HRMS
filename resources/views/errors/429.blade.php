@extends('errors.layout')

@section('code', '429')
@section('title', __("Too many requests"))
@section('message', __("You've done that a lot in a short time. Wait a minute and try again."))

