@extends('layouts.member')
@section('title', '站内消息')
@section('member')
<div class="panel">
    <h3>站内消息</h3>
    <ul class="list-plain body">
        @forelse($items as $item)
            <li><strong>{{ $item->title }}</strong><div class="muted">{{ $item->created_at->format('Y-m-d H:i') }}</div><div>{{ $item->content }}</div></li>
        @empty
            <li>暂无消息</li>
        @endforelse
    </ul>
    <div class="body">{{ $items->links() }}</div>
</div>
@endsection
