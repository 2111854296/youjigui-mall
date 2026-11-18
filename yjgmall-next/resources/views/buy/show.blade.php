@extends('layouts.app')
@section('title', $purchase->title)
@section('content')
<div class="breadcrumb"><a href="{{ route('home') }}">首页</a> / <a href="{{ route('buy.index') }}">采购</a> / 详情</div>
<article class="detail">
    <h1>{{ $purchase->title }}</h1>
    <p>预算：{{ $purchase->budget ? '¥'.number_format($purchase->budget,2) : '面议' }} · 数量：{{ $purchase->quantity ?: '-' }}</p>
    <p>{{ $purchase->summary }}</p>
    <div>{!! nl2br(e($purchase->content)) !!}</div>
</article>
@endsection
