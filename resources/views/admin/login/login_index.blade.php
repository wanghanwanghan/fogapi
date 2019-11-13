<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>全民占领admin - 登陆</title>

    <!-- Custom fonts for this template-->
    <link href="{{asset('vendor/fontawesome-free/css/all.min.css')}}" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="{{asset('css/admin/sb-admin-2.min.css')}}?<?php echo time()?>" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>

</head>

<body class="bg-gradient-primary">

{{csrf_field()}}

<div class="container">

    <!-- Outer Row -->
    <div class="row justify-content-center">

        <div class="col-xl-10 col-lg-12 col-md-9">

            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-0">
                    <!-- Nested Row within Card Body -->
                    <div class="row">
                        <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                        <div class="col-lg-6">
                            <div class="p-5">
                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-4">全民占领</h1>
                                </div>
                                <form class="user">

                                    <div class="form-group">
                                        <input type="text" class="form-control form-control-user" id="phoneNum" placeholder="please输入your手机number...">
                                    </div>

                                    <div class="form-group">
                                        <input type="text" class="form-control form-control-user" id="googleCode" placeholder="please输入your手机googleapp上的number...">
                                    </div>

                                    <hr>

                                    <div class="modal-body align-items-center text-center">
                                        <p class="modal-title" id="exampleModalLabel" style="color:black">密钥绑定码：<?php if ($code) echo $code; else echo '不显示'; ?></p>
                                        <div>{!! $qrCode !!}</div>
                                    </div>

                                    <hr>

                                </form>

                                <a href="#" onclick="login()" class="btn btn-primary btn-user btn-block">登陆</a>

                                <hr>

                                <div class="text-center">
                                    <a class="small" href="#">有问题？和纪申联系！</a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

</body>

<script type="text/javascript">

    function login() {

        var url ='/admin/login/ajax';

        var data=
            {
                _token:$("input[name=_token]").val(),
                type  :'login_check',
                phoneNum:$("#phoneNum").val(),
                googleCode:$("#googleCode").val(),
            };

        $.post(url,data,function (response)
        {
            if (response.error==0)
            {
                //验证成功
                location.href='/admin';

            }else
            {
                //验证失败
                alert('验证失败');
            }

        },'json');

    }

    $(document).keydown(function (event) {

        if (event.keyCode=='13')
        {
            login();
        }

    });

</script>

</html>
