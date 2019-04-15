@extends('admin.layout.index')

@section('content')

    {{csrf_field()}}

    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">说明</h1>
        <p class="mb-3">审核用户自定义格子名称，<span style="color: red">没有</span>通过审核的名称<span style="color: red">不显示</span>到app上.</p>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-center" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>格子主键</th>
                            <th>用户主键</th>
                            <th>格子编号</th>
                            <th>用户名称</th>
                            <th>格子名称</th>
                            <th>审核操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td style="vertical-align: middle">1</td>
                            <td style="vertical-align: middle">1</td>
                            <td style="vertical-align: middle">w1n1</td>
                            <td style="vertical-align: middle">可爱多</td>
                            <td style="color: red;vertical-align: middle">黄小超的格子格子</td>
                            <td style="vertical-align: middle">
                                <a href="#" onclick="namePass();" class="btn btn-success btn-circle btn-sm">
                                    <i class="fas fa-check"></i>
                                </a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script>

        //通过审核
        function namePass()
        {
            swal({
                title: "确定通过吗",
                text: "一旦同意，不可退回",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
                .then((willDelete) => {
                    if (willDelete) {

                        //发送请求

                        swal("成功", {
                            icon: "success",
                        });
                    } else {
                        //swal("取消");
                    }
                });
        }

        $(function () {

            //获取未审核格子数据
            var url ='/admin/grid/ajax';

            var data=
                {
                    _token:$("input[name=_token]").val(),
                    type  :'get_grid_name'
                };

            $.post(url,data,function (response)
            {
                return;

            },'json');

        })

    </script>

@endsection