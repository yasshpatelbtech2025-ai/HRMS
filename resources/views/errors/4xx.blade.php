@extends('errors.layout')

@section('code', $exception->getStatusCode())
@section('title', __("This request couldn't be completed"))
@section('message', __("Go back and try again. If it keeps happening, tell an admin."))

