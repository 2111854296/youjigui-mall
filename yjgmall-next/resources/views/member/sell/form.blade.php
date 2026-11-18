@extends('layouts.member')
@section('title', $item->exists ? '编辑供应' : '发布供应')
@section('member')
<div class="panel">
    <h3>{{ $item->exists ? '编辑供应' : '发布供应' }}</h3>
    <div class="body" style="max-width:640px">
        <form method="post" action="{{ $item->exists ? route('member.supplies.update', $item) : route('member.supplies.store') }}">
            @csrf
            @if($item->exists) @method('PUT') @endif
            <div class="form-row"><label>标题</label><input name="title" value="{{ old('title', $item->title) }}" required></div>
            <div class="form-row"><label>价格</label><input name="price" value="{{ old('price', $item->price) }}"></div>
            <div class="form-row"><label>单位</label><input name="unit" value="{{ old('unit', $item->unit) }}"></div>
            <div class="form-row"><label>品牌</label><input name="brand" value="{{ old('brand', $item->brand) }}"></div>
            <div class="form-row"><label>摘要</label><textarea name="summary" rows="2">{{ old('summary', $item->summary) }}</textarea></div>
            <div class="form-row"><label>详情</label><textarea name="content" rows="6">{{ old('content', $item->content) }}</textarea></div>
            <button class="btn" type="submit">提交</button>
        </form>
    </div>
</div>
@endsection
