@extends('layouts.app')
@section('title', '采购')
@section('content')
<div class="breadcrumb"><a href="{{ route('home') }}">首页</a> / 采购</div>
<div class="panel">
    <h3>采购需求</h3>
    <div class="body">
        <table class="table">
            <thead><tr><th>标题</th><th>预算</th><th>数量</th><th>时间</th></tr></thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td><a href="{{ route('buy.show', $item) }}">{{ $item->title }}</a></td>
                    <td>{{ $item->budget ? '¥'.number_format($item->budget,2) : '面议' }}</td>
                    <td>{{ $item->quantity ?: '-' }}</td>
                    <td>{{ $item->refreshed_at?->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">暂无数据</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $items->links() }}
    </div>
</div>
@endsection
