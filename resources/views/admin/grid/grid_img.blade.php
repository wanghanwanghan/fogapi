@extends('admin.layout.index')

@section('content')

    <script src="{{asset('vendor/layer/layer.js')}}"></script>

    {{csrf_field()}}

    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">说明</h1>
        <p class="mb-3">审核用户自定义格子图片，<span style="color: red">没有</span>通过审核的图片<span style="color: red">不显示</span>到app上.</p>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">未审核：<span id="noPassTotle" style="color: red">0</span></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-center" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th>格子主键</th>
                            <th>用户主键</th>
                            <th>格子编号</th>
                            <th>格子图片</th>
                            <th>审核操作</th>
                        </tr>
                        </thead>
                        <tbody id="img_tbody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>


    <script>

        //展示大图
        function showpic() {

            layer.open({
                type: 2,
                title: false,
                closeBtn: 0,
                scrollbar: false,
                resize:false,
                area: ['500px','300px'],
                skin: 'layui-layer-nobg', //没有背景色
                shadeClose: true,
                content: 'https://static.laravelacademy.org/wp-content/uploads/2017/09/laravel-routing.png'
            });

        }
        //通过审核
        function picPass(stringId)
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
                        var url ='/admin/grid/ajax';

                        var data=
                            {
                                _token   : $("input[name=_token]").val(),
                                type     : 'picPass',
                                stringId : stringId
                            };

                        $.post(url,data,function (response) {

                        },'json');

                        swal("成功", {
                            icon: "success",
                        });

                        location.reload();

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
                    type  :'get_grid_img'
                };

            $.post(url,data,function (response)
            {
                $.each(response.data,function(key,value)
                {
                    //创建一行
                    var newTr=$("<tr></tr>");

                    //添加gid
                    newTr.append("<td style='vertical-align: middle'>"+value.gid+"</td>");

                    //添加uid
                    newTr.append("<td style='vertical-align: middle'>"+value.uid+"</td>");

                    //格子编号
                    newTr.append("<td style='vertical-align: middle'>"+value.name+"</td>");

                    //图片
                    newTr.append("<td style='vertical-align: middle'><img src="+value.pic1+" onclick=showpic(); width='80px;' height='50px;'></td>");

                    //按钮
                    newTr.append("<td style='vertical-align: middle'><a href='#' id="+value.uid+","+value.gid+" onclick=picPass($(this).attr('id')) class='btn btn-success btn-circle btn-sm'><i class='fas fa-check'></i></a></td>");

                    $("#img_tbody").append(newTr);

                });

                $("#noPassTotle").html(response.count);

            },'json');

        })

    </script>

@endsection