@extends('layouts.app')
@section('title', $supply->title)
@section('content')
<div class="breadcrumb"><a href="{{ route('home') }}">首页</a> / <a href="{{ route('sell.index') }}">供应</a> / 详情</div>
<article class="detail">
    <h1>{{ $supply->title }}</h1>
    <p class="muted">分类：{{ $supply->category?->name ?: '-' }} · 浏览 {{ $supply->views }} · {{ $supply->refreshed_at?->format('Y-m-d H:i') }}</p>
    <p class="price" style="font-size:22px;color:var(--brand-dark)">{{ $supply->price ? '¥'.number_format($supply->price,2).($supply->unit ? '/'.$supply->unit : '') : '价格面议' }}</p>
    <p>{{ $supply->summary }}</p>
    <div>{!! nl2br(e($supply->content)) !!}</div>
    <hr>
    <p>发布会员：{{ $supply->user?->name }} {{ $supply->user?->company_name }}</p>
</article>
@if($related->count())
<section class="section panel">
    <h3>相关供应</h3>
    <ul class="list-plain body">
        @foreach($related as $item)
            <li><a href="{{ route('sell.show', $item) }}">{{ $item->title }}</a></li>
        @endforeach
    </ul>
</section>
@endif
@endsection
