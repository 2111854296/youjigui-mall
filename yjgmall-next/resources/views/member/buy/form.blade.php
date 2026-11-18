@extends('layouts.member')
@section('title', '发布采购')
@section('member')
<div class="panel">
    <h3>发布采购</h3>
    <div class="body" style="max-width:640px">
        <form method="post" action="{{ route('member.purchases.store') }}">
            @csrf
            <div class="form-row"><label>标题</label><input name="title" required></div>
            <div class="form-row"><label>预算</label><input name="budget"></div>
            <div class="form-row"><label>数量</label><input name="quantity"></div>
            <div class="form-row"><label>摘要</label><textarea name="summary" rows="2"></textarea></div>
            <div class="form-row"><label>详情</label><textarea name="content" rows="6"></textarea></div>
            <button class="btn" type="submit">提交</button>
        </form>
    </div>
</div>
@endsection
