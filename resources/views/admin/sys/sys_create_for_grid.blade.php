@extends('admin.layout.index')

@section('content')

    {{--<script src="{{asset('vendor/layer/layer.js')}}"></script>--}}

    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">说明</h1>
        <p class="mb-3">已经发布的系统通知.</p>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <a href="{{route('sysCreateMsgForGrid')}}" class="btn btn-success btn-icon-split">
                        <span class="text">新建公告</span>
                        <span class="icon text-white-50">
                            <i class="fas fa-smile-beam"></i>
                        </span>
                    </a>
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-center" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>标题</th>
                            <th>影响</th>
                            <th>类型</th>
                            <th>数值</th>
                            <th>是否执行</th>
                            <th>执行时间</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($res as $one)

                            <tr>
                                <td>{!! $one->mySubject !!}</td>
                                <td>{!! $one->myRange !!}</td>
                                <td>{!! $one->myType !!}</td>
                                <td>{!! $one->myNum !!}%</td>
                                <td>{!! $one->exec !!}</td>
                                <td>{!! $one->execTime !!}</td>
                                <td>
                                    <a href="{{route('sysMsgDetail',$one->id)}}" class="btn btn-info btn-circle btn-sm">
                                        <i class="fas fa-info-circle"></i>
                                    </a>
                                </td>
                            </tr>

                        @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>


    <script>

    </script>

@endsection