<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">

  <title>后台</title>

  <!-- Custom fonts for this template-->
  <link href="{{asset('vendor/fontawesome-free/css/all.min.css')}}" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

  <!-- Custom styles for this template-->
  <link href="{{asset('css/admin/sb-admin-2.min.css')}}" rel="stylesheet">

  <script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>

  <script src="https://cdn.bootcss.com/sweetalert/2.1.2/sweetalert.min.js"></script>

</head>

<body id="page-top">

  <!-- Page Wrapper -->
  <div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

      <!-- Sidebar - Brand -->
      <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
        <div class="sidebar-brand-icon rotate-n-15">
          <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">管理后台<sup></sup></div>
      </a>

      <!-- Divider -->
      <hr class="sidebar-divider my-0">

      <!-- Nav Item - Dashboard -->
      <li class="nav-item">
        <a class="nav-link" href="{{route('main')}}">
          <i class="fas fa-fw fa-tachometer-alt"></i>
          <span>热点信息</span></a>
      </li>

      <!-- Divider -->
      <hr class="sidebar-divider my-0">

      <!-- Nav Item - Dashboard -->
      <li class="nav-item">
        <a class="nav-link" href="{{route('appSetupIndex')}}">
          <i class="fas fa-fw fa-file"></i>
          <span>上传app安装包</span></a>
      </li>

      <hr class="sidebar-divider my-0">

      <!-- Nav Item - Dashboard -->
      <li class="nav-item">
        <a class="nav-link" href="{{route('placeMap')}}">
          <i class="fas fa-fw fa-map-pin"></i>
          <span>格子位置展示</span></a>
      </li>

      <!-- Divider -->
      <hr class="sidebar-divider">

      <!-- Heading -->
      <div class="sidebar-heading">
        系统公告
      </div>

      <li class="nav-item">
        <a class="nav-link" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
          <i class="fas fa-fw fa-table"></i>
          <span>发布公告</span>
        </a>
        <div id="collapseTwo" class="collapse show" aria-labelledby="headingTwo" data-parent="#accordionSidebar" style="">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header" style="color: red">要发布哪种公告？</h6>
            <a class="collapse-item" href="{{route('sysCreateForUser')}}">公告-对人</a>
            <a class="collapse-item" href="{{route('sysCreateForGrid')}}">公告-对格</a>
          </div>
        </div>
      </li>

      <!-- Divider -->
      <hr class="sidebar-divider">

      <!-- Heading -->
      <div class="sidebar-heading">
        审核自定义
      </div>

      <li class="nav-item">
        <a class="nav-link" href="{{route('gridImg2')}}">
          <i class="fas fa-fw fa-images"></i>
          <span>排行榜格子图片</span></a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{route('picInRedis1')}}">
          <i class="fas fa-fw fa-images"></i>
          <span>排行榜人的图片</span></a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{route('gridImg')}}">
          <i class="fas fa-fw fa-images"></i>
          <span>自定义格子图片</span></a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{route('userAvatar')}}">
          <i class="fas fa-fw fa-images"></i>
          <span>自定义头像图片</span></a>
      </li>

      <!-- Divider -->
      <hr class="sidebar-divider">

      <!-- Heading -->
      <div class="sidebar-heading">
        用户反馈
      </div>

      <li class="nav-item">
        <a class="nav-link" href="{{route('feedback')}}">
          <i class="fas fa-fw fa-comments"></i>
          <span>反馈意见</span></a>
      </li>

      <!-- Divider -->
      <hr class="sidebar-divider">

      <!-- Heading -->
      <div class="sidebar-heading">
        微信支付相关
      </div>

      <li class="nav-item">
        <a class="nav-link" href="{{route('wechatIndex')}}">
          <i class="fas fa-fw fa-handshake"></i>
          <span>测试微信支付</span></a>
      </li>

      <!-- Divider -->
      <hr class="sidebar-divider">

      <!-- Heading -->
      <div class="sidebar-heading">
        Mysql
      </div>

      <li class="nav-item">
        <a class="nav-link" href="{{route('slowSelect')}}">
          <i class="fas fa-fw fa-snowflake"></i>
          <span>慢查询</span></a>
      </li>

      <!-- Divider -->
      <hr class="sidebar-divider">

      <!-- Heading -->
      <div class="sidebar-heading">
        编辑器
      </div>

      <li class="nav-item">
        <a class="nav-link" href="{{route('wangEditor')}}">
          <i class="fas fa-fw fa-file-word"></i>
          <span>wangEditor</span></a>
      </li>

      <!-- Divider -->
      <hr class="sidebar-divider">

      <!-- Sidebar Toggler (Sidebar) -->
      <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
      </div>

    </ul>
    <!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

      <!-- Main Content -->
      <div id="content">

        <!-- header -->
        @include('admin.layout.header')

        @yield('content')

      </div>
      <!-- End of Main Content -->

      <!-- footer -->
      @include('admin.layout.footer')

    </div>
    <!-- End of Content Wrapper -->

  </div>
  <!-- End of Page Wrapper -->

  <!-- 快速返回顶部 body的id设置成page-top-->
  @include('admin.layout.returntop')

  <!-- Bootstrap core JavaScript-->
  <script src="{{asset('vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>

  <!-- Core plugin JavaScript-->
  <script src="{{asset('vendor/jquery-easing/jquery.easing.min.js')}}"></script>

  <!-- Custom scripts for all pages-->
  <script src="{{asset('js/admin/sb-admin-2.min.js')}}"></script>

  <!-- Page level plugins -->
  <script src="{{asset('vendor/chart.js/Chart.min.js')}}"></script>

</body>

</html>
