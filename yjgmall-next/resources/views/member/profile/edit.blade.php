@extends('layouts.member')
@section('title', '修改资料')
@section('member')
<div class="panel">
    <h3>修改资料</h3>
    <div class="body" style="max-width:480px">
        <form method="post" action="{{ route('member.profile.update') }}">
            @csrf @method('PUT')
            <div class="form-row"><label>昵称</label><input name="name" value="{{ old('name', $user->name) }}" required></div>
            <div class="form-row"><label>Email</label><input name="email" value="{{ old('email', $user->email) }}" required></div>
            <div class="form-row"><label>手机</label><input name="mobile" value="{{ old('mobile', $user->mobile) }}"></div>
            <div class="form-row"><label>公司名</label><input name="company_name" value="{{ old('company_name', $user->company_name) }}"></div>
            <div class="form-row"><label>新密码（不改请留空）</label><input type="password" name="password"></div>
            <div class="form-row"><label>确认密码</label><input type="password" name="password_confirmation"></div>
            <button class="btn" type="submit">保存</button>
        </form>
    </div>
</div>
@endsection
