@extends('layout')
@section('title', 'Вход')
@section('style', 'login')
@section('content')
    @include('partials.auth-form', ['register' => false])
@endsection
