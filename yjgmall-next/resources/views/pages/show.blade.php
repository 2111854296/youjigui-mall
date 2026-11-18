@extends('layouts.app')
@section('title', $page->title)
@section('content')
<div class="breadcrumb"><a href="{{ route('home') }}">首页</a> / {{ $page->title }}</div>
<article class="detail">
    <h1>{{ $page->title }}</h1>
    <div>{!! nl2br(e($page->content)) !!}</div>
</article>
@endsection
