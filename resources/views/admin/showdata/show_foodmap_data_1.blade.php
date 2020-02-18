@extends('admin.layout.index')

@section('content')

    <script src="{{asset('vendor/layer/layer.js')}}"></script>

    {{csrf_field()}}

    <div class="container-fluid">
        @foreach($tssj as $key => $val)

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary float-left"><span style="color: red">{{$key}}</span></h6>
                    <h6 class="m-0 font-weight-bold text-primary float-right" onclick="redirate('{{$key}}')"><span style="color: red">充值详情</span></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-center" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                            <tr>
                                <th style='vertical-align: middle'>用户设备</th>
                                <th style='vertical-align: middle'>购买项目</th>
                                <th style='vertical-align: middle'>多少人买</th>
                                <th style='vertical-align: middle'>收入金额</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($val as $one)

                                <tr>
                                    <td>{{$one->plant}}</td>
                                    <td>{{$one->subject}}</td>
                                    <td>{{$one->buyPeople}}</td>
                                    <td>{{$one->priceTotal}}</td>
                                </tr>

                            @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        @endforeach
        @foreach($wodelu as $key => $val)

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary float-left"><span style="color: red">{{$key}}</span></h6>
                    <h6 class="m-0 font-weight-bold text-primary float-right" onclick="redirate('{{$key}}')"><span style="color: red">充值详情</span></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-center" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                            <tr>
                                <th style='vertical-align: middle'>用户设备</th>
                                <th style='vertical-align: middle'>购买项目</th>
                                <th style='vertical-align: middle'>多少人买</th>
                                <th style='vertical-align: middle'>收入金额</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($val as $one)

                                <tr>
                                    <td>{{$one->plant}}</td>
                                    <td>{{$one->subject}}</td>
                                    <td>{{$one->buyPeople}}</td>
                                    <td>{{$one->priceTotal}}</td>
                                </tr>

                            @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        @endforeach
    </div>

    <script>

        //跳转充值详情
        function redirate(string) {

            window.location.href='/admin/moneyDetail/'+string;

        }




    </script>

@endsection
