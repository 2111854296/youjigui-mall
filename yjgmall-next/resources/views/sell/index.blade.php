@extends('layouts.app')
@section('title', '供应')
@section('content')
<div class="breadcrumb"><a href="{{ route('home') }}">首页</a> / 供应</div>
<div class="grid-2">
    <aside class="panel">
        <h3>分类</h3>
        <div class="side-cats">
            <a href="{{ route('sell.index') }}">全部</a>
            @foreach($categories as $cat)
                <a href="{{ route('sell.index', ['category'=>$cat->id]) }}">{{ $cat->name }}</a>
                @foreach($cat->children as $child)
                    <a href="{{ route('sell.index', ['category'=>$child->id]) }}" style="padding-left:28px">{{ $child->name }}</a>
                @endforeach
            @endforeach
        </div>
    </aside>
    <section class="panel">
        <h3>供应列表</h3>
        <div class="body">
            <table class="table">
                <thead><tr><th>标题</th><th>价格</th><th>企业</th><th>刷新</th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td><a href="{{ route('sell.show', $item) }}">{{ $item->title }}</a></td>
                        <td>{{ $item->price ? '¥'.number_format($item->price,2) : '面议' }}</td>
                        <td>{{ $item->user?->company_name ?: $item->user?->name }}</td>
                        <td>{{ $item->refreshed_at?->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">暂无数据</td></tr>
                @endforelse
                </tbody>
            </table>
            <div style="margin-top:12px">{{ $items->links() }}</div>
        </div>
    </section>
</div>
@endsection
