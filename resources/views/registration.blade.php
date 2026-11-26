@extends('layout')
@section('title', 'Регистрация')
@section('style', 'registration')
@section('content')
    @include('partials.auth-form', ['register' => true])
@endsection
