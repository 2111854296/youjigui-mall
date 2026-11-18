@extends('layouts.member')
@section('title', '采购管理')
@section('member')
<div class="panel">
    <h3>采购管理 <a class="btn" href="{{ route('member.purchases.create') }}">发布</a></h3>
    <div class="body">
        <table class="table">
            <thead><tr><th>标题</th><th>状态</th><th>操作</th></tr></thead>
            <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->title }}</td>
                    <td>{{ $item->status }}</td>
                    <td>
                        <form action="{{ route('member.purchases.destroy', $item) }}" method="post" onsubmit="return confirm('删除？')">@csrf @method('DELETE')<button type="submit">删除</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $items->links() }}
    </div>
</div>
@endsection
