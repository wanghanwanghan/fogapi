@extends('admin.layout.index')

@section('content')

    <div class="container-fluid">

        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">服务器信息</h1>
            {{--<a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> Generate Report</a>--}}
        </div>

        <!-- Content Row -->
        <div class="row">

            <!-- Earnings (Monthly) Card Example -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Cpu使用率</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{!! $info['cpu'] !!}%</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-2x text-gray-300 fa-laptop"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Earnings (Monthly) Card Example -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Mem使用率</div>
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">总共{{ round($info['mem']['totle']/1024/1024,1) }}G 已用{{ round($info['mem']['used']/1024/1024,1) }}G</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ round($info['mem']['used']/$info['mem']['totle'],3) }}%</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-2x text-gray-300 fa-bomb"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Earnings (Monthly) Card Example -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Swap使用率</div>
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">总共{{ round($info['swap']['totle']/1024/1024,1) }}G 已用{{ round($info['swap']['used']/1024/1024,1) }}G</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ round($info['swap']['used']/$info['swap']['totle'],3) }}%</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-2x text-gray-300 fa-retweet"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Requests Card Example -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">disk使用率</div>
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">总共{!! $info['disk']['totle'] !!} 空闲{!! $info['disk']['free'] !!} 已用{!! $info['disk']['used'] !!}</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{!! $info['disk']['percentage'] !!}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-2x text-gray-300 fa-hdd"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            <!-- Area Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <!-- Card Header - Dropdown -->
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">当月访问量PV和独立用户UV展示，<span id="currentAreaShow" style="color: red">当前展示UV</span></h6>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink" x-placement="bottom-end" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(17px, 19px, 0px);">
                                <div class="dropdown-header">操作列表:</div>
                                <a class="dropdown-item" href="#" onclick="showUV();">查看当月UV</a>
                                <a class="dropdown-item" href="#" onclick="showPV();">查看当月PV</a>
                                {{--<div class="dropdown-divider"></div>--}}
                                {{--<a class="dropdown-item" href="#">Something else here</a>--}}
                            </div>
                        </div>
                    </div>
                    <!-- Card Body -->
                    <div class="card-body">
                        <div class="chart-area" id="myAreaChartFather">
                            <div class="chartjs-size-monitor">
                                <div class="chartjs-size-monitor-expand">
                                    <div class="">

                                    </div>
                                </div>
                                <div class="chartjs-size-monitor-shrink">
                                    <div class="">

                                    </div>
                                </div>
                            </div>
                            <canvas id="myAreaChart" style="display: block; height: 320px; width: 584px;" width="1168" height="640" class="chartjs-render-monitor"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pie Chart -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <!-- Card Header - Dropdown -->
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">用户地区分布</h6>
                        <div class="dropdown no-arrow">
                            <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                <div class="dropdown-header">操作列表:</div>
                                <a class="dropdown-item" href="#">没想好</a>
                                <a class="dropdown-item" href="#">还是没想好</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#">更没想好了</a>
                            </div>
                        </div>
                    </div>
                    <!-- Card Body -->
                    <div class="card-body">
                        <div class="chart-pie pt-4 pb-2"><div class="chartjs-size-monitor"><div class="chartjs-size-monitor-expand"><div class=""></div></div><div class="chartjs-size-monitor-shrink"><div class=""></div></div></div>
                            <canvas id="myPieChart" width="518" height="490" class="chartjs-render-monitor" style="display: block; height: 245px; width: 259px;"></canvas>
                        </div>
                        <div class="mt-4 text-center small">
                            <span class="mr-2"><i class="fas fa-circle text-danger"></i> 第一</span>
                            <span class="mr-2"><i class="fas fa-circle text-warning"></i> 第二</span>
                            <span class="mr-2"><i class="fas fa-circle text-success"></i> 第三</span>
                            <span class="mr-2"><i class="fas fa-circle text-info"></i> 第四</span>
                            <span class="mr-2"><i class="fas fa-circle text-primary"></i> 第五</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{csrf_field()}}

    <script src="{{asset('vendor/chart.js/Chart.min.js')}}"></script>
    <script src="{{asset('js/admin/chart-area-demo.js')}}?<?php echo time()?>"></script>
    <script src="{{asset('js/admin/chart-pie-demo.js')}}?<?php echo time()?>"></script>

    <script>

        function showUV() {

            $("#currentAreaShow").html('当前展示UV');

            $("#myAreaChart").remove();

            $("#myAreaChartFather").append("<canvas id=\"myAreaChart\" style=\"display: block; height: 320px; width: 584px;\" width=\"1168\" height=\"640\" class=\"chartjs-render-monitor\"></canvas>");

            get_uv();

        }

        function showPV() {

            $("#currentAreaShow").html('当前展示PV');

            $("#myAreaChart").remove();

            $("#myAreaChartFather").append("<canvas id=\"myAreaChart\" style=\"display: block; height: 320px; width: 584px;\" width=\"1168\" height=\"640\" class=\"chartjs-render-monitor\"></canvas>");

            get_pv();

        }

    </script>

@endsection