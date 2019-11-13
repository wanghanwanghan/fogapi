<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>AdminLTE 3 | User Profile</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{--jquery--}}
    <script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/css/admin/fontawesome-free/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="/css/admin/dist/css/adminlte.min.css">
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
</head>

<body>

{{--当前虚拟用户的uid--}}
<input type="hidden" id="notRealUid" value="{{$userId}}">
{{--印象id--}}
<input type="hidden" id="articleId" value="{{$article['aid']}}">
{{--印象拥有者id--}}
<input type="hidden" id="oId" value="{{$article['uid']}}">

<div class="col-md-12">
    <div class="card">
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="activity">
                    <div class="post">
                        <div class="user-block">
                            <img class="img-circle img-bordered-sm" src="{{$article['avatar']}}" alt="User Image">
                            <span class="username">
                                <a href="#">{{$article['name']}}</a>
                            </span>
                            <span class="description">
                                <img style="width: 18px;height: 18px;" src="/img/admin/location.png">
                                {{$article['gName']}} - {{$article['created_at']}}
                            </span>
                        </div>

                        <p>{{$article['content']}}</p>

                        <hr>

                        <ul class="users-list clearfix">
                            @if($article['picOrVideo1']!='')
                                <li>
                                    @if(preg_match('/mp4/',$article['picOrVideo1'])>0)
                                        <video autoplay loop><source src="http://newfogapi.wodeluapp.com/{{$article['picOrVideo1']}}" type='video/mp4'>您的浏览器不支持 HTML5 video 标签。</video>
                                    @else
                                        <img src="http://newfogapi.wodeluapp.com/{{$article['picOrVideo1']}}" alt="User Image">
                                    @endif
                                    {{--<a class="users-list-name" href="#">Alexander Pierce</a>--}}
                                    {{--<span class="users-list-date">Today</span>--}}
                                </li>
                            @endif
                            @if($article['picOrVideo2']!='')
                                <li>
                                    <img src="http://newfogapi.wodeluapp.com/{{$article['picOrVideo2']}}" alt="User Image">
                                    {{--<a class="users-list-name" href="#">Alexander Pierce</a>--}}
                                    {{--<span class="users-list-date">Today</span>--}}
                                </li>
                            @endif
                            @if($article['picOrVideo3']!='')
                                <li>
                                    <img src="http://newfogapi.wodeluapp.com/{{$article['picOrVideo3']}}" alt="User Image">
                                    {{--<a class="users-list-name" href="#">Alexander Pierce</a>--}}
                                    {{--<span class="users-list-date">Today</span>--}}
                                </li>
                            @endif
                            @if($article['picOrVideo4']!='')
                                <li>
                                    <img src="http://newfogapi.wodeluapp.com/{{$article['picOrVideo4']}}" alt="User Image">
                                    {{--<a class="users-list-name" href="#">Alexander Pierce</a>--}}
                                    {{--<span class="users-list-date">Today</span>--}}
                                </li>
                            @endif
                            @if($article['picOrVideo5']!='')
                                <li>
                                    <img src="http://newfogapi.wodeluapp.com/{{$article['picOrVideo5']}}" alt="User Image">
                                    {{--<a class="users-list-name" href="#">Alexander Pierce</a>--}}
                                    {{--<span class="users-list-date">Today</span>--}}
                                </li>
                            @endif
                            @if($article['picOrVideo6']!='')
                                <li>
                                    <img src="http://newfogapi.wodeluapp.com/{{$article['picOrVideo6']}}" alt="User Image">
                                    {{--<a class="users-list-name" href="#">Alexander Pierce</a>--}}
                                    {{--<span class="users-list-date">Today</span>--}}
                                </li>
                            @endif
                            @if($article['picOrVideo7']!='')
                                <li>
                                    <img src="http://newfogapi.wodeluapp.com/{{$article['picOrVideo7']}}" alt="User Image">
                                    {{--<a class="users-list-name" href="#">Alexander Pierce</a>--}}
                                    {{--<span class="users-list-date">Today</span>--}}
                                </li>
                            @endif
                            @if($article['picOrVideo8']!='')
                                <li>
                                    <img src="http://newfogapi.wodeluapp.com/{{$article['picOrVideo8']}}" alt="User Image">
                                    {{--<a class="users-list-name" href="#">Alexander Pierce</a>--}}
                                    {{--<span class="users-list-date">Today</span>--}}
                                </li>
                            @endif
                            @if($article['picOrVideo9']!='')
                                <li>
                                    <img src="http://newfogapi.wodeluapp.com/{{$article['picOrVideo9']}}" alt="User Image">
                                    {{--<a class="users-list-name" href="#">Alexander Pierce</a>--}}
                                    {{--<span class="users-list-date">Today</span>--}}
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-md-12">
    <div class="card direct-chat direct-chat-warning">
        <div class="card-header">
            <h3 class="card-title">所有评论如下：<span style="color: red">评论中不能带有#号</span></h3>
            <div class="card-tools">
                {{--<span data-toggle="tooltip" title="3 New Messages" class="badge badge-warning">3</span>--}}
                {{--<button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i>--}}
                {{--</button>--}}
                {{--<button type="button" class="btn btn-tool" data-toggle="tooltip" title="Contacts" data-widget="chat-pane-toggle">--}}
                    {{--<i class="fas fa-comments"></i>--}}
                {{--</button>--}}
                <button type="button" class="btn btn-tool" data-card-widget="remove" onclick="location.reload()">
                    <i class="fa fa-comments"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="direct-chat-messages" style="height: 315px">

                {{--所有评论--}}
                @foreach($comments as $one)

                    <div class="direct-chat-msg">
                        <div class="direct-chat-infos clearfix">
                            @if($one['isShowTargetName']==0)
                                <span class="direct-chat-name float-left">{{$one['uName']}}</span>
                            @else
                                <span class="direct-chat-name float-left">{{$one['uName']}} <span class="badge badge-info">回复</span> {{$one['tName']}}</span>
                            @endif
                            <span class="direct-chat-timestamp float-right">{{$one['created_at']}}</span>
                        </div>
                        <img class="direct-chat-img" src="{{$one['uAvatar']}}" alt="">
                        <div class="direct-chat-text" sendfor="{{$one['uid']}}" onclick="choseOne($(this).attr('sendfor'))">{{$one['comment']}}</div>
                    </div>

                @endforeach

                {{--<div class="direct-chat-msg right">--}}
                    {{--<div class="direct-chat-infos clearfix">--}}
                        {{--<span class="direct-chat-name float-right">Sarah Bullock</span>--}}
                        {{--<span class="direct-chat-timestamp float-left">23 Jan 2:05 pm</span>--}}
                    {{--</div>--}}
                    {{--<img class="direct-chat-img" src="http://newfogapi.wodeluapp.com/img/3/104563_avatar.jpg" alt="message user image">--}}
                    {{--<div class="direct-chat-text">--}}
                        {{--You better believe it!--}}
                    {{--</div>--}}
                {{--</div>--}}
            </div>
        </div>
        <div class="card-footer">
            <div class="input-group">
                <input type="text" name="message" placeholder="" class="form-control" id="commentContent">
                <span class="input-group-append">
                        <button type="button" class="btn btn-warning" onclick="sendComment($('#notRealUid').val())">提交评论</button>
                    </span>
            </div>
        </div>
    </div>
</div>

</body>

<script>

    //选择回复给谁
    function choseOne(tid) {

        $('#commentContent').val('');

        $('#commentContent').val('@'+tid+'# ');
    }

    //发送评论
    function sendComment(uid) {

        //发送者的id，tid从评论input中取得

        var url ='/admin/community/ajax';

        var data={
            _token:$("input[name=_token]").val(),
            aid:$('#articleId').val(),
            uid:uid,
            oid:$('#oId').val(),
            commtent:$('#commentContent').val(),
            type:'sendComment'
        };

        //取视频或图片
        $.post(url,data,function (response)
        {
            if (response.resCode==200)
            {
                location.reload();
            }

        },'json');

    }


</script>
