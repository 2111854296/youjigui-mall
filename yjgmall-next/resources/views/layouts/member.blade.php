@extends('layouts.app')

@section('content')
<div class="member-layout">
    <aside class="panel member-side">
        <h3>商务中心</h3>
        <a href="{{ route('member.index') }}" class="{{ request()->routeIs('member.index') ? 'active' : '' }}">会员服务</a>
        <a href="{{ route('member.supplies') }}" class="{{ request()->routeIs('member.supplies*') ? 'active' : '' }}">供应管理</a>
        <a href="{{ route('member.purchases') }}" class="{{ request()->routeIs('member.purchases*') ? 'active' : '' }}">采购管理</a>
        <a href="{{ route('member.orders') }}" class="{{ request()->routeIs('member.orders*') ? 'active' : '' }}">我的订单</a>
        <a href="{{ route('mall.cart') }}">购物车</a>
        <a href="{{ route('member.messages') }}">站内消息</a>
        <a href="{{ route('member.profile') }}" class="{{ request()->routeIs('member.profile*') ? 'active' : '' }}">修改资料</a>
        <a href="{{ route('home') }}">网站首页</a>
    </aside>
    <section>
        @yield('member')
    </section>
</div>
@endsection
