@extends('layouts.app')
@section('title', $article->title)
@section('content')
<div class="breadcrumb"><a href="{{ route('home') }}">首页</a> / <a href="{{ route('news.index') }}">资讯</a> / 详情</div>
<article class="detail">
    <h1>{{ $article->title }}</h1>
    <p class="muted">作者：{{ $article->author ?: '编辑部' }} · 发布：{{ $article->published_at?->format('Y-m-d H:i') }} · 浏览 {{ $article->views }}</p>
    <p>{{ $article->summary }}</p>
    <div>{!! nl2br(e($article->content)) !!}</div>
</article>
@endsection
