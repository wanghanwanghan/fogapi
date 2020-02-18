@extends('admin.layout.index')

@section('content')

    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary float-left"><span style="color: red">{{$res['target']}}每月的充值金额</span></h6>
                <h6 class="m-0 font-weight-bold text-primary float-right"><span style="color: red">全年金额 {{$res['allMoney']}}</span></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-center" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            <th style='vertical-align: middle'>月份</th>
                            <th style='vertical-align: middle'>ios</th>
                            <th style='vertical-align: middle'>android</th>
                            <th style='vertical-align: middle'>当月金额</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($res['money'] as $key => $val)

                            <tr>
                                <td>{{$key}}</td>
                                <td>{{$val['ios']}}</td>
                                <td>{{$val['android']}}</td>
                                <td>{{$val['android']+$val['ios']}}</td>
                            </tr>

                        @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
