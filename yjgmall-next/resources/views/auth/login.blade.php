@extends('layouts.app')
@section('title', '会员登录')
@section('content')
<div class="form-box">
    <h2>会员登录</h2>
    <p class="muted">您尚未登录，或者访问了一个需要登录的页面</p>
    <form method="post" action="{{ route('login') }}">
        @csrf
        <div class="form-row"><label>用户名 / Email</label><input name="login" value="{{ old('login') }}" required></div>
        <div class="form-row"><label>密码</label><input type="password" name="password" required></div>
        <div class="form-row"><label><input type="checkbox" name="remember" value="1"> 记住我</label></div>
        <button class="btn" type="submit">登录</button>
        <a href="{{ route('register') }}" style="margin-left:12px">立即免费注册</a>
    </form>
</div>
@endsection
