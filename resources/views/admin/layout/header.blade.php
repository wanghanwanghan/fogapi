<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">

        <link type="text/css" href="{{asset('js/gundong3/css/jq22.css')}}?<?php echo time() ?>" rel="stylesheet" />
        <script type="text/javascript" src="{{asset('js/gundong3/js/jq22.js')}}?<?php echo time() ?>"></script>

        <div class="gundong3box">
            <div class="t_news">
                <b class="m-0 font-weight-bold" style="color: red">最新信息：</b>
                <ul class="news_li">
                    <li><a href="#" class="text-xs font-weight-bold text-primary text-uppercase">迷雾队列中有{{ (jsonDecode(\Illuminate\Support\Facades\Redis::connection('default')->get('ServerInfo'))['fogUploadNum']) }}个点等待上传</a></li>
                </ul>
                <ul class="swap"></ul>
            </div>
        </div>

        <div class="input-group d-none">
            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..." aria-label="Search" aria-describedby="basic-addon2">
            <div class="input-group-append">
                <button class="btn btn-primary" type="button">
                    <i class="fas fa-search fa-sm"></i>
                </button>
            </div>
        </div>
    </form>

</nav>
