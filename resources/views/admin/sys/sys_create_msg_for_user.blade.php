@extends('admin.layout.index')

@section('content')

    {{--<script src="{{asset('vendor/layer/layer.js')}}"></script>--}}

    {{csrf_field()}}

    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">说明</h1>
        <p class="mb-3">发布一条新的系统通知.</p>

        <!-- DataTales Example -->
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3"> 创建一条新的通知信息吧</h4>
                    <div id="rootwizard">
                        <ul class="nav nav-pills nav-justified form-wizard-header mb-3">
                            <li class="nav-item" data-target-form="#accountForm">
                                <a id="firstTitle" href="#first" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2 active">
                                    <i class="mdi mdi-account-circle mr-1"></i>
                                    <span class="d-none d-sm-inline">第一步</span>
                                </a>
                            </li>
                            <li class="nav-item" data-target-form="#profileForm">
                                <a id="secondTitle" href="#second" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                    <i class="mdi mdi-face-profile mr-1"></i>
                                    <span class="d-none d-sm-inline">第二步</span>
                                </a>
                            </li>
                            <li class="nav-item" data-target-form="#otherForm">
                                <a id="thirdTitle" href="#third" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                    <i class="mdi mdi-checkbox-marked-circle-outline mr-1"></i>
                                    <span class="d-none d-sm-inline">第三步</span>
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content mb-0 b-0">
                            <div class="tab-pane active" id="first">
                                <form id="accountForm" method="post" action="#" class="form-horizontal">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group row mb-3">
                                                <label class="col-md-3 col-form-label text-center" for="mySubject">标题</label>
                                                <div class="col-md-9">
                                                    <textarea class="form-control" id="mySubject" name="mySubject" rows="2" style="margin-top: 0px; margin-bottom: 0px; height: 60px;" placeholder="输入简单的标题"></textarea>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-3">
                                                <label class="col-md-3 col-form-label text-center" for="myContent">通知内容</label>
                                                <div class="col-md-9">
                                                    <textarea class="form-control" id="myContent" name="myContent" rows="5" style="margin-top: 0px; margin-bottom: 0px; height: 150px;" placeholder="输入简单的描述，别输入乱七八糟的字符或符号"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <ul class="list-inline wizard mb-0" style="margin-top: 20px;">
                                    <li class="previous list-inline-item disabled">
                                        {{--<a href="#" class="btn btn-info">Previous</a>--}}
                                    </li>
                                    <li class="next list-inline-item float-right">
                                        <a href="#" onclick="nextStep(1)" class="btn btn-success btn-icon-split">
                                            <span class="text">下一步</span>
                                            <span class="icon text-white-50">
                                            <i class="fas fa-check"></i>
                                        </span>
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <div class="tab-pane fade" id="second">

                                <div class="row">
                                    <div class="col-12">

                                        <div class="form-group row mb-3">
                                            <label class="col-md-3 col-form-label text-center">通知类型</label>
                                            <div class="col-md-9">
                                                <div class="col-12">
                                                    <div class="row">
                                                        <div class="form-group col-2 mt-2">
                                                            <div class="custom-control custom-radio">
                                                                <input onclick="choseMyType(1);" type="radio" id="myType1" name="myType" checked class="custom-control-input">
                                                                <label class="custom-control-label" for="myType1">加钱</label>
                                                            </div>
                                                        </div>

                                                        <div class="form-group col-2 mt-2">
                                                            <div class="custom-control custom-radio">
                                                                <input onclick="choseMyType(2);" type="radio" id="myType2" name="myType" class="custom-control-input">
                                                                <label class="custom-control-label" for="myType2">送道具</label>
                                                            </div>
                                                        </div>


                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="myNumPlane" class="form-group row mb-3">
                                            <label class="col-md-3 col-form-label text-center" for="myNum">数值</label>
                                            <div class="col-md-9">
                                                <div class="input-group">
                                                    <div class="input-group-append">
                                                        <a class="btn btn-info" href="#">￥</a>
                                                    </div>
                                                    <input type="text" class="form-control" name="myNum" id="myNum" placeholder="输入正整数，别整乱起八糟的">
                                                </div>
                                            </div>
                                        </div>

                                        <div id="myGoodsPlane" class="form-group row mb-3 d-none">
                                            <label class="col-md-3 col-form-label text-center" for="myGoods">道具</label>
                                            <div class="col-md-9">
                                                <div class="input-group">
                                                    <div class="input-group-append">
                                                        <a class="btn btn-info" href="#"><i class="fas fa-fw fa-wrench"></i></a>
                                                    </div>
                                                    <select class="form-control" id="myGoods" name="myGoods">
                                                        <option value="1">小超的帽子</option>
                                                        <option value="2">小超的裤衩</option>
                                                        <option value="3">小超的袜子</option>
                                                    </select>
                                                    <div class="input-group-append">
                                                        <a class="btn btn-info" style="width: 100px" href="#" onclick="createOneGoods()">确定送出</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="sendGoodsPlane" class="form-group row mb-3 d-none">
                                            <label class="col-md-3 col-form-label text-center" for="myGoods">准备送出</label>
                                            <div class="col-md-9">
                                                <div class="input-group">

                                                    <div class="card bg-secondary text-white shadow">
                                                        <div class="card-body">
                                                            小超的帽子
                                                            <div class="text-white-50 small">智商上涨10%</div>
                                                        </div>
                                                    </div>
                                                    <sup>
                                                        <a href="#" class="btn btn-danger btn-circle btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </sup>
                                                    <div style="width: 5%;height: 10px;"></div>
                                                    <div class="card bg-secondary text-white shadow">
                                                        <div class="card-body">
                                                            小超的裤衩
                                                            <div class="text-white-50 small">闷骚上涨10%</div>
                                                        </div>
                                                    </div>
                                                    <sup>
                                                        <a href="#" class="btn btn-danger btn-circle btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </sup>
                                                    <div style="width: 5%;height: 10px;"></div>
                                                    <div class="card bg-secondary text-white shadow">
                                                        <div class="card-body">
                                                            小超的袜子
                                                            <div class="text-white-50 small">封号一名玩家</div>
                                                        </div>
                                                    </div>
                                                    <sup>
                                                        <a href="#" class="btn btn-danger btn-circle btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </sup>

                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row mb-3">
                                            <label class="col-md-3 col-form-label text-center">影响范围</label>
                                            <div class="col-md-9">
                                                <div class="col-12">
                                                    <div class="row">
                                                        <div class="form-group col-2 mt-2">
                                                            <div class="custom-control custom-radio" onclick="choseMyRange(1);">
                                                                <input onclick="showPlane(1)" type="radio" id="myRange1" name="myRange" checked class="custom-control-input">
                                                                <label class="custom-control-label" for="myRange1">全部</label>
                                                            </div>
                                                        </div>



                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row mb-3">
                                            <label class="col-md-3 col-form-label text-center">领取截至日</label>
                                            <div class="col-md-9">
                                                <div class="col-12">
                                                    <div class="row">
                                                        <div class="form-group col-3 mt-2">
                                                            <div class="custom-control custom-radio" onclick="choseMyExecTime(1);">
                                                                <input type="radio" id="myExecTime1" name="myExecTime" class="custom-control-input">
                                                                <label class="custom-control-label" for="myExecTime1">今天</label>
                                                            </div>
                                                        </div>

                                                        <div class="form-group col-3 mt-2">
                                                            <div class="custom-control custom-radio" onclick="choseMyExecTime(2);">
                                                                <input type="radio" id="myExecTime2" name="myExecTime" checked class="custom-control-input">
                                                                <label class="custom-control-label" for="myExecTime2">3天后</label>
                                                            </div>
                                                        </div>

                                                        <div class="form-group col-3 mt-2">
                                                            <div class="custom-control custom-radio" onclick="choseMyExecTime(3);">
                                                                <input type="radio" id="myExecTime3" name="myExecTime" class="custom-control-input">
                                                                <label class="custom-control-label" for="myExecTime3">9天后</label>
                                                            </div>
                                                        </div>

                                                        <div class="form-group col-3 mt-2">
                                                            <div class="custom-control custom-radio" onclick="choseMyExecTime(4);">
                                                                <input type="radio" id="myExecTime4" name="myExecTime" class="custom-control-input">
                                                                <label class="custom-control-label" for="myExecTime4">27天后</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <form id="changeMyObj">

                                            <div id="rangeGrid" style="display: none">
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-3 col-form-label text-center" for="myStart">起始格子</label>
                                                    <div class="col-md-9">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" name="myStart" id="myStart" placeholder="格子坐标：n10w1">
                                                            <div class="input-group-append">
                                                                <a class="btn btn-info" style="width: 100px" href="#">Start</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-3 col-form-label text-center" for="myStop">结束格子</label>
                                                    <div class="col-md-9">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" name="myStop" id="myStop" placeholder="格子坐标：n1w10">
                                                            <div class="input-group-append">
                                                                <a class="btn btn-info" style="width: 100px" href="#">Stop</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div id="oneGrid" style="display: none">
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-3 col-form-label text-center">格子坐标</label>
                                                    <div class="col-md-9">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" name="myGridName" placeholder="n1w1">
                                                            <div class="input-group-append">
                                                                <a class="btn btn-info" style="width: 100px" href="#" onclick="createOneGrid()">新增</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </form>

                                    </div>
                                </div>

                                <ul class="list-inline wizard mb-0" style="margin-top: 20px;">
                                    <li class="previous list-inline-item disabled">
                                        {{--<a href="#" class="btn btn-info">Previous</a>--}}
                                    </li>
                                    <li class="next list-inline-item float-right">
                                        <a href="#" onclick="nextStep(2)" class="btn btn-success btn-icon-split">
                                            <span class="text">下一步</span>
                                            <span class="icon text-white-50">
                                            <i class="fas fa-check"></i>
                                        </span>
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <div class="tab-pane fade" id="third">
                                <form id="otherForm" method="post" action="#" class="form-horizontal">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="text-center">
                                                <h2 class="mt-0">
                                                    <i class="mdi mdi-check-all"></i>
                                                </h2>
                                                <h3 class="mt-0">再检查检查！</h3>
                                                <p class="w-75 mb-2 mx-auto">头两步检查没问题了？把下面的小方块点成对勾，就可以提交了</p>
                                                <div class="mb-3">
                                                    <div class="custom-control custom-checkbox" onclick="$('#myAgree').val(1)">
                                                        <input type="checkbox" class="custom-control-input" id="myAgree1">
                                                        <label class="custom-control-label" for="myAgree1">纪申已完全知晓并同意发布此通知<span style="color: red">（甩锅单选框）</span></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <ul class="list-inline wizard mb-0" style="margin-top: 20px;">
                                    <li class="previous list-inline-item disabled">
                                        {{--<a href="#" class="btn btn-info">Previous</a>--}}
                                    </li>
                                    <li class="next list-inline-item float-right">
                                        <a href="#" onclick="nextStep(3)" class="btn btn-success btn-icon-split">
                                            <span class="text">提交</span>
                                            <span class="icon text-white-50">
                                            <i class="fas fa-check"></i>
                                        </span>
                                        </a>
                                    </li>
                                </ul>


                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    {{--传值区--}}
    <input type="hidden" id="myType" value="1"/>
    <input type="hidden" id="myRange" value="1"/>
    <input type="hidden" id="myAgree" value=""/>
    <input type="hidden" id="myExecTime" value="2"/>

    <script>

        //选择格子范围或个别格子时候切换plane
        function showPlane(num) {

            if (num===1)
            {
                $("#rangeGrid").css('display','none');
                $("#oneGrid").css('display','none');
            }

            if (num===2)
            {
                $("#rangeGrid").css('display','block');
                $("#oneGrid").css('display','none');
            }

            if (num===3)
            {
                $("#rangeGrid").css('display','none');
                $("#oneGrid").css('display','block');
            }
        }

        //确定送出该道具
        function createOneGoods() {

        }

        //下一步
        function nextStep(num) {

            if (num===1)
            {
                $("#firstTitle").removeClass('active');
                $('#secondTitle').addClass('active');
                $("#thirdTitle").removeClass('active');

                $('#first').removeClass('active');
                $('#first').addClass('fade');

                $('#second').removeClass('fade');
                $('#second').addClass('active');

                $("#third").removeClass('active');
                $("#third").addClass('fade');
            }

            if (num===2)
            {
                $("#firstTitle").removeClass('active');
                $('#secondTitle').removeClass('active');
                $("#thirdTitle").addClass('active');

                $('#first').removeClass('active');
                $('#first').addClass('fade');

                $('#second').removeClass('active');
                $('#second').addClass('fade');

                $("#third").removeClass('fade');
                $("#third").addClass('active');
            }

            if (num===3)
            {
                //甩锅
                if ($("#myAgree").val()=='')
                {
                    swal("请把甩锅单选框打勾")
                        .then((value) => {
                        });

                    return;
                }

                var url ='/admin/sys/ajax';

                var data=
                    {
                        _token:$("input[name=_token]").val(),
                        type  :'create_sys_msg_for_user',
                        mySubject:$("#mySubject").val(),//通知标题
                        myContent:$("#myContent").val(),//通知内容
                        myType:$("#myType").val(),//上升，下降，限制，解除限制，其他
                        myNum:$("#myNum").val(),//变化的数值
                        myRange:$("#myRange").val(),//全部，部分，个别
                        myAgree:$("#myAgree").val(),//纪申check
                        myExecTime:$("#myExecTime").val(),
                        changeMyObj:$("#changeMyObj").serializeArray()//要改变的对象
                    };

                $.post(url,data,function (response)
                {
                    if (response.error==0)
                    {
                        //提交
                        swal("天降大锅！纪申背好！")
                            .then((value) => {
                                location.href='/admin/sys/create/user';
                            });
                    }else
                    {
                        alert('出错了');
                    }

                },'json');
            }
        }

        function choseMyType(num) {

            $("#myType").val(num);

            if (num===1)
            {
                $("#myNumPlane").removeClass('d-none');

                $("#myGoodsPlane").addClass('d-none');
                $("#sendGoodsPlane").addClass('d-none');

            }

            if (num===2)
            {
                $("#myGoodsPlane").removeClass('d-none');
                $("#sendGoodsPlane").removeClass('d-none');

                $("#myNumPlane").addClass('d-none');
            }
        }

        function choseMyRange(num) {

            $("#myRange").val(num);

        }

        function choseMyExecTime(num) {

            $("#myExecTime").val(num);

        }

    </script>

@endsection