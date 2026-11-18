@extends('layouts.app')
@section('title', $product->title)
@section('content')
<div class="breadcrumb"><a href="{{ route('home') }}">首页</a> / <a href="{{ route('mall.index') }}">商城</a> / 详情</div>
<article class="detail">
    <h1>{{ $product->title }}</h1>
    <p class="price" style="font-size:24px;color:var(--brand-dark)">¥{{ number_format($product->price, 2) }}</p>
    <p>库存：{{ $product->stock }} {{ $product->unit }} · 销量 {{ $product->sales }}</p>
    <p>{{ $product->summary }}</p>
    <div>{!! nl2br(e($product->content)) !!}</div>
    @auth
    <form action="{{ route('mall.cart.add', $product) }}" method="post" style="margin-top:16px">
        @csrf
        <input type="number" name="quantity" value="1" min="1" style="width:80px;padding:8px">
        <button class="btn" type="submit">加入购物车</button>
    </form>
    @else
        <a class="btn" href="{{ route('login') }}">登录后购买</a>
    @endauth
</article>
@endsection
