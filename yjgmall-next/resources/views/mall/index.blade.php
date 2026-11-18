@extends('layouts.app')
@section('title', '商城')
@section('content')
<div class="breadcrumb"><a href="{{ route('home') }}">首页</a> / 商城</div>
<div class="grid-4">
    @forelse($items as $item)
        <a class="card" href="{{ route('mall.show', $item) }}">
            <div style="height:140px;background:#f3f3f3;display:flex;align-items:center;justify-content:center;color:#999">商品</div>
            <div class="title">{{ $item->title }}</div>
            <div class="price">¥{{ number_format($item->price, 2) }}</div>
        </a>
    @empty
        <p>暂无商品</p>
    @endforelse
</div>
<div style="margin:16px 0">{{ $items->links() }}</div>
@endsection
