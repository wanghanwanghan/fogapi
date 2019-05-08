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

    </div>

@endsection