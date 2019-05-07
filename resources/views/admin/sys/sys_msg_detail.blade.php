@extends('admin.layout.index')

@section('content')

    {{--<script src="{{asset('vendor/layer/layer.js')}}"></script>--}}

    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">说明</h1>
        <p class="mb-3">查看通知详情.</p>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-center" id="dataTable" width="100%" cellspacing="0">
                        <tr>
                            <th>
                                内容
                            </th>
                            <td>
                                {!! $res->myContent !!}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                类型
                            </th>
                            <td>
                                {!! $res->myType !!}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                数值
                            </th>
                            <td>
                                {!! $res->myNum !!}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                影响
                            </th>
                            <td>
                                {!! $res->myRange !!}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                范围
                            </th>
                            <td>
                                {!! $res->range !!}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                是否执行
                            </th>
                            <td>
                                {!! $res->exec !!}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                执行时间
                            </th>
                            <td>
                                {!! $res->execTime !!}
                            </td>
                        </tr>

                    </table>
                </div>
            </div>
        </div>

    </div>


    <script>

    </script>

@endsection