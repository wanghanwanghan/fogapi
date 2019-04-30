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
                                <a href="#first" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2 active">
                                    <i class="mdi mdi-account-circle mr-1"></i>
                                    <span class="d-none d-sm-inline">第一步</span>
                                </a>
                            </li>
                            <li class="nav-item" data-target-form="#profileForm">
                                <a href="#second" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                    <i class="mdi mdi-face-profile mr-1"></i>
                                    <span class="d-none d-sm-inline">第二步</span>
                                </a>
                            </li>
                            <li class="nav-item" data-target-form="#otherForm">
                                <a href="#third" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
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
                                                <label class="col-md-3 col-form-label text-center" for="myContent">通知内容</label>
                                                <div class="col-md-9">
                                                    <textarea class="form-control" id="myContent" name="myContent" rows="5" style="margin-top: 0px; margin-bottom: 0px; height: 150px;" placeholder="输入简单的描述，别输入乱七八糟的字符或符号"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="second">
                                <form id="profileForm" method="post" action="#" class="form-horizontal">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group row mb-3">
                                                <label class="col-md-3 col-form-label text-center">通知类型</label>
                                                <div class="col-md-9">
                                                    <div class="col-12">
                                                        <div class="row">
                                                            <div class="form-group col-2 mt-2">
                                                                <div class="custom-control custom-radio">
                                                                    <input type="radio" id="myType1" name="myType" class="custom-control-input">
                                                                    <label class="custom-control-label" for="myType1">上升</label>
                                                                </div>
                                                            </div>

                                                            <div class="form-group col-2 mt-2">
                                                                <div class="custom-control custom-radio">
                                                                    <input type="radio" id="myType2" name="myType" class="custom-control-input">
                                                                    <label class="custom-control-label" for="myType2">下降</label>
                                                                </div>
                                                            </div>

                                                            <div class="form-group col-2 mt-2">
                                                                <div class="custom-control custom-radio">
                                                                    <input type="radio" id="myType3" name="myType" class="custom-control-input">
                                                                    <label class="custom-control-label" for="myType3">限制</label>
                                                                </div>
                                                            </div>

                                                            <div class="form-group col-3 mt-2">
                                                                <div class="custom-control custom-radio">
                                                                    <input type="radio" id="myType4" name="myType" class="custom-control-input">
                                                                    <label class="custom-control-label" for="myType4">解除限制</label>
                                                                </div>
                                                            </div>

                                                            <div class="form-group col-2 mt-2">
                                                                <div class="custom-control custom-radio">
                                                                    <input type="radio" id="myType5" name="myType" class="custom-control-input">
                                                                    <label class="custom-control-label" for="myType5">其他</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-3">
                                                <label class="col-md-3 col-form-label text-center" for="myNum">数值</label>
                                                <div class="col-md-9">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" name="myNum" id="myNum" placeholder="输入数字，处理的时候被认作百分数">
                                                        <div class="input-group-append">
                                                            <a class="btn btn-info" href="#">%</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group row mb-3">
                                                <label class="col-md-3 col-form-label text-center">影响范围</label>
                                                <div class="col-md-9">
                                                    <div class="col-12">
                                                        <div class="row">
                                                            <div class="form-group col-2 mt-2">
                                                                <div class="custom-control custom-radio">
                                                                    <input onclick="showPlane(1)" type="radio" id="myRange1" name="myRange" class="custom-control-input">
                                                                    <label class="custom-control-label" for="myRange1">全部</label>
                                                                </div>
                                                            </div>

                                                            <div class="form-group col-2 mt-2">
                                                                <div class="custom-control custom-radio">
                                                                    <input onclick="showPlane(2)" type="radio" id="myRange2" name="myRange" class="custom-control-input">
                                                                    <label class="custom-control-label" for="myRange2">部分</label>
                                                                </div>
                                                            </div>

                                                            <div class="form-group col-2 mt-2">
                                                                <div class="custom-control custom-radio">
                                                                    <input onclick="showPlane(3)" type="radio" id="myRange3" name="myRange" class="custom-control-input">
                                                                    <label class="custom-control-label" for="myRange3">个别</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

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
                                                            <input type="text" class="form-control" name="myGridName[]" placeholder="n1w1">
                                                            <div class="input-group-append">
                                                                <a class="btn btn-info" style="width: 100px" href="#" onclick="createOneGrid()">新增</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                </form>
                            </div>

                            <div class="tab-pane fade" id="third">
                                <form id="otherForm" method="post" action="#" class="form-horizontal"></form>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="text-center">
                                            <h2 class="mt-0">
                                                <i class="mdi mdi-check-all"></i>
                                            </h2>
                                            <h3 class="mt-0">再检查检查！</h3>
                                            <p class="w-75 mb-2 mx-auto">头两步检查没问题了？把下面的小方块点成对勾，就可以提交了</p>
                                            <div class="mb-3">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="myAgree">
                                                    <label class="custom-control-label" for="myAgree">纪申已完全知晓并同意发布此通知（甩锅单选框）</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>


                            </div>

                            <ul class="list-inline wizard mb-0" style="margin-top: 20px;">
                                <li class="previous list-inline-item disabled">
                                    {{--<a href="#" class="btn btn-info">Previous</a>--}}
                                </li>
                                <li class="next list-inline-item float-right">
                                    <a href="#" class="btn btn-success btn-icon-split">
                                        <span class="icon text-white-50">
                                            <i class="fas fa-check"></i>
                                        </span>
                                        <span class="text">检查无误，发布通知</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>


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

        //新建一个个别格子
        function createOneGrid() {

            $("#oneGrid").append("<div class=\"form-group row mb-3\"><label class=\"col-md-3 col-form-label text-center\">格子坐标</label><div class=\"col-md-9\"><div class=\"input-group\"><input type=\"text\" class=\"form-control\" name=\"myGridName[]\" placeholder=\"n1w1\"><div class=\"input-group-append\"><a class=\"btn btn-info\" style=\"width: 100px\" href=\"#\" onclick=\"createOneGrid()\">新增</a></div></div></div></div>");



        }

    </script>

@endsection