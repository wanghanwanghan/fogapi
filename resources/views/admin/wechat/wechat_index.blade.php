@extends('admin.layout.index')

@section('content')

    {{csrf_field()}}

    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">说明</h1>
        <p class="mb-3">测试微信支付</p>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-center" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>

                            <button type="button" id="order" class="btn btn-secondary btn-success" style="width: 100px">
                                扫码支付
                            </button>

                        </tr>
                        </thead>
                        <tbody>
                        <tr>

                            <!--二维码,随便放在当前页面的那里都可以,因为是通过ajax控制,请求成功后才会弹出的-->
                            <div class="modal fade" id="qrcode" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-sm" role="document">
                                    <div class="modal-content bg-transparent" style="border:none">
                                        <div class="modal-body align-items-center text-center">
                                            <p class="modal-title" id="exampleModalLabel" style="color:white">微信扫码支付</p>
                                            <br>
                                            {{--生成的二维码会放在这里--}}
                                            <div id="qrcode2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script>

        $('#order').click(function () {

            var url ='/admin/wechat/makeQr';
            var data={
                uid:'make'
            };

            $.get(url,data,function (response) {

                //二维码放到页面上
                $('#qrcode2').html(response.data);

                //弹出二维码
                $('#qrcode').modal('show');

                //设置定时器
                var myTimer=setInterval(function () {

                    var url ='/admin/wechat/listening';
                    var data={
                        uid:'listening'
                    };

                    $.get(url,data,function (response) {

                        if (response.code == 200)
                        {
                            window.clearInterval(myTimer);
                            window.location.reload();
                        }

                    });


                },2000);

            });

        });

    </script>

@endsection