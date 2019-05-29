@extends('admin.layout.index')

@section('content')

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

                <div id="div1" style="background-color:#f1f1f1; border:1px solid #ccc;" class="toolbar"></div>

                <div id="div2" style="border:1px solid #ccc; border-top:none; height:600px; z-index:10000;" class="text"></div>

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

        //下面两个配置，使用其中一个即可显示“上传图片”的tab。但是两者不要同时使用！！！
        editor.customConfig.uploadImgShowBase64 = true;   // 使用 base64 保存图片
        //editor.customConfig.uploadImgServer = '/admin/uploadPic';  // 上传图片到服务器

        //隐藏“网络图片”tab
        editor.customConfig.showLinkImg = false;

        editor.create();

        document.getElementById('mySubmit1').addEventListener('click', function () {
            // 读取 html
            alert(editor.txt.html())
        }, false);

        document.getElementById('mySubmit2').addEventListener('click', function () {
            // 读取 text
            alert(editor.txt.text())
        }, false);

    </script>















@endsection