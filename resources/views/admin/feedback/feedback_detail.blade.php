@extends('admin.layout.index')

@section('content')

    {{csrf_field()}}

    <input type="hidden" id="feedbackId" value="{{$res->id}}">

    <script type="text/javascript" src="//unpkg.com/wangeditor/release/wangEditor.min.js"></script>

    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">反馈详情</h6>
            </div>
            <div class="card-body">

                <p style="font-size: 12px"><span class="text-xs font-weight-bold text-primary">用户主键：</span>{{$res->uid}}</p>
                <p style="font-size: 12px"><span class="text-xs font-weight-bold text-primary">用户名称：</span>{{$res->uName}}</p>
                <p style="font-size: 12px"><span class="text-xs font-weight-bold text-primary">反馈内容：</span>{{$res->userContent}}</p>
                <p style="font-size: 12px"><span class="text-xs font-weight-bold text-primary">反馈时间：</span>{{$res->created_at}}</p>

                <div class="col-lg-12">

                    <div class="row">

                        @for($i=1;$i<=6;$i++)

                            <?php $tar='userPic'.$i ?>

                            @if($res->$tar!='')
                                    <div class="col-lg-2 mb-4">
                                        <div class="card bg-primary text-white shadow">
                                            <div class="card-body">
                                                图片{!! $i !!}
                                                <div class="text-white-50 small">
                                                    <a href="{!! $res->$tar !!}" target="_blank" class="text-white-50 small">查看图片</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            @endif

                        @endfor

                    </div>

                    <div class="row">

                        @for($i=1;$i<=3;$i++)

                            <?php $tar='userVideo'.$i ?>

                            @if($res->$tar!='')
                                    <div class="col-lg-2">
                                        <div class="card bg-info text-white shadow">
                                            <div class="card-body">
                                                视频{!! $i !!}
                                                <div class="text-white-50 small">
                                                    <a href="{!! $res->$tar !!}" target="_blank" class="text-white-50 small">查看视频</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            @endif

                        @endfor

                    </div>

                </div>

                <hr>

                <p style="font-size: 12px"><span class="text-xs font-weight-bold text-primary">官方回复：</span>{{$res->tssjContent}}</p>
                <p style="font-size: 12px"><span class="text-xs font-weight-bold text-primary">回复时间：</span>@if($res->tssjContent){{$res->updated_at}}@endif</p>

                <div class="col-lg-12">

                    <div class="row">

                        @for($i=1;$i<=6;$i++)

                            <?php $tar='tssjPic'.$i ?>

                            @if($res->$tar!='')
                                <div class="col-lg-2 mb-4">
                                    <div class="card bg-primary text-white shadow">
                                        <div class="card-body">
                                            图片{!! $i !!}
                                            <div class="text-white-50 small">
                                                <a href="{!! $res->$tar !!}" target="_blank" class="text-white-50 small">查看图片</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                        @endfor

                    </div>

                    <div class="row">

                        @for($i=1;$i<=3;$i++)

                            <?php $tar='tssjVideo'.$i ?>

                            @if($res->$tar!='')
                                <div class="col-lg-2">
                                    <div class="card bg-info text-white shadow">
                                        <div class="card-body">
                                            视频{!! $i !!}
                                            <div class="text-white-50 small">
                                                <a href="{!! $res->$tar !!}" target="_blank" class="text-white-50 small">查看视频</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                        @endfor

                    </div>

                </div>

            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary float-left">官方回复</h6>
                <a href="#" id="mySubmit1" class="btn btn-success btn-icon-split float-right">
                    <span class="text">提交</span>
                </a>
            </div>
            <div class="card-body">

                <div class="col-12">
                    <div id="div1" style="background-color:#f1f1f1; border:1px solid #ccc;" class="toolbar"></div>
                </div>

                <div class="col-12">
                    <div id="div2" style="border:1px solid #ccc; border-top:none; height:400px; z-index:10000;" class="text"></div>
                </div>

            </div>
        </div>
    </div>

    <script type="text/javascript">

        var E = window.wangEditor;

        var editor = new E('#div1','#div2');

        //开启的功能
        editor.customConfig.menus = [
            'head',          //标题
            'bold',          //粗体
            'fontSize',      //字号
            'fontName',      //字体
            'italic',        //斜体
            'underline',     //下划线
            'strikeThrough', //删除线
            'foreColor',     //文字颜色
            'backColor',     //背景颜色
            //'link',          //插入链接
            'list',          //列表
            'justify',       //对齐方式
            //'quote',         //引用
            //'emoticon',      //表情
            'image',         //插入图片
            //'table',         //表格
            //'video',         //插入视频
            //'code',          //插入代码
            'undo',          //撤销
            'redo'           //重复
        ];

        //将图片大小限制为 3M
        editor.customConfig.uploadImgMaxSize = 3 * 1024 * 1024;

        //限制一次最多上传 6  张图片
        editor.customConfig.uploadImgMaxLength = 6;

        //上传图片时可自定义传递一些参数，例如传递验证的token等。参数会被添加到formdata中。
        editor.customConfig.uploadImgParams = {
            // 如果版本 <=v3.1.0 ，属性值会自动进行 encode ，此处无需 encode
            // 如果版本 >=v3.1.1 ，属性值不会自动 encode ，如有需要自己手动 encode
            token: $("input[name=_token]").val()
        };

        //如果还需要将参数拼接到 url 中，可再加上如下配置，true是拼接，false是不拼接
        editor.customConfig.uploadImgParamsWithUrl = false;

        //下面两个配置，使用其中一个即可显示“上传图片”的tab。但是两者不要同时使用！！！
        //editor.customConfig.uploadImgShowBase64 = true;   // 使用 base64 保存图片
        editor.customConfig.uploadImgServer = "{{route('feedbackUploadPic',$res->id)}}";  // 上传图片到服务器

        //将 timeout 时间改为 10s
        editor.customConfig.uploadImgTimeout = 10000;

        //隐藏“网络图片”tab
        editor.customConfig.showLinkImg = false;

        //忽略粘贴内容中的图片
        editor.customConfig.pasteIgnoreImg = true;

        editor.customConfig.colors = [
            '#FF0000',
            '#FF7D00',
            '#FFFF00',
            '#00FF00',
            '#0000FF',
            '#00FFFF',
            '#FF00FF',
        ];

        //创建编辑器
        editor.create();

        document.getElementById('mySubmit1').addEventListener('click', function ()
        {
            //带html标签的，从中提取img的src
            var html=editor.txt.html();

            //纯文本，直接可以存数据库的
            var text=editor.txt.text();

            var data=
                {
                    _token:$("input[name=_token]").val(),
                    type  :'answerFeedback',
                    html  :html,
                    text  :text,
                    fid   :$("#feedbackId").val(),
                };

            $.post('/admin/user/feedback/ajax',data,function (response) {

                if (response.resCode==200)
                {
                    swal("回复成功", "回复成功", "success");
                }else
                {
                    swal("回复失败", "回复失败", "error");
                }

                //刷新页面
                location.reload();

            },'json');

        }, false);

    </script>

@endsection