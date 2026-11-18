<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '有机硅商城') - 领先的有机硅电商综合服务平台</title>
    <meta name="description" content="@yield('description', '有机硅商城-供应采购企业库资讯一体的B2B平台')">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.geo.default')
    @stack('geo')
    @stack('head')
</head>
<body>
<header class="topbar">
    <div class="container inner">
        <div>
            @auth
                欢迎，{{ auth()->user()->name }}（{{ auth()->user()->username }}）
                <a href="{{ route('member.index') }}">商务中心</a>
                <a href="{{ route('member.logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();">退出</a>
                <form id="logout-form" action="{{ route('member.logout') }}" method="POST" style="display:none">@csrf</form>
            @else
                <a href="{{ route('login') }}">请登录</a>
                <a href="{{ route('register') }}">免费注册</a>
            @endauth
        </div>
        <div>
            <a href="{{ route('member.index') }}">商务中心</a>
            <a href="{{ route('page.show', 'vip') }}">VIP服务</a>
            <a href="{{ route('page.show', 'help') }}">客服中心</a>
            <a href="{{ route('page.show', 'safety') }}">网站导航</a>
        </div>
    </div>
</header>

<div class="header">
    <div class="container inner">
        <a class="logo" href="{{ route('home') }}">有机硅商城</a>
        <div class="search-box" x-data="{ type: '{{ request('type', 'sell') }}' }">
            <div class="search-tabs">
                <button type="button" :class="{active: type==='sell'}" @click="type='sell'">供应</button>
                <button type="button" :class="{active: type==='buy'}" @click="type='buy'">采购</button>
                <button type="button" :class="{active: type==='company'}" @click="type='company'">企业库</button>
                <button type="button" :class="{active: type==='news'}" @click="type='news'">资讯</button>
            </div>
            <form class="search-form" action="{{ route('search') }}" method="get">
                <input type="hidden" name="type" :value="type">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="请输入关键词" required>
                <button type="submit">搜索</button>
            </form>
            <div class="hot-words">
                热搜：
                <a href="{{ route('search', ['type'=>'sell','q'=>'107胶']) }}">107胶</a>
                <a href="{{ route('search', ['type'=>'sell','q'=>'DMC']) }}">DMC</a>
                <a href="{{ route('search', ['type'=>'sell','q'=>'混炼胶']) }}">混炼胶</a>
                <a href="{{ route('search', ['type'=>'sell','q'=>'硅油']) }}">硅油</a>
            </div>
        </div>
        <div class="header-actions">
            <a class="btn" href="{{ route('member.supplies.create') }}">发布供应</a>
            <div style="margin-top:8px"><a href="{{ route('mall.cart') }}">购物车</a></div>
        </div>
    </div>
</div>

<nav class="nav">
    <div class="container">
        <ul>
            <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">首页</a></li>
            <li><a href="{{ route('mall.index') }}" class="{{ request()->routeIs('mall.*') ? 'active' : '' }}">商城</a></li>
            <li><a href="{{ route('sell.index') }}" class="{{ request()->routeIs('sell.*') ? 'active' : '' }}">供应</a></li>
            <li><a href="{{ route('buy.index') }}" class="{{ request()->routeIs('buy.*') ? 'active' : '' }}">采购</a></li>
            <li><a href="{{ route('company.index') }}" class="{{ request()->routeIs('company.*') ? 'active' : '' }}">企业库</a></li>
            <li><a href="{{ route('news.index') }}" class="{{ request()->routeIs('news.*') ? 'active' : '' }}">资讯</a></li>
            <li><a href="{{ route('member.index') }}">商务中心</a></li>
        </ul>
    </div>
</nav>

<main class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert">
            @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
        </div>
    @endif
    @yield('content')
</main>

<footer class="footer">
    <div class="container">
        <div class="links">
            <a href="{{ route('home') }}">网站首页</a>
            <a href="{{ route('page.show', 'about') }}">关于我们</a>
            <a href="{{ route('page.show', 'contact') }}">联系方式</a>
            <a href="{{ route('page.show', 'privacy') }}">隐私政策</a>
            <a href="{{ route('page.show', 'terms') }}">用户协议</a>
            <a href="{{ route('page.show', 'help') }}">帮助中心</a>
            <a href="{{ route('page.show', 'safety') }}">交易安全</a>
        </div>
        <div>关键词：有机硅DMC、D4、生胶、107胶、混炼硅橡胶、液体胶、有机硅助剂</div>
        <div style="margin-top:8px">© {{ date('Y') }} 有机硅商城 · 新栈复刻演示站</div>
    </div>
</footer>
</body>
</html>
