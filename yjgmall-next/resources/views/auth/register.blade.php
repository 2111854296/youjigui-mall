@extends('layouts.app')
@section('title', '免费注册')
@section('content')
<div class="form-box">
    <h2>免费注册</h2>
    <form method="post" action="{{ route('register') }}">
        @csrf
        <div class="form-row"><label>用户名</label><input name="username" value="{{ old('username') }}" required></div>
        <div class="form-row"><label>昵称</label><input name="name" value="{{ old('name') }}" required></div>
        <div class="form-row"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
        <div class="form-row"><label>手机</label><input name="mobile" value="{{ old('mobile') }}"></div>
        <div class="form-row"><label>公司名</label><input name="company_name" value="{{ old('company_name') }}"></div>
        <div class="form-row"><label>密码</label><input type="password" name="password" required></div>
        <div class="form-row"><label>确认密码</label><input type="password" name="password_confirmation" required></div>
        <button class="btn" type="submit">注册</button>
    </form>
</div>
@endsection
