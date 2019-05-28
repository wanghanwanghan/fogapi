@extends('admin.layout.index')

@section('content')

    <script src="{{asset('vendor/layer/layer.js')}}"></script>

    {{csrf_field()}}

    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">说明</h1>
        <p class="mb-3">审核用户格子<span style="color: red">自定义</span>图片，<span style="color: red">没有</span>通过审核的图片<span style="color: red">不显示</span>到app上.</p>

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
        function showpic(url) {

            layer.open({
                type: 2,
                title: false,
                closeBtn: 0,
                scrollbar: false,
                resize:false,
                // area: ['200px','200px'],
                skin: 'layui-layer-nobg', //没有背景色
                shadeClose: true,
                content: 'http://newfogapi.wodeluapp.com'+url
            });

        }
        //通过审核
        function picPass(stringId)
        {
            swal("纪申你要考虑清楚", {
                title: "通过审核，还是删除不合格图片？",
                icon: "warning",
                buttons: {
                    nothing: "什么都不做",
                    nopass: {
                        text: "删除图片",
                        value: "nopass",
                    },
                    pass: '通过审核',
                },
            })
                .then((value) => {
                    switch (value) {

                        case "pass":

                            //====================================================
                            var url ='/admin/grid/ajax';

                            var data=
                                {
                                    _token   : $("input[name=_token]").val(),
                                    type     : 'picPass',
                                    stringId : stringId,
                                    whitchPic:1,
                                };

                            $.post(url,data,function (response) {

                            },'json');

                            swal("通过审核", "app上已经可以显示了", "success");

                            //====================================================

                            break;

                        case "nopass":

                            //====================================================
                            var url ='/admin/grid/ajax';

                            var data=
                                {
                                    _token   : $("input[name=_token]").val(),
                                    type     : 'picNoPass',
                                    stringId : stringId,
                                    whitchPic:1,
                                };

                            $.post(url,data,function (response) {

                            },'json');

                            swal("删除成功", "图片已经没有了，不能恢复了", "success");
                            //====================================================

                            break;

                        default:
                            swal("考虑好了再审");
                    }

                    //刷新页面
                    location.reload();
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
                    newTr.append("<td style='vertical-align: middle'><img src="+value.picUrl+" onclick=showpic('"+value.picUrl+"'); width='80px;' height='50px;'></td>");

                    //按钮
                    newTr.append("<td style='vertical-align: middle'><a href='#' id="+value.uid+","+value.gid+" onclick=picPass($(this).attr('id')) class='btn btn-success btn-circle btn-sm'><i class='fas fa-check'></i></a></td>");

                    $("#img_tbody").append(newTr);

                });

                $("#noPassTotle").html(response.count);

            },'json');

        })

    </script>

@endsection