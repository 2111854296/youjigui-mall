@extends('layouts.member')
@section('title', '商务中心')
@section('member')
@if($user->status === 'pending')
<div class="alert">您的会员账号正在审核中，本站部分功能和您的账号有一定的使用限制。建议尽快<a href="{{ route('member.profile') }}">完善资料</a>。</div>
@endif
<div class="panel">
    <h3>欢迎，{{ $user->name }}（{{ $user->username }}）</h3>
    <div class="body">
        <p>状态：{{ $user->status === 'active' ? '正式会员' : '待审核会员' }} · 等级：{{ $user->isVip() ? 'VIP' : '普通' }} · 积分：{{ $user->points }} · 余额：¥{{ number_format($user->balance, 2) }}</p>
        <div class="stats">
            <div class="stat"><b>{{ $stats['supplies'] }}</b>供应</div>
            <div class="stat"><b>{{ $stats['purchases'] }}</b>采购</div>
            <div class="stat"><b>{{ $stats['orders'] }}</b>订单</div>
            <div class="stat"><b>{{ $stats['cart'] }}</b>购物车</div>
            <div class="stat"><b>{{ $stats['messages'] }}</b>新消息</div>
        </div>
        <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn" href="{{ route('member.supplies.create') }}">发布供应</a>
            <a class="btn btn-outline" href="{{ route('member.purchases.create') }}">发布采购</a>
            <a class="btn btn-outline" href="{{ route('mall.index') }}">去商城</a>
        </div>
    </div>
</div>
@endsection
