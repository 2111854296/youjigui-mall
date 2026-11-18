@extends('layouts.app')
@section('title', $company->name)
@section('content')
<div class="breadcrumb"><a href="{{ route('home') }}">首页</a> / <a href="{{ route('company.index') }}">企业库</a> / {{ $company->name }}</div>
<article class="detail">
    <h1>{{ $company->name }}</h1>
    <p>联系人：{{ $company->contact }} · 电话：{{ $company->phone }}</p>
    <p>地址：{{ $company->address }}</p>
    <p>{{ $company->intro }}</p>
    <div>{!! nl2br(e($company->content)) !!}</div>
</article>
@endsection
