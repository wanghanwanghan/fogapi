@extends('admin.layout.index')

@section('content')

    {{csrf_field()}}

    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">说明</h1>
        <p class="mb-3">查看用户反馈的意见.</p>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">共有 <span id="noPassTotle" style="color: red">{{$res->count()}}</span> 条意见</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-center" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>用户主键</th>
                            <th>用户名称</th>
                            <th>意见内容</th>
                            <th>是否回复</th>
                            <th>时间</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($res as $one)

                            <tr>
                                <td style="vertical-align: middle">{!! $one->uid !!}</td>
                                <td style="vertical-align: middle">{!! $one->uName !!}</td>
                                <td style="vertical-align: middle"><a href="{{ route('feedbackDetail',$one->id) }}" style="text-decoration: underline">{!! $one->userContent !!}</a></td>
                                <td>
                                    @if ($one->isReply)
                                        <span class="btn btn-success btn-icon-split">已回复</span>
                                    @else
                                        <span class="btn btn-warning btn-icon-split">未回复</span>
                                    @endif
                                </td>
                                <td style="vertical-align: middle">{!! $one->updated_at !!}</td>
                            </tr>

                        @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    </div>

@endsection