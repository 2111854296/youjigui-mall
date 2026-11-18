@extends('layouts.member')
@section('title', '我的订单')
@section('member')
<div class="panel">
    <h3>我的订单</h3>
    <div class="body">
        <table class="table">
            <thead><tr><th>订单号</th><th>金额</th><th>状态</th><th>时间</th></tr></thead>
            <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->order_no }}</td>
                    <td>¥{{ number_format($item->amount, 2) }}</td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $items->links() }}
    </div>
</div>
@endsection
