@extends('layouts.app')
@section('title', '资讯')
@section('content')
<div class="breadcrumb"><a href="{{ route('home') }}">首页</a> / 资讯</div>
<div class="panel">
    <h3>
        资讯行情
        <span>
            <a href="{{ route('news.index') }}">全部</a> |
            <a href="{{ route('news.index', ['type'=>'news']) }}">资讯</a> |
            <a href="{{ route('news.index', ['type'=>'quote']) }}">报价</a>
        </span>
    </h3>
    <ul class="list-plain body">
        @forelse($items as $item)
            <li><a href="{{ route('news.show', $item) }}"><span>{{ $item->is_top ? '[置顶] ' : '' }}{{ $item->title }}</span><span class="muted">{{ $item->published_at?->format('Y-m-d') }}</span></a></li>
        @empty
            <li>暂无资讯</li>
        @endforelse
    </ul>
    <div class="body">{{ $items->links() }}</div>
</div>
@endsection
