@extends('layouts.member')
@section('title', '供应管理')
@section('member')
<div class="panel">
    <h3>供应管理 <a class="btn" href="{{ route('member.supplies.create') }}">发布</a></h3>
    <div class="body">
        <table class="table">
            <thead><tr><th>标题</th><th>状态</th><th>操作</th></tr></thead>
            <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->title }}</td>
                    <td>{{ $item->status }}</td>
                    <td>
                        <a href="{{ route('member.supplies.edit', $item) }}">编辑</a>
                        <form action="{{ route('member.supplies.refresh', $item) }}" method="post" style="display:inline">@csrf<button type="submit">刷新</button></form>
                        <form action="{{ route('member.supplies.destroy', $item) }}" method="post" style="display:inline" onsubmit="return confirm('删除？')">@csrf @method('DELETE')<button type="submit">删除</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $items->links() }}
    </div>
</div>
@endsection
