@extends('admin.layout.index')

@section('content')

    {{csrf_field()}}

    {{--wangEditor--}}
    <script type="text/javascript" src="//unpkg.com/wangeditor/release/wangEditor.min.js"></script>

    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <a href="#" id="mySubmit1" class="btn btn-success btn-icon-split">
                    <span class="text">查看内容html</span>
                </a>

                <a href="#" id="mySubmit2" class="btn btn-danger btn-icon-split">
                    <span class="text">查看内容纯文本</span>
                </a>
            </div>
            <div class="card-body">

                <div class="col-12">

                    <div class="form-group row mb-2">
                        <label class="col-md-2 col-form-label">测试1</label>
                        <div class="col-md-10">
                            <div class="col-12">
                                <div class="row">
                                    <div class="form-group col-2 mt-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" id="checkbox1" name="checkbox1" class="custom-control-input">
                                            <label class="custom-control-label" for="checkbox1">测试</label>
                                        </div>
                                    </div>

                                    <div class="form-group col-2 mt-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" id="checkbox2" name="checkbox2" class="custom-control-input">
                                            <label class="custom-control-label" for="checkbox2">测试</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row mb-2">
                        <label class="col-md-2 col-form-label">测试3</label>
                        <div class="col-md-10">
                            <div class="col-12">
                                <div class="row">
                                    <div class="form-group col-2 mt-2">
                                        <div class="custom-control custom-radio">
                                            <input onclick="" type="radio" id="radio1" name="radio1" checked class="custom-control-input">
                                            <label class="custom-control-label" for="radio1">测试</label>
                                        </div>
                                    </div>

                                    <div class="form-group col-2 mt-2">
                                        <div class="custom-control custom-radio">
                                            <input onclick="" type="radio" id="radio2" name="radio1" class="custom-control-input">
                                            <label class="custom-control-label" for="radio2">测试</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row mb-2">
                        <label class="col-md-2 col-form-label">测试4</label>
                        <div class="col-md-10">
                            <div class="col-12">
                                <div class="row">
                                    <div class="form-group col-2 mt-2">
                                        <div class="custom-control custom-radio">
                                            <input onclick="" type="radio" id="radio3" name="radio3" class="custom-control-input">
                                            <label class="custom-control-label" for="radio3">测试</label>
                                        </div>
                                    </div>

                                    <div class="form-group col-2 mt-2">
                                        <div class="custom-control custom-radio">
                                            <input onclick="" type="radio" id="radio4" name="radio3" checked class="custom-control-input">
                                            <label class="custom-control-label" for="radio4">测试</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="sidebar-divider">

                    <div class="form-group row mb-2">
                        <label class="col-md-2 col-form-label">测试2</label>
                        <div class="col-md-10">
                            <div class="input-group">
                                <input type="text" class="col-2 form-control" name="" id="" placeholder="测试">
                            </div>
                        </div>
                    </div>

                    <div class="form-group row mb-2">
                        <label class="col-md-2 col-form-label">测试2</label>
                        <div class="col-md-10">
                            <div class="input-group">
                                <select type="text" class="col-2 form-control" name="" id="">
                                    <option class="form-control">1</option>
                                    <option class="form-control">2</option>
                                    <option class="form-control">3</option>
                                </select>
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

        //限制一次最多上传 3  张图片
        editor.customConfig.uploadImgMaxLength = 3;

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
        editor.customConfig.uploadImgServer = '/admin/editor/wangEditor/uploadPic';  // 上传图片到服务器

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
            alert(editor.txt.html())
        }, false);

        document.getElementById('mySubmit2').addEventListener('click', function ()
        {
            alert(editor.txt.text())
        }, false);

    </script>















@endsection