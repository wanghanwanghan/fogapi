@extends('admin.layout.index')

@section('content')

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://cdn.staticfile.org/font-awesome/4.7.0/css/font-awesome.css">

    {{ storage_host_field() }} <!--（可选）需要标识资源服务器host地址的field，用以支持分布式部署-->
    {{ csrf_field() }} <!--需要标识csrf token的field-->

    <div class="container-fluid">

        <div class="row">
            <div class="col-xl-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 text-center" >
                        <i class="fa fa-android fa-3x" aria-hidden="true" style="color: green"></i>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <form method="post" action="/aetherupload">

                                <div class="form-group">
                                    <label>安卓版本号：</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control col-xl-3" name="androidVer" id="androidVer" placeholder="x.x.x">
                                        <div class="input-group-append">
                                            <a class="btn btn-info" href="#">Ver</a>
                                        </div>
                                    </div>

                                    <p></p>
                                    <p>RedisDBN：default</p>
                                    <p>RedisKey：tssjAndroidAppVersion</p>

                                </div>

                                <hr>

                                {{--安卓--}}
                                <div class="form-group" id="aetherupload-wrapper"><!--组件最外部需要一个名为aetherupload-wrapper的id，用以包装组件-->
                                    <label>安卓安装包：</label>
                                    <div class="controls">
                                        <input type="file" id="aetherupload-resource" onchange="aetherupload(this).setGroup('file').setSavedPathField('#aetherupload-savedpath').setPreprocessRoute('/aetherupload/preprocess').setUploadingRoute('/aetherupload/uploading').success(someCallback).upload()"/>
                                        <!--需要一个名为aetherupload-resource的id，用以标识上传的文件，setGroup(...)设置分组名，setSavedPathField(...)设置资源存储路径的保存节点，setPreprocessRoute(...)设置预处理路由，setUploadingRoute(...)设置上传分块路由，success(...)可用于声名上传成功后的回调方法名。默认为选择文件后触发上传，也可根据需求手动更改为特定事件触发，如点击提交表单时-->
                                        <div class="progress" style="height: 6px;margin-bottom: 2px;margin-top: 10px;width: 220px;">
                                            <div id="aetherupload-progressbar" style="background:blue;height:6px;width:0;"></div><!--需要一个名为aetherupload-progressbar的id，用以标识进度条-->
                                        </div>
                                        <span style="font-size:12px;color:#aaa;" id="aetherupload-output"></span><!--需要一个名为aetherupload-output的id，用以标识提示信息-->
                                        <input type="hidden" name="file1" id="aetherupload-savedpath"><!--需要一个自定义名称的id，以及一个自定义名称的name值, 用以标识资源储存路径自动填充位置，默认id为aetherupload-savedpath，可根据setSavedPathField(...)设置为其它任意值-->
                                    </div>
                                </div>
                                {{--<button type="submit" class="btn btn-primary">提交</button>--}}
                                {{--<i class="fa fa-spinner fa-spin fa-3x fa-fw margin-bottom"></i>--}}

                            </form>

                            <hr/>

                            <div id="result"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="{{ URL::asset('vendor/aetherupload/js/spark-md5.min.js') }}"></script><!--（可选）需要引入spark-md5.min.js，用以支持秒传功能-->
    <script src="{{ URL::asset('vendor/aetherupload/js/aetherupload.js') }}"></script><!--需要引入aetherupload.js-->
    <script>
        // success(someCallback)中声名的回调方法需在此定义，参数someCallback可为任意名称，此方法将会在上传完成后被调用
        // 可使用this对象获得resourceName,resourceSize,resourceTempBaseName,resourceExt,groupSubdir,group,savedPath等属性的值
        someCallback = function ()
        {
            var filename=this.savedPath.substr(this.savedPath.lastIndexOf('_') + 1);

            $('#result').append('<p>文件已上传成功，原名：<span>' + this.resourceName + '</span></p>');
            $('#result').append('<p>大小：<span>' + parseFloat(this.resourceSize / (1000 * 1000)).toFixed(2) + 'MB' + '</span></p>');
            $('#result').append('<p>储存后：<span>/aetherupload/display/file_2019_'+ filename +'</span></p>');

            $('#result').append('<p><i id="myWait" class="fa fa-spinner fa-spin fa-1x fa-fw margin-bottom"></i></p>');


            //更新app版本号
            var url ='/admin/app/setup/ajax';

            var data=
                {
                    _token:$("input[name=_token]").val(),
                    type  :'updateVer',
                    androidVer:$("#androidVer").val(),
                    appleVer:'',
                };

            $.post(url,data,function (myResponse) {

                if (myResponse.resCode==200)
                {
                    $('#result').append('<p>版本号更新成功</p>');

                }else
                {
                    $('#result').append('<p>版本号更新<span style="color: red">失败</span></p>');
                }

                $("#myWait").addClass('d-none');

            },'json');
        }

    </script>


@endsection