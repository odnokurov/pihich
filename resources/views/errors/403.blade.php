@extends('layout')
@section('title', 'Доступ закрыт')
@section('style', 'errors')
@section('content')
<section class="workspace"><h1>Доступ закрыт</h1><a class="action" href="{{ route('home') }}">На главную</a></section>
@endsection
