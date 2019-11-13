@extends('admin.layout.index')

@section('content')

    <script src="{{asset('vendor/layer/layer.js')}}"></script>

    {{csrf_field()}}

    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">说明</h1>
        <p class="mb-3">审核用户头像<span style="color: red">自定义</span>图片，<span style="color: red">没有</span>通过审核的图片<span style="color: red">不显示</span>到app上.</p>

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
                            <th>用户主键</th>
                            <th>用户名称</th>
                            <th>头像图片</th>
                            <th>审核操作</th>
                        </tr>
                        </thead>
                        <tbody id="img_tbody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="img_div" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content bg-transparent" style="border:none">
                    <div class="modal-body align-items-center text-center">
                        <br>
                        <div id="img_content"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>

        //展示大图
        function showpic(imgUrl) {

            $("#img_content").children().remove();

            var data=
                {
                    _token:$("input[name=_token]").val(),
                    imgUrl:imgUrl,
                    type  :'get_img_size',
                };

            $.post('/admin/user/ajax',data,function (response) {

                //填上图片
                $('#img_content').append("<img width="+response.width+"px; height="+response.width+"px; src="+imgUrl+">");

                //弹出图片
                $('#img_div').modal('show');

            },'json');

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
                            var url ='/admin/user/ajax';

                            var data=
                                {
                                    _token   : $("input[name=_token]").val(),
                                    type     : 'picPass',
                                    stringId : stringId,
                                };

                            $.post(url,data,function () {},'json');

                            swal("通过审核", "app上已经可以显示了", "success");

                            //====================================================

                            break;

                        case "nopass":

                            //====================================================
                            var url ='/admin/user/ajax';

                            var data=
                                {
                                    _token   : $("input[name=_token]").val(),
                                    type     : 'picNoPass',
                                    stringId : stringId,
                                };

                            $.post(url,data,function () {},'json');

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
            var url ='/admin/user/ajax';

            var data=
                {
                    _token:$("input[name=_token]").val(),
                    type  :'get_user_img'
                };

            $.post(url,data,function (response)
            {
                $.each(response.data,function(key,value)
                {
                    //创建一行
                    var newTr=$("<tr></tr>");

                    //添加uid
                    newTr.append("<td style='vertical-align: middle'>"+value.uid+"</td>");

                    //用户名称
                    newTr.append("<td style='vertical-align: middle'>"+value.name+"</td>");

                    //头像
                    newTr.append("<td style='vertical-align: middle'><img src="+value.avatarUrl+" onclick=showpic('"+value.avatarUrl+"'); width='80px;' height='50px;'></td>");

                    //按钮
                    newTr.append("<td style='vertical-align: middle'><a href='#' id="+value.id+","+value.uid+" onclick=picPass($(this).attr('id')) class='btn btn-success btn-circle btn-sm'><i class='fas fa-check'></i></a></td>");

                    $("#img_tbody").append(newTr);

                });

                $("#noPassTotle").html(response.count);

            },'json');

        })

    </script>

@endsection