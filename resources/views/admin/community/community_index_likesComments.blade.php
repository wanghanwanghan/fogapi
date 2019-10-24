@extends('admin.layout.index')

@section('content')

    <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css">
    <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js" type="text/javascript"></script>
    <script src="{{asset('vendor/layer/layer.js')}}"></script>

    <link href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.datatables.net/1.10.19/css/dataTables.jqueryui.min.css" rel="stylesheet" type="text/css">

    {{csrf_field()}}

    <div class="container-fluid">

        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

            <!-- Topbar Navbar -->
            <ul class="navbar-nav mr-auto text-center">

                {{--头像--}}
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="javascript:;" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img class="mr-2 img-profile rounded-circle" src="{{$currentUser['uAvatar']}}" id="currentUserAvatar">
                        <span class="d-none d-lg-inline text-gray-600 small" id="currentUserName">{{$currentUser['uName']}}</span>
                        <input type="hidden" id="currentUserId" value="{{$currentUser['uid']}}">
                    </a>
                    <!-- Dropdown - User Information -->
                    <div class="dropdown-menu dropdown-menu-left shadow animated--grow-in" aria-labelledby="userDropdown" style="width: 250px">
                        <a class="dropdown-item" href="javascript:;">只看与TA相关的印象</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="javascript:;" data-toggle="modal" data-target="#logoutModal">
                            @foreach($notRealUser as $one)
                                <a class="dropdown-item" href="javascript:;" onclick="changeUser({{$one['uid']}},'{{$one['uName']}}','{{$one['uAvatar']}}')">{{$one['uid']}} - {{$one['uName']}}</a>
                            @endforeach
                        </a>
                    </div>
                </li>

                {{--小竖杠--}}
                <div class="topbar-divider d-none d-sm-block"></div>

                {{--信息小红点--}}
                <li class="nav-item dropdown no-arrow mx-1">
                    <a class="nav-link dropdown-toggle" href="javascript:;" id="messagesDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-envelope fa-fw"></i>
                        <!-- Counter - Messages -->
                        <span class="badge badge-danger badge-counter" id="currentUserMsgNum">0</span>
                    </a>
                    <!-- Dropdown - Messages -->
                    <div class="dropdown-list dropdown-menu dropdown-menu-left shadow animated--grow-in" aria-labelledby="messagesDropdown">
                        <div id="currentUserMsgDetail">
                        </div>
                        <a class="dropdown-item text-center small text-gray-500" page="2" onclick="moreDetail($(this));">加载更多</a>
                    </div>
                </li>

            </ul>

        </nav>

        <div class="card shadow mb-4">
            <!-- Card Header - Dropdown -->
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">相关印象</h6>
            </div>
            <!-- Card Body -->
            <div class="card-body">

                <table id="myTable" class="display" style="width: 100%;text-align: center">
                    <thead>
                    <tr>
                        <th>印象</th>
                        <th>用户</th>
                        <th>编号</th>
                        <th>时间</th>
                        <th>点赞</th>
                        <th>评论</th>
                        <th>点赞</th>
                        <th>评论</th>
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
                        <th>点赞</th>
                        <th>评论</th>
                        <th>点赞</th>
                        <th>评论</th>
                        <th>内容</th>
                        <th>删除</th>
                    </tr>
                    </tfoot>
                </table>

            </div>
        </div>




    </div>

    <script>

        //删除这条印象
        function deleteThisArticle(aid) {

            alert('删不了');
        }

        //赞一个
        function setLike(aid) {

            event.stopPropagation();

            var uid=$('#currentUserId').val();

            if (uid<=0)
            {
                alert('没uid');return;
            }

            var url ='/admin/community/ajax';

            var data={_token:$("input[name=_token]").val(),aid:aid,uid:uid,type:'setLike'};

            $.post(url,data,function (response)
            {
                if (response.iLike)
                {
                    //1
                    $("#"+aid).children().remove();
                    $("#"+aid).append("<img width='35px' height='35px' src='/img/admin/like.png'>");

                }else
                {
                    //0
                    $("#"+aid).children().remove();
                    $("#"+aid).append("<img width='35px' height='35px' src='/img/admin/unlike.png'>");
                }
            },'json');
        }

        //评论一个
        function setComment(aid) {

            event.stopPropagation();

            var uid=$('#currentUserId').val();

            if (uid<=0)
            {
                alert('没uid');return;
            }

            layer.open({
                type: 2,
                title: '评论窗',
                shadeClose: true,
                shade: 0.8,
                area: ['70%','95%'],
                content: '/admin/community/setcomment/'+aid+'/'+uid
            });
        }

        //改变虚拟用户
        function changeUser(id,name,avatar)
        {
            $('#currentUserAvatar').attr('src','http://newfogapi.wodeluapp.com'+avatar);
            $('#currentUserName').html(name);
            $('#currentUserId').val(id);

            //加载msg小红点
            getUserMsg(id,1);
        }

        //修改小红点
        function getUserMsg(id,page)
        {
            if (page===1)
            {
                $('#currentUserMsgNum').html(0);

                $('#currentUserMsgDetail').children().remove();
            }

            var url ='/admin/community/ajax';

            var data={_token:$("input[name=_token]").val(),uid:id,page:page,type:'getUserMsg'};

            //取视频或图片
            $.post(url,data,function (response)
            {
                $.each(response.res,function (key,value) {

                    //是评论的还是点赞的
                    if (value['comment'])
                    {
                        $('#currentUserMsgDetail').append("<a class='dropdown-item d-flex align-items-center' href='javascript:;' onclick=setComment('"+value['aid']+"')>" +
                            "<div class='dropdown-list-image mr-3'>" +
                            "<img class='rounded-circle' src='"+value['uAvatar']+"' alt=''>" +
                            "<div class='status-indicator bg-success'></div></div>" +
                            "<div class='font-weight-bold'>" +
                            "<div class='text-truncate'>"+value['comment']+"</div>" +
                            "<div class='small text-gray-500'>"+value['uName']+" @ "+value['unixTime']+"</div></div></a>");

                    }else
                    {
                        $('#currentUserMsgDetail').append("<a class='dropdown-item d-flex align-items-center' href='javascript:;' onclick=setComment('"+value['aid']+"')>" +
                            "<div class='dropdown-list-image mr-3'>" +
                            "<img class='rounded-circle' src='"+value['uAvatar']+"' alt=''>" +
                            "<div class='status-indicator bg-success'></div></div>" +
                            "<div class='font-weight-bold'>" +
                            "<span><svg style='fill: orangered' data-icon='thumb_up' role='presentation' xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' class='svg-icon'><path d='M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-1.91l-.01-.01L23 10z'></path></svg></span>" +
                            "<div class='small text-gray-500'>"+value['uName']+" @ "+value['unixTime']+"</div></div></a>");
                    }
                });

                $('#currentUserMsgNum').html(response.count);

            },'json');
        }

        //小红点的加载更多
        function moreDetail(dom)
        {
            // window.location.replace('/admin/community/index/moredetail/'+$('#currentUserId').val());
            getUserMsg($('#currentUserId').val(),dom.attr('page'));

            dom.attr('page',Number(dom.attr('page'))+1);
        }


        //重新画datatable
        function drawNewDataTable()
        {

        }


        $(document).ready( function () {

            if ($('#currentUserId').val()!=0)
            {
                getUserMsg($('#currentUserId').val(),1);
            }

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
                        d.type='communityIndexLikesComments';
                        d.uid=$('#currentUserId').val()||0;
                        return JSON.stringify(d);
                    },
                },
                "columnDefs":[
                    {"width":"5%","targets":0},//印象主键
                    {"width":"5%","targets":1},//用户主键
                    {"width":"5%","targets":2},//格子编号
                    {"width":"7%","targets":3},//发布时间
                    {"width":"5%","targets":4},//点赞
                    {"width":"5%","targets":5},//评论
                    {"width":"5%","targets":6},//点赞数字
                    {"width":"5%","targets":7},//评论数字
                    {"targets":8},             //印象内容
                    {"width":"5%","targets":9},//删除印象
                ],
                "columns": [
                    {"data":"aid"},
                    {"data":"uid"},
                    {"data":"gName"},
                    {"data":"created_at"},
                    {"data":"setLike"},
                    {"data":"setComment"},
                    {"data":"likes"},
                    {"data":"comments"},
                    {"data":"content"},
                    {"data":"useForDelete"},
                ],
                "order":[3,'desc'],//默认用时间排序，最新的在第一条
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
