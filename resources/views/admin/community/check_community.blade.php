@extends('admin.layout.index')

@section('content')

    <script src="{{asset('vendor/layer/layer.js')}}"></script>

    {{csrf_field()}}

    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">说明</h1>
        <p class="mb-3">审核用户<span style="color: red">发布印象</span></p>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">未审核：<span style="color: red">{{ $waitToCheck }}</span></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-center" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th style='vertical-align: middle'>操作</th>
                            <th style='vertical-align: middle'>用户主键</th>
                            <th style='vertical-align: middle'>格子编号</th>
                            <th style='vertical-align: middle'>图片视频</th>
                            <th style='vertical-align: middle'>图片视频</th>
                            <th style='vertical-align: middle'>图片视频</th>
                            <th style='vertical-align: middle'>图片视频</th>
                            <th style='vertical-align: middle'>图片视频</th>
                            <th style='vertical-align: middle'>图片视频</th>
                            <th style='vertical-align: middle'>图片视频</th>
                            <th style='vertical-align: middle'>图片视频</th>
                            <th style='vertical-align: middle'>图片视频</th>
                            <th style='vertical-align: middle'>发布时间</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($info as $one)

                            <tr>
                                <td style='vertical-align: middle'><a href='javascript:void(0)' id='{{ $one->aid }}' onclick=picPass($(this).attr('id')) class='btn btn-success btn-circle btn-sm'><i class='fas fa-check'></i></a></td>
                                <td style='vertical-align: middle'>{{ $one->uid }}</td>
                                <td style='vertical-align: middle'>{{ $one->gName }}</td>
                                @if($one->picOrVideo1!=null)
                                    <td style='vertical-align: middle'><img src='{{ $one->picOrVideo1 }}' onclick=showpic($(this).attr('src'))></td>
                                @else
                                    <td style='vertical-align: middle'></td>
                                @endif
                                @if($one->picOrVideo2!=null)
                                    <td style='vertical-align: middle'><img src='{{ $one->picOrVideo2 }}' onclick=showpic($(this).attr('src'))></td>
                                @else
                                    <td style='vertical-align: middle'></td>
                                @endif
                                @if($one->picOrVideo3!=null)
                                    <td style='vertical-align: middle'><img src='{{ $one->picOrVideo3 }}' onclick=showpic($(this).attr('src'))></td>
                                @else
                                    <td style='vertical-align: middle'></td>
                                @endif
                                @if($one->picOrVideo4!=null)
                                    <td style='vertical-align: middle'><img src='{{ $one->picOrVideo4 }}' onclick=showpic($(this).attr('src'))></td>
                                @else
                                    <td style='vertical-align: middle'></td>
                                @endif
                                @if($one->picOrVideo5!=null)
                                    <td style='vertical-align: middle'><img src='{{ $one->picOrVideo5 }}' onclick=showpic($(this).attr('src'))></td>
                                @else
                                    <td style='vertical-align: middle'></td>
                                @endif
                                @if($one->picOrVideo6!=null)
                                    <td style='vertical-align: middle'><img src='{{ $one->picOrVideo6 }}' onclick=showpic($(this).attr('src'))></td>
                                @else
                                    <td style='vertical-align: middle'></td>
                                @endif
                                @if($one->picOrVideo7!=null)
                                    <td style='vertical-align: middle'><img src='{{ $one->picOrVideo7 }}' onclick=showpic($(this).attr('src'))></td>
                                @else
                                    <td style='vertical-align: middle'></td>
                                @endif
                                @if($one->picOrVideo8!=null)
                                    <td style='vertical-align: middle'><img src='{{ $one->picOrVideo8 }}' onclick=showpic($(this).attr('src'))></td>
                                @else
                                    <td style='vertical-align: middle'></td>
                                @endif
                                @if($one->picOrVideo9!=null)
                                    <td style='vertical-align: middle'><img src='{{ $one->picOrVideo9 }}' onclick=showpic($(this).attr('src'))></td>
                                @else
                                    <td style='vertical-align: middle'></td>
                                @endif
                                <td style='vertical-align: middle'>{{ $one->created_at }}</td>
                            </tr>

                        @endforeach
                        </tbody>
                    </table>
                </div>
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
                    location.reload();
                });
        }

    </script>

@endsection
