@extends('admin.layout.index')

@section('content')


    <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css">
    <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js" type="text/javascript"></script>
    <script src="{{asset('vendor/layer/layer.js')}}"></script>

    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.datatables.net/1.10.19/css/dataTables.jqueryui.min.css" rel="stylesheet" type="text/css">

    {{csrf_field()}}

    <div class="container-fluid">

        <!-- Page Heading -->
        {{--<h1 class="h3 mb-2 text-gray-800">说明</h1>--}}
        {{--<p class="mb-3">印象置顶加精</p>--}}

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                {{--<a href="#" id="mySubmit1" class="btn btn-success btn-icon-split">--}}
                    {{--<span class="text">加精</span>--}}
                {{--</a>--}}

                {{--<a href="#" id="mySubmit2" class="btn btn-danger btn-icon-split">--}}
                    {{--<span class="text">置顶</span>--}}
                {{--</a>--}}
            </div>
            <div class="card-body">

                <table id="myTable" class="display" style="width: 100%;text-align: center">
                    <thead>
                    <tr>
                        <th>印象</th>
                        <th>用户</th>
                        <th>编号</th>
                        <th>时间</th>
                        <th>置顶(格)</th>
                        <th>精华(格)</th>
                        {{--<th>置顶(广)</th>--}}
                        <th>内容</th>
                        <th>删除</th>
                    </tr>
                    </thead>

                    {{--<tbody>--}}
                    {{--</tbody>--}}

                    <tfoot>
                    <tr>
                        <th>印象</th>
                        <th>用户</th>
                        <th>编号</th>
                        <th>时间</th>
                        <th>置顶(格)</th>
                        <th>精华(格)</th>
                        {{--<th>置顶(广)</th>--}}
                        <th>内容</th>
                        <th>删除</th>
                    </tr>
                    </tfoot>
                </table>

            </div>
        </div>

        <!--二维码,随便放在当前页面的那里都可以,因为是通过ajax控制,请求成功后才会弹出的-->
        <div class="modal fade" id="img_div" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content bg-transparent" style="border:none">
                    <div class="modal-body align-items-center text-center">
                        <br>
                        {{--生成的二维码会放在这里--}}
                        <div id="img_content"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>

        //展示大图
        function showpic(url) {

            //var baseUrl='http://newfogapi.wodeluapp.com'+url;
            var baseUrl=url;

            $("#img_content").children().remove();

            if (baseUrl.search(/video/i)>0)
            {
                //是视频
                baseUrl=baseUrl.replace('thum','origin');
                baseUrl=baseUrl.replace('jpg','mp4');

                $('#img_content').append("<video autoplay loop><source src='"+baseUrl+"' type='video/mp4'>您的浏览器不支持 HTML5 video 标签。</video>");
            }else
            {
                //是图片
                baseUrl=baseUrl.replace('thum','origin');
                $('#img_content').append("<img src="+baseUrl+">");
            }

            //弹出
            $('#img_div').modal('show');
        }

        //通过审核
        function picPass(aid)
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
                            var url ='/admin/community/ajax';

                            var data=
                                {
                                    _token   : $("input[name=_token]").val(),
                                    type     : 'pass',
                                    aid      : aid
                                };

                            $.post(url,data,function (response) {

                                if (response.resCode==200)
                                {
                                    swal("通过审核", "app上已经可以显示了", "success");

                                }else
                                {
                                    alert('error');return;
                                }

                            },'json');

                            //====================================================

                            break;

                        case "nopass":

                            //====================================================
                            var url ='/admin/community/ajax';

                            var data=
                                {
                                    _token   : $("input[name=_token]").val(),
                                    type     : 'nopass',
                                    aid      : aid
                                };

                            $.post(url,data,function (response) {

                                if (response.resCode==200)
                                {
                                    swal("删除成功", "印象已经没有了，不能恢复了", "success");

                                }else
                                {
                                    alert('error');return;
                                }

                            },'json');

                            //====================================================

                            break;

                        default:
                            swal("考虑好了再审");
                    }

                    //刷新页面
                    setTimeout(function()
                    {
                        location.reload();
                    },1000);

                });
        }

        //设置成置顶
        function setTop(aid)
        {
            event.stopPropagation();

            topAndTheBestAjax(aid,'setTop');

            //点击之前是叉子状态
            //设置成置顶以后要显示对勾
            //绑定成对勾按钮，事件是取消置顶
            $("#"+aid).attr('onclick',"cancleTop($(this).attr('id'))");
            $("#"+aid).attr('class',"btn btn-success btn-circle btn-sm");
            $("#"+aid).children().remove();
            $("#"+aid).append("<i class='fas fa-check'></i>");
        }

        //设置成精华
        function setTheBest(aid)
        {
            event.stopPropagation();

            topAndTheBestAjax(aid,'setTheBest');

            //点击之前是叉子状态
            //加精以后要显示对勾
            //绑定成对勾按钮，事件是取消加精
            var node=$("[id="+aid+"]").last();
            node.attr('onclick',"cancleTheBest($(this).attr('id'))");
            node.attr('class',"btn btn-success btn-circle btn-sm");
            node.children().remove();
            node.append("<i class='fas fa-check'></i>");
        }

        //取消置顶
        function cancleTop(aid)
        {
            event.stopPropagation();

            topAndTheBestAjax(aid,'cancleTop');

            //点击之前是对勾状态
            //设置成置顶以后要显示叉子
            //绑定成叉子按钮，事件是置顶
            $("#"+aid).attr('onclick',"setTop($(this).attr('id'))");
            $("#"+aid).attr('class',"btn btn-danger btn-circle btn-sm");
            $("#"+aid).children().remove();
            $("#"+aid).append("<i class='fas fa-times'></i>");
        }

        //取消精华
        function cancleTheBest(aid)
        {
            event.stopPropagation();

            topAndTheBestAjax(aid,'cancleTheBest');

            //点击之前是对勾状态
            //取消加精以后要显示叉子
            //绑定成叉子按钮，事件是加精
            var node=$("[id="+aid+"]").last();
            node.attr('onclick',"setTheBest($(this).attr('id'))");
            node.attr('class',"btn btn-danger btn-circle btn-sm");
            node.children().remove();
            node.append("<i class='fas fa-times'></i>");
        }

        //删除这条印象
        function deleteThisArticle(aid)
        {
            event.stopPropagation();

            topAndTheBestAjax(aid,'deleteThisArticle');

            location.reload();
        }

        //置顶，精华，删除 ajax请求
        function topAndTheBestAjax(aid,type)
        {
            var url ='/admin/community/ajax';
            var data=
                {
                    _token:$("input[name=_token]").val(),
                    aid   :aid,
                    type  :type,//setTop,setTheBest,cancleTop,cancleTheBest,deleteThisArticle
                };

            $.post(url,data,function (response) {

                if (response.resCode==200)
                {

                }else
                {
                    alert('error');return;
                }

            },'json');
        }

        $(document).ready( function () {

            var lastIdx = null;

            var table = $('#myTable').DataTable({
                "autoWidth":  false,
                "processing": true,
                "serverSide": true,
                "ajax": {
                    contentType: "application/json",
                    dataType: 'json',
                    url: "/admin/community/ajax",
                    type: "POST",
                    data: function(d){
                        d._token=$("input[name=_token]").val();
                        d.type='communityIndex';
                        return JSON.stringify(d);
                    },
                },
                "columnDefs":[
                    {"width":"5%","targets":0},
                    {"width":"5%","targets":1},
                    {"width":"5%","targets":2},
                    {"width":"7%","targets":3},
                    {"width":"5%","targets":4},
                    {"width":"5%","targets":5},
                    {"targets":6},
                    {"width":"5%","targets":7},
                ],
                "columns": [
                    {"data":"aid"},
                    {"data":"uid"},
                    {"data":"gName"},
                    {"data":"created_at"},
                    {"data":"isTop"},
                    {"data":"theBest"},
                    {"data":"content"},
                    {"data":"useForDelete"},
                ],
                "order":[3,'desc'],//默认用时间排序，最新的再第一条
                "language":{
                    "lengthMenu": "每页显示 _MENU_ 记录",
                    "zeroRecords": "无记录",
                    "info": "第 _PAGE_ 页，共 _PAGES_ 页",
                    "infoEmpty": "无记录",
                    "infoFiltered": "无记录",
                    "sSearch":"搜索",
                    "sLoadingRecords": 	"正在加载，请稍等...",
                    "sProcessing":   	"正在加载，请稍等...",
                    "oPaginate": {
                        "sFirst":    	"开始页",
                        "sPrevious": 	"上一页",
                        "sNext":     	"下一页",
                        "sLast":     	"最后页"
                    },
                }
            });

            //给每行绑定时间，查看图或视频
            $('#myTable tbody').on('click','tr',function () {

                $("#img_content").children().remove();

                var data = table.row(this).data();

                //拿到aid
                var aid = data.aid;

                var url ='/admin/community/ajax';

                //取视频或图片
                $.post(url,{_token:$("input[name=_token]").val(),aid:aid,type:'getVideoOrPic'},function (response) {

                    if (response.resCode==201)
                    {
                        alert('没有图片或视频');
                        return;
                    }

                    if (response.resCode!=200)
                    {
                        alert('error');
                        return;
                    }

                    $.each(response.url,function (key,value)
                    {
                        if (value && value.search(/video/i)>0)
                        {
                            //视频
                            $('#img_content').append("<video autoplay loop><source src='"+value+"' type='video/mp4'>您的浏览器不支持 HTML5 video 标签。</video>");
                        }

                        if (value && value.search(/pic/i)>0)
                        {
                            //图片
                            value=value.replace('thum','origin');
                            $('#img_content').append("<img width='600px' src="+value+">");
                        }
                    });

                },'json');

                //弹出二维码
                $('#img_div').modal('show');

            });


        });

    </script>

@endsection
