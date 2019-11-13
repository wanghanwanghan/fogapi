@extends('admin.layout.index')

@section('content')

    {{csrf_field()}}

    <link rel="stylesheet" href="{{asset('js/zyupload/zyupload/skins/zyupload-1.0.0.min.css?12')}}" type="text/css">
    <script type="text/javascript" src="{{asset('js/zyupload/zyupload/zyupload.basic-1.0.0.min.js?12')}}"></script>

    {{--wangEditor--}}
    <script type="text/javascript" src="//unpkg.com/wangeditor/release/wangEditor.min.js"></script>

    <link rel="stylesheet" type="text/css" href="{{asset('js/uploadfile/diyUpload/css/webuploader.css')}}">

    <link rel="stylesheet" type="text/css" href="{{asset('js/uploadfile/diyUpload/css/diyUpload.css')}}">

    <script type="text/javascript" src="{{asset('js/uploadfile/diyUpload/js/webuploader.html5only.min.js')}}"></script>

    <script type="text/javascript" src="{{asset('js/uploadfile/diyUpload/js/diyUpload.js')}}"></script>

    <style>
        #demo{border:solid silver 1px; width:300px; min-height:200px; background:white}
    </style>

    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <a href="#" id="myMsg1" style="display: none" class="btn btn-danger btn-icon-split">
                    <span id="myMsg2" class="text">等到图片上传<i class="fa fa-spinner fa-spin fa-fw margin-bottom"></i></span>
                </a>

                <a href="#" id="mySubmit1" class="btn btn-success btn-icon-split">
                    <span class="text">发布印象</span>
                </a>
            </div>
            <div class="card-body">

                <div class="col-12">

                    <div class="form-group row mb-2">
                        <label class="col-md-2 col-form-label">虚拟用户头像</label>
                        <div class="col-md-10">
                            <div class="input-group">
                                <div id="demo">
                                    <div id="as" ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="sidebar-divider">

                    <div class="form-group row mb-2">
                        <label class="col-md-2 col-form-label">虚拟用户</label>
                        <div class="col-md-10">
                            <div class="input-group">
                                <input type="hidden" id="VRuid">
                                <select type="text" class="col-3 form-control" name="VRselect" id="VRselect" ondblclick=$("#userName").removeClass('d-none')>
                                    @foreach($user as $oneUser)
                                        <option class="form-control" value="{{$oneUser->userid}}">{{$oneUser->username}}</option>
                                    @endforeach
                                </select>

                                <input type="text" class="col-3 form-control ml-5 d-none" name="" id="userName" placeholder="修改用户名" ondblclick="modifyUserName($('#VRselect').val(),$(this).val());">
                            </div>
                        </div>
                    </div>

                    <hr class="sidebar-divider">

                    <div class="form-group row mb-2">
                        <label class="col-md-2 col-form-label">系统标签</label>
                        <div class="col-md-10">
                            <div class="col-12">
                                <div class="row">
                                    @foreach($label as $k=>$oneLabel)

                                        @if($k%5===0)

                                            </div>
                                            <div class="row">

                                        @endif

                                        <div class="form-group col-2 mt-2">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" id="checkbox{{$oneLabel->id}}" name="myCheckbox" onclick="getCheckboxValues()" value="{{$oneLabel->id}}" class="custom-control-input">
                                                <label class="custom-control-label" for="checkbox{{$oneLabel->id}}">{{$oneLabel->labelContent}}</label>
                                            </div>
                                        </div>

                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="sidebar-divider">

                    <div class="form-group row mb-2">
                        <label class="col-md-2 col-form-label">格子编号</label>
                        <div class="col-md-10">
                            <div class="input-group" id="changeGridNameInput" ondblclick="changeGridNameInput()">
                                <input type="text" class="col-2 form-control" name="" id="gName" placeholder="">
                            </div>
                        </div>
                    </div>

                    <hr class="sidebar-divider">

                </div>

                <div class="col-12">
                    <div id="div1" style="background-color:#f1f1f1; border:1px solid #ccc;" class="toolbar"></div>
                </div>

                <div class="col-12">
                    <div id="div2" style="border:1px solid #ccc; border-top:none; height:600px; z-index:10000;" class="text"></div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function changeGridNameInput() {

            //取得id
            var id=$("#changeGridNameInput").children().eq(0).attr('id');

            if (id==='gName')
            {
                //转换成下拉列表
                $("#changeGridNameInput").children().remove();
                $("#changeGridNameInput").append("<select type=\"text\" class=\"col-3 form-control\" id=\"selectGridName\"></select>");

                $("#selectGridName").append("<option value=北京>北京</option>");
                $("#selectGridName").append("<option value=上海>上海</option>");
                $("#selectGridName").append("<option value=广州>广州</option>");
                $("#selectGridName").append("<option value=深圳>深圳</option>");
                $("#selectGridName").append("<option value=成都>成都</option>");

                //绑定onchange事件
                $("#selectGridName").on('change',function () {

                    $("#gName").remove();

                    var cond=$("#selectGridName").val();

                    var url='/admin/community/ajax';

                    var data={
                        _token:$("input[name=_token]").val(),
                        type  :'getTargetGridName',
                        cond  : cond,
                    };

                    $.post(url,data,function (res) {

                        if (res.data.length===0)
                        {
                            $("#selectGridName").after("<input type=\"text\" class=\"col-3 form-control ml-5\" id=\"gName\">");

                        }else
                        {
                            $("#selectGridName").after("<select type=\"text\" class=\"col-3 form-control ml-5\" id=\"gName\"></select>");

                            //随机发就选这个
                            $("#gName").append("<option value=''></option>");

                            $.each(res.data,function (k,v) {

                                $("#gName").append("<option value="+k+">"+k+"---"+v+"</option>");

                            });


                        }

                    },'json');




                })

            }else
            {
                //转换成输入框
                $("#changeGridNameInput").children().remove();
                $("#changeGridNameInput").append("<input type=\"text\" class=\"col-2 form-control\" name=\"\" id=\"gName\" placeholder=\"\">");
            }




        }
    </script>

    <script type="text/javascript">

        $('#as').diyUpload({

            url:'/admin/community/ajax',

            success:function(data) {
                console.log(data,'ok');
            },

            error:function(err) {
                console.log(err,'err');
            },

            buttonText : '选择头像图片',

            chunked:false,

            // 分片大小
            chunkSize:512 * 1024,

            //最大上传的文件数量, 总文件大小,单个文件大小(单位字节);
            fileNumLimit:1,

            fileSizeLimit:500000 * 1024,

            fileSingleSizeLimit:50000 * 1024,

            accept: {}

        });

        function getCheckboxValues() {

            var arr = new Array();

            $("input:checkbox[name='myCheckbox']:checked").each(function (i) {
                arr[i] = $(this).val();
            });

            var vals = arr.join(",");

            console.log(vals);

        }

        function modifyUserName(id,name) {
            var uid=$.trim(id);
            var userName=$.trim(name);

            $.ajax({
                url: '/admin/community/ajax',
                type: 'post',
                cache: false,
                async: true,//true为异步，false为同步
                dataType: 'json',
                data: {
                    _token: $("input[name=_token]").val(),
                    type: 'modifyUserName',
                    uid: uid,
                    userName: userName
                },
                success: function (response, textStatus) {
                    if (response.resCode!=200)
                    {
                        alert('修改失败：'+response.resCode);
                    }else
                    {
                        swal("修改成功", "app上已经可以显示了", "success");
                        //刷新页面
                        setTimeout(function()
                        {
                            location.reload();
                        },600);
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert('修改出错');return;
                },
                beforeSend: function (XMLHttpRequest) {
                    $('#myMsg1').css('display','block');
                    $('#myMsg2').html('');
                    $('#myMsg2').append("等待修改用户名");
                    $('#myMsg2').append("<i class=\"fa fa-spinner fa-spin fa-fw margin-bottom\"></i>");
                },
                complete: function (XMLHttpRequest, textStatus) {
                    $("#userName").addClass('d-none');
                    $('#myMsg1').css('display','none');
                },
            });
        }

        function modifyAvatar(id) {
            var uid=$.trim(id);
            var formData = new FormData();

            formData.append("crowd_file",$('#i-file')[0].files[0]);

            $.ajax({
                url: '/admin/community/ajax',
                type: 'post',
                cache: false,
                async: true,//true为异步，false为同步
                dataType: 'json',
                contentType:false,
                processData:false,
                data: {
                    _token: $("input[name=_token]").val(),
                    type: 'modifyAvatar',
                    uid: uid,
                    avatar: formData
                },
                success: function (response, textStatus) {
                    if (response.resCode!=200)
                    {
                        alert('修改失败：'+response.resCode);
                    }else
                    {
                        swal("修改成功", "app上已经可以显示了", "success");
                        //刷新页面
                        setTimeout(function()
                        {
                            location.reload();
                        },600);
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert('修改出错');return;
                },
                beforeSend: function (XMLHttpRequest) {
                    $('#myMsg1').css('display','block');
                    $('#myMsg2').html('');
                    $('#myMsg2').append("等待修改用户名");
                    $('#myMsg2').append("<i class=\"fa fa-spinner fa-spin fa-fw margin-bottom\"></i>");
                },
                complete: function (XMLHttpRequest, textStatus) {
                    $("#userName").addClass('d-none');
                    $('#myMsg1').css('display','none');
                },
            });
        }

        $("#VRselect").change(function() {

            $("#VRuid").val($(this).children('option:selected').val());

            //这个逼
            editor.config.uploadImgParams = {
                token: $("input[name=_token]").val(),
                uid  : $("#VRuid").val() || $("#VRselect").val(),
            };
        });

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

        //将图片大小限制为 20M
        editor.customConfig.uploadImgMaxSize = 20 * 1024 * 1024;

        //限制一次最多上传 9 张图片
        editor.customConfig.uploadImgMaxLength = 9;

        //上传图片时可自定义传递一些参数，例如传递验证的token等。参数会被添加到formdata中。
        editor.customConfig.uploadImgParams = {
            // 如果版本 <=v3.1.0 ，属性值会自动进行 encode ，此处无需 encode
            // 如果版本 >=v3.1.1 ，属性值不会自动 encode ，如有需要自己手动 encode
            token: $("input[name=_token]").val(),
            uid  : $("#VRuid").val() || $("#VRselect").val(),
        };

        //如果还需要将参数拼接到 url 中，可再加上如下配置，true是拼接，false是不拼接
        editor.customConfig.uploadImgParamsWithUrl = false;

        //下面两个配置，使用其中一个即可显示“上传图片”的tab。但是两者不要同时使用！！！
        //editor.customConfig.uploadImgShowBase64 = true;   // 使用 base64 保存图片
        editor.customConfig.uploadImgServer = '/admin/community/publish/community/uploadPic';  // 上传图片到服务器

        //钩子
        editor.customConfig.uploadImgHooks = {
            before: function (xhr, editor, files) {
                // 图片上传之前触发
                // xhr 是 XMLHttpRequst 对象，editor 是编辑器对象，files 是选择的图片文件

                // 如果返回的结果是 {prevent: true, msg: 'xxxx'} 则表示用户放弃上传
                // return {
                //     prevent: true,
                //     msg: '放弃上传'
                // }
                $('#myMsg1').css('display','block');
                $('#myMsg2').html('');
                $('#myMsg2').append("等待图片上传");
                $('#myMsg2').append("<i class=\"fa fa-spinner fa-spin fa-fw margin-bottom\"></i>");

            },
            success: function (xhr, editor, result) {
                // 图片上传并返回结果，图片插入成功之后触发
                // xhr 是 XMLHttpRequst 对象，editor 是编辑器对象，result 是服务器端返回的结果
                $('#myMsg1').css('display','none');
            },
            fail: function (xhr, editor, result) {
                // 图片上传并返回结果，但图片插入错误时触发
                // xhr 是 XMLHttpRequst 对象，editor 是编辑器对象，result 是服务器端返回的结果
                $('#myMsg1').css('display','none');
            },
            error: function (xhr, editor) {
                // 图片上传出错时触发
                // xhr 是 XMLHttpRequst 对象，editor 是编辑器对象
                $('#myMsg1').css('display','none');
            },
            timeout: function (xhr, editor) {
                // 图片上传超时时触发
                // xhr 是 XMLHttpRequst 对象，editor 是编辑器对象
                $('#myMsg1').css('display','none');
            },
        };

        //将 timeout 时间改为 30s
        editor.customConfig.uploadImgTimeout = 30000;

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
            var arr=new Array();

            $("input:checkbox[name='myCheckbox']:checked").each(function (i) {
                arr[i]=$(this).val();
            });

            //取标签
            var vals=arr.join(",");

            //取uid
            var uid=$("#VRselect").val();

            //取img src
            var html=editor.txt.html();

            //取内容
            var text=editor.txt.text();

            //格子编号
            var gName=$("#gName").val();

            //====================================================
            $.ajax({
                url: '/admin/community/ajax',
                type: 'post',
                cache: false,
                async: true,//true为异步，false为同步
                dataType: 'json',
                data: {
                    _token: $("input[name=_token]").val(),
                    type: 'createArticle',
                    uid: uid,
                    vals: vals,
                    html:html,
                    text:text,
                    gName:gName,
                },
                success: function (response, textStatus) {
                    if (response.resCode!=200)
                    {
                        alert('发布失败：'+response.resCode);
                    }else
                    {
                        swal("发布成功", "app上已经可以显示了", "success");
                        //刷新页面
                        setTimeout(function()
                        {
                            location.reload();
                        },600);
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    alert('发布出错');
                },
                beforeSend: function (XMLHttpRequest) {
                    $('#myMsg1').css('display','block');
                    $('#myMsg2').html('');
                    $('#myMsg2').append("等待印象发布");
                    $('#myMsg2').append("<i class=\"fa fa-spinner fa-spin fa-fw margin-bottom\"></i>");
                },
                complete: function (XMLHttpRequest, textStatus) {
                    $('#myMsg1').css('display','none');
                },
            });
            //====================================================










        }, false);

    </script>















@endsection
