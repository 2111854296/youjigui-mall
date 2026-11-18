@extends('layouts.app')
@section('title', '搜索')
@section('content')
<div class="breadcrumb"><a href="{{ route('home') }}">首页</a> / 搜索</div>
<div class="panel">
    <h3>搜索结果：{{ $q ?: '请输入关键词' }}（{{ $type }}）</h3>
    <div class="body">
        @if($q === '')
            <p class="muted">请输入关键词后搜索</p>
        @elseif(is_object($items) && method_exists($items, 'count'))
            <ul class="list-plain">
                @forelse($items as $item)
                    <li>
                        @if($type === 'buy')
                            <a href="{{ route('buy.show', $item) }}">{{ $item->title }}</a>
                        @elseif($type === 'company')
                            <a href="{{ route('company.show', $item) }}">{{ $item->name }}</a>
                        @elseif($type === 'news')
                            <a href="{{ route('news.show', $item) }}">{{ $item->title }}</a>
                        @elseif($type === 'mall')
                            <a href="{{ route('mall.show', $item) }}">{{ $item->title }}</a>
                        @else
                            <a href="{{ route('sell.show', $item) }}">{{ $item->title }}</a>
                        @endif
                    </li>
                @empty
                    <li>无匹配结果</li>
                @endforelse
            </ul>
            {{ method_exists($items, 'links') ? $items->links() : '' }}
        @endif
    </div>
</div>
@endsection
