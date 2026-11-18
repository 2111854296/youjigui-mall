@extends('layouts.app')
@section('title', '购物车')
@section('content')
<div class="breadcrumb"><a href="{{ route('home') }}">首页</a> / 购物车</div>
<div class="panel">
    <h3>我的购物车</h3>
    <div class="body">
        <table class="table">
            <thead><tr><th>商品</th><th>单价</th><th>数量</th><th>小计</th></tr></thead>
            <tbody>
            @php $total = 0; @endphp
            @forelse($items as $item)
                @php $sub = $item->product->price * $item->quantity; $total += $sub; @endphp
                <tr>
                    <td>{{ $item->product->title }}</td>
                    <td>¥{{ number_format($item->product->price, 2) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>¥{{ number_format($sub, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">购物车为空</td></tr>
            @endforelse
            </tbody>
        </table>
        @if($items->count())
            <p style="margin-top:12px">合计：<strong style="color:var(--brand-dark)">¥{{ number_format($total, 2) }}</strong></p>
            <form action="{{ route('mall.checkout') }}" method="post" style="margin-top:16px;max-width:480px">
                @csrf
                <div class="form-row"><label>收货人</label><input name="receiver_name" required value="{{ auth()->user()->name }}"></div>
                <div class="form-row"><label>手机</label><input name="receiver_phone" required value="{{ auth()->user()->mobile }}"></div>
                <div class="form-row"><label>地址</label><input name="receiver_address" required></div>
                <div class="form-row"><label>备注</label><textarea name="remark" rows="2"></textarea></div>
                <button class="btn" type="submit">提交订单并模拟支付</button>
            </form>
        @endif
    </div>
</div>
@endsection
