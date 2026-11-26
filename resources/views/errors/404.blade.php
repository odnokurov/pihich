@extends('layout')
@section('title', 'Страница не найдена')
@section('style', 'errors')
@section('content')
<section class="workspace"><h1>Страница не найдена</h1><a class="action" href="{{ route('home') }}">На главную</a></section>
@endsection
