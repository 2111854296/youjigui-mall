@extends('layouts.app')
@section('title', '企业库')
@section('content')
<div class="breadcrumb"><a href="{{ route('home') }}">首页</a> / 企业库</div>
<div class="panel">
    <h3>企业黄页</h3>
    <div class="body grid-4">
        @forelse($items as $item)
            <a class="card" href="{{ route('company.show', $item) }}">
                <div class="title">{{ $item->name }}</div>
                <div class="muted">{{ $item->address }}</div>
            </a>
        @empty
            <p>暂无企业</p>
        @endforelse
    </div>
    <div style="padding:12px">{{ $items->links() }}</div>
</div>
@endsection
