@extends('admin.layout.index')

@section('content')

    {{csrf_field()}}

    <style>
        .auto { padding:5px 15px; border:0; background:#fff; }
    </style>

    <script type="text/javascript" src="http://api.map.baidu.com/api?v=3.0&ak=0lPULNZ5PmrFVg76kFuRjezF"></script>
    <script type="text/javascript" src="https://unpkg.com/inmap@2.2.8/dist/inmap.min.js"></script>

    <div class="col-xl-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">格子展示，共有 <span id="pointCount"> 0 </span> 个格子</h6>
                <div class="input-group" style="width: 370px">
                    <input type="text" class="form-control bg-light border-1 small" id="uidInput" placeholder="用户主键，格子编号，不输入查全部">
                    <div class="input-group-append">
                        <button class="btn btn-primary" id="searchBtn" onclick="selectData($('#uidInput').val());" type="button">
                            <i class="fas fa-search fa-sm">搜索</i>
                        </button>
                    </div>
                </div>
            </div>
            <div id="allmap" class="card-body" style="height: calc(100vh);"></div>
        </div>
    </div>

    <script>
        var data=[];
        var inmap = new inMap.Map({
            id:"allmap",
            skin: "",
            center: [105.403119, 38.028658],
            zoom: {
                value: 5,
                show: false,
                max: 18,
                min: 5
            }
        });
        var overlay = new inMap.PointOverlay({
            tooltip: {
                show: true,
                formatter: function(params) {
                    return (
                        '<div>' +
                        ' <div>' +
                        ' <span>格子编号：</span><span>' +
                        params.name +
                        "</span>" +
                        " </div>" +
                        ' <div>' +
                        ' <span>格子价格：</span><span>' +
                        params.price +
                        "</span>" +
                        " </div>" +
                        ' <div>' +
                        ' <span>格子交易次数：</span><span>' +
                        params.totle +
                        "</span>" +
                        " </div>" +
                        ' <div>' +
                        ' <span>格子属于：</span><span>' +
                        params.uid +
                        "</span>" +
                        " </div>" +
                        ' <div>' +
                        ' <span>最后交易时间：</span><span>' +
                        params.updated_at +
                        "</span>" +
                        " </div>" +
                        ' <div>' +
                        ' <span>lat：</span><span>' +
                        params.geometry.coordinates[1] +
                        "</span>" +
                        " </div>" +
                        ' <div>' +
                        ' <span></span><span class="series-label">lng：</span><span>' +
                        params.geometry.coordinates[0] +
                        "</span></div></div>"
                    );
                },
                offsets: {
                    top: 15,
                    left: 15
                },
                customClass: "auto"
            },
            style: {
                normal: {
                    backgroundColor: "#ff0000", // 填充颜色
                    //shadowColor: "rgba(255, 255, 255, 1)", // 投影颜色
                    //shadowBlur: 35, // 投影模糊级数
                    //globalCompositeOperation: "lighter", // 颜色叠加方式
                    size: 5 // 半径
                },
                mouseOver: {
                    backgroundColor: "rgba(200, 200, 200, 1)",
                    borderColor: "rgba(255,255,255,1)",
                    borderWidth: 1
                },
                selected: {
                    borderWidth: 1,
                    backgroundColor: "rgba(184,0,0,1)",
                    borderColor: "rgba(255,255,255,1)"
                },
                splitList: [
                    {
                        //区间颜色，由data中的count设置
                        start: 0,
                        end: 10.1,//开区间
                        size:5,
                        backgroundColor: "#4169e1"
                    },
                    {
                        start: 11,
                        end: 100.1,
                        size:5,
                        backgroundColor: "#ffbf00"
                    },
                    {
                        start: 101,
                        end: 1000.1,
                        size:5,
                        backgroundColor: "#228b22"
                    },
                    {
                        start: 1001,
                        end: 5000.1,
                        size:5,
                        backgroundColor: "#8b0000"
                    },
                    {
                        start: 5001,
                        size:5,
                        backgroundColor: "#000000"
                    }
                ],
            },
            //draw: {
            //    interval: 400, //间隔时间
            //    splitCount: 5000 //每批绘画的数量
            //},
            legend: {
                show: true,
                title: "格子价格",
                formatter: function(val,index,item)
                {
                    return val + "元";
                }
            },
            data: data,
            event: {
                onMouseClick: function (item,event)
                {
                    //能获取当前点的信息
                    console.log(item);//把这个发给php
                    console.log(event);
                }
            }
        });
        inmap.add(overlay);
    </script>

    <script>

        $(document).keydown(function (event) {

            if (event.keyCode=='13')
            {
                selectData($('#uidInput').val());
            }

        });

        function selectData(uid) {

            $.ajax({
                url:'/admin/place/map/ajax',
                type:'post',
                cache:false,
                async:true,//true为异步，false为同步
                dataType:'json',
                data:{
                    _token:$("input[name=_token]").val(),
                    type:'get_one_or_all_data',
                    uid:uid,
                },
                success:function(response,textStatus)
                {
                    if (response.resCode==500)
                    {
                        alert('出错了');
                    }

                    if (response.resCode==201)
                    {
                        alert('无数据');
                    }

                    if (response.resCode==202)
                    {
                        alert('格子不存在');
                    }

                    $("#pointCount").html(response.count);
                    overlay.setData(response.data);
                    overlay.refresh();
                },
                error:function(XMLHttpRequest,textStatus,errorThrown)
                {
                    alert('出错了');
                },
                beforeSend:function(XMLHttpRequest)
                {
                    $("#searchBtn").children().remove();
                    $("#searchBtn").append("<i class=\"fa fa-spinner fa-spin fa-fw margin-bottom\"></i>");
                },
                complete:function(XMLHttpRequest,textStatus)
                {
                    $("#searchBtn").children().remove();
                    $("#searchBtn").append("<i class=\"fas fa-search fa-sm\">搜索</i>");
                }
            });
        }

    </script>

@endsection