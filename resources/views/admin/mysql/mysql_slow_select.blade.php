@extends('admin.layout.index')

@section('content')

    <style type="text/css">
        #pull_right{
            text-align:center;
        }
        .pull-right {
            /*float: left!important;*/
        }
        .pagination {
            display: inline-block;
            padding-left: 0;
            margin: 0px 0;
            border-radius: 4px;
            font-size: 12px;
        }
        .pagination > li {
            display: inline;
        }
        .pagination > li > a,
        .pagination > li > span {
            position: relative;
            float: left;
            padding: 6px 12px;
            margin-left: -1px;
            line-height: 1.42857143;
            color: #428bca;
            text-decoration: none;
            background-color: #fff;
            border: 1px solid #ddd;
        }
        .pagination > li:first-child > a,
        .pagination > li:first-child > span {
            margin-left: 0;
            border-top-left-radius: 4px;
            border-bottom-left-radius: 4px;
        }
        .pagination > li:last-child > a,
        .pagination > li:last-child > span {
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
        }
        .pagination > li > a:hover,
        .pagination > li > span:hover,
        .pagination > li > a:focus,
        .pagination > li > span:focus {
            color: #2a6496;
            background-color: #eee;
            border-color: #ddd;
        }
        .pagination > .active > a,
        .pagination > .active > span,
        .pagination > .active > a:hover,
        .pagination > .active > span:hover,
        .pagination > .active > a:focus,
        .pagination > .active > span:focus {
            z-index: 2;
            color: #fff;
            cursor: default;
            background-color: #428bca;
            border-color: #428bca;
        }
        .pagination > .disabled > span,
        .pagination > .disabled > span:hover,
        .pagination > .disabled > span:focus,
        .pagination > .disabled > a,
        .pagination > .disabled > a:hover,
        .pagination > .disabled > a:focus {
            color: #777;
            cursor: not-allowed;
            background-color: #fff;
            border-color: #ddd;
        }
        .clear{
            clear: both;
        }
    </style>

    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">MySql慢查询排行榜</h6>
            </div>
            <div class="card-body">

                @foreach($res as $one)

                    <p style="font-size: 12px"><span class="text-xs font-weight-bold text-primary">语句：</span>{{$one->sql}}</p>
                    <p style="font-size: 12px"><span class="text-xs font-weight-bold text-primary">参数：</span>{{$one->bind}}</p>
                    <p style="font-size: 12px"><span class="text-xs font-weight-bold text-primary">耗时：</span>{{$one->execTime}} s</p>
                    <p style="font-size: 12px"><span class="text-xs font-weight-bold text-primary">时间：</span>{{date('Y-m-d H:i:s',$one->time)}}</p>

                    <hr>

                @endforeach

                <div id="pull_right">
                    <div class="pull-right">
                        {!! $res->render() !!}
                    </div>
                </div>

            </div>
        </div>
    </div>



@endsection