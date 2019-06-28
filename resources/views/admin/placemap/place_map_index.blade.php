@extends('admin.layout.index')

@section('content')

    {{csrf_field()}}

    <style>
        .auto { padding:5px 15px; border:0; background:#fff; }
    </style>

    <script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=U3q69k0Dv0GCYNiiZeHPf7BS"></script>
    <script type="text/javascript" src="http://unpkg.com/inmap/dist/inmap.min.js"></script>

    <div class="col-xl-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">格子展示，共有 <span id="pointCount"> 0 </span> 个格子</h6>
                <div class="input-group" style="width: 370px">
                    <input type="text" class="form-control bg-light border-1 small" id="uidInput" placeholder="输入用户主键查单用户，不输入查全部">
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
            },
            draw: {
                interval: 400, //间隔时间
                splitCount: 5000 //每批绘画的数量
            },
            data: data
        });
        inmap.add(overlay);
    </script>

    <script>

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