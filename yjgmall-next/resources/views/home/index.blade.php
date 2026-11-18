@extends('layouts.app')

@section('title', '首页')

@section('content')
<div class="layout-home">
    <aside class="panel">
        <div class="cat-nav">全部行业分类</div>
        <div class="side-cats">
            @foreach($rootCategories as $cat)
                <a href="{{ route('sell.index', ['category' => $cat->id]) }}">{{ $cat->name }}</a>
            @endforeach
        </div>
    </aside>

    <section>
        <div class="banner panel">
            @forelse($banners as $banner)
                <a class="banner-slide" href="{{ $banner->link ?: '#' }}" style="@if($banner->image)background:url('{{ $banner->image }}') center/cover;@endif">
                    {{ $banner->title }}
                </a>
            @empty
                <div class="banner-slide">有机硅商城 · 领先的有机硅电商综合服务平台</div>
            @endforelse
        </div>
        <div class="grid-2" style="margin-top:12px">
            <div class="panel">
                <h3>最新供应 <a href="{{ route('sell.index') }}" style="font-size:12px;font-weight:400">更多</a></h3>
                <ul class="list-plain body">
                    @foreach($supplies as $item)
                        <li><a href="{{ route('sell.show', $item) }}"><span>{{ $item->title }}</span><span class="muted">{{ $item->refreshed_at?->format('m-d') }}</span></a></li>
                    @endforeach
                </ul>
            </div>
            <div class="panel">
                <h3>最新求购 <a href="{{ route('buy.index') }}" style="font-size:12px;font-weight:400">更多</a></h3>
                <ul class="list-plain body">
                    @foreach($purchases as $item)
                        <li><a href="{{ route('buy.show', $item) }}"><span>{{ $item->title }}</span><span class="muted">{{ $item->refreshed_at?->format('m-d') }}</span></a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <aside>
        <div class="panel">
            <h3>快捷入口</h3>
            <div class="body" style="display:grid;gap:8px">
                <a class="btn" href="{{ route('member.supplies.create') }}">发布供求信息</a>
                <a class="btn btn-outline" href="{{ route('register') }}">免费开通旺铺</a>
                <a class="btn btn-outline" href="{{ route('member.profile') }}">完善企业资料</a>
                <a class="btn btn-dark" href="{{ route('member.index') }}">进入商务中心</a>
            </div>
        </div>
        <div class="panel" style="margin-top:12px">
            <h3>行业报价</h3>
            <ul class="list-plain body">
                @foreach($quotes as $quote)
                    <li><a href="{{ route('news.show', $quote) }}">{{ $quote->title }}</a></li>
                @endforeach
            </ul>
        </div>
    </aside>
</div>

<section class="section panel">
    <h3>商城精选 <a href="{{ route('mall.index') }}" style="font-size:12px;font-weight:400">更多</a></h3>
    <div class="body grid-4">
        @foreach($products as $product)
            <a class="card" href="{{ route('mall.show', $product) }}">
                <div style="height:120px;background:#f3f3f3;display:flex;align-items:center;justify-content:center;color:#999">商品图</div>
                <div class="title">{{ $product->title }}</div>
                <div class="price">¥{{ number_format($product->price, 2) }}</div>
            </a>
        @endforeach
    </div>
</section>

<section class="section grid-2">
    <div class="panel">
        <h3>行业资讯 <a href="{{ route('news.index') }}" style="font-size:12px;font-weight:400">更多</a></h3>
        <ul class="list-plain body">
            @foreach($articles as $article)
                <li><a href="{{ route('news.show', $article) }}"><span>{{ $article->title }}</span><span class="muted">{{ $article->published_at?->format('m-d') }}</span></a></li>
            @endforeach
        </ul>
    </div>
    <div class="panel">
        <h3>名企推荐 <a href="{{ route('company.index') }}" style="font-size:12px;font-weight:400">更多</a></h3>
        <ul class="list-plain body">
            @foreach($companies as $company)
                <li><a href="{{ route('company.show', $company) }}">{{ $company->name }}</a></li>
            @endforeach
        </ul>
    </div>
</section>
@endsection
