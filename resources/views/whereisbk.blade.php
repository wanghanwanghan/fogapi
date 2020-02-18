<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no, width=device-width">
    <title>this is very important</title>
    <link rel="stylesheet" href="https://a.amap.com/jsapi_demos/static/demo-center/css/demo-center.css"/>
    <style>
        html,body,#container{
            height:100%;
            width:100%;
        }
        .btn{
            width:10rem;
            margin-left:6.8rem;
        }
        .input-card {
            display: flex;
            flex-direction: column;
            min-width: 0;
            word-wrap: break-word;
            background-color: #fff;
            background-clip: border-box;
            width: 22rem;
            border-width: 0;
            border-radius: 0.4rem;
            box-shadow: 0 2px 6px 0 rgba(114, 124, 245, .5);
            position: fixed;
            bottom: 0rem;
            right: 0rem;
            left: 0rem;
            -ms-flex: 1 1 auto;
            flex: 1 1 auto;
            padding: 0.75rem 1.25rem;
            opacity: 0.85;
        }
        #address {
            font-size: 10px;
        }
        .mark{
            width: 19px;
            height: 31px;
            color: white;
            text-align: center;
            line-height: 21px;
            background: url('https://webapi.amap.com/theme/v1.3/markers/n/mark_b.png');
        }
        .my-flash1{
            padding: .75rem 1.25rem;
            margin-bottom: 1rem;
            border-radius: 50%;
            position: fixed;
            top: 1rem;
            background-color: white;
            opacity: 0.85;
            width: auto;
            /*min-width: 3rem;*/
            border-width: 0;
            right: 1rem;
            box-shadow: 0 2px 6px 0 rgba(114, 124, 245, .5);
        }
        .my-flash2{
            padding: .75rem 1.25rem;
            margin-bottom: 1rem;
            border-radius: 50%;
            position: fixed;
            top: 5rem;
            background-color: white;
            opacity: 0.85;
            width: auto;
            /*min-width: 3rem;*/
            border-width: 0;
            right: 1rem;
            box-shadow: 0 2px 6px 0 rgba(114, 124, 245, .5);
        }
    </style>
    <script src="https://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js"></script>
</head>
<body>
<input type="hidden" id="currentLat" value="{{$info['lat']}}">
<input type="hidden" id="currentLng" value="{{$info['lng']}}">
<input type="hidden" id="currentUpdate_at" value="{{$info['update_at']}}">
<input type="hidden" id="currentTime" value="{{$info['time']}}">
<input type="hidden" id="gongsiLat" value="{{$info['gongsiLat']}}">
<input type="hidden" id="gongsiLng" value="{{$info['gongsiLng']}}">
<div id="container"></div>
<div class='my-flash1' onclick="window.location.reload()">刷新</div>
<div class='my-flash2' onclick="changeUid()">切换</div>
<div class="input-card" style="width: 100%">
{{--    <label labelstyle='color:grey'></label>--}}
    <div class="input-item">
        <div class="input-item-prepend"><span class="input-item-text">最后时间</span></div>
        <input id='update_at' type="text" value='' readonly="readonly">
    </div>
    <div class="input-item">
        <div class="input-item-prepend"><span class="input-item-text">当前时间</span></div>
        <input id='cTime' type="text" value='' readonly="readonly">
    </div>
    <div class="input-item">
        <div class="input-item-prepend"><span class="input-item-text">经纬度</span></div>
        <input id='lnglat' type="text" value='' readonly="readonly">
    </div>
    <div class="input-item">
        <div class="input-item-prepend"><span class="input-item-text" >地址</span></div>
        <input id='address' type="text" readonly="readonly">
    </div>
{{--    <input id="regeo" type="button" class="btn" value="经纬度 -> 地址" >--}}
</div>
<script type="text/javascript" src="https://webapi.amap.com/maps?v=1.4.15&key=686bcccea2d6f038a44a88554628334a&plugin=AMap.Geocoder"></script>
<script type="text/javascript">

    var map=new AMap.Map("container",{
        resizeEnable: true, //是否监控地图容器尺寸变化
        zoom: 14, //初始地图级别
        center: [$("#currentLng").val(),$("#currentLat").val()], //初始地图中心点
        showIndoorMap: false //关闭室内地图
    });

    //实时路况图层
    var trafficLayer = new AMap.TileLayer.Traffic({
        zIndex: 10
    });
    trafficLayer.setMap(map);
    trafficLayer.show();

    var geocoder=new AMap.Geocoder({city:'全国',radius:1000});

    var marker=new AMap.Marker();

    function regeoCode(lat,lng)
    {
        var lnglat=[lng,lat];

        //一个点
        //marker.setPosition(lnglat);
        //map.add(marker);

        //多个点
        var markers=[];
        //bk
        var marker = new AMap.Marker({
            content:'<div class="mark">bk</div>',
            position: lnglat
        });
        markers.push(marker);
        //公司
        var marker = new AMap.Marker({
            content:'<div class="mark">咱</div>',
            position: [$("#gongsiLng").val(),$("#gongsiLat").val()]
        });
        markers.push(marker);
        map.add(markers);

        geocoder.getAddress(lnglat, function(status, result)
        {
            if (status==='complete'&&result.regeocode)
            {
                var address=result.regeocode.formattedAddress;

                $("#address").val(address);
                $("#lnglat").val(lat+','+lng);
                $("#update_at").val($("#currentUpdate_at").val());
                $("#cTime").val($("#currentTime").val());

            }else
            {
                log.error('根据经纬度查询地址失败')
            }
        });
    }

    //触发
    regeoCode($("#currentLat").val(),$("#currentLng").val());

    function changeUid() {

        var currentUrl = window.location.href;

        var baseUrl = currentUrl.lastIndexOf('/');

        baseUrl = currentUrl.slice(0,baseUrl + 1);

        var type = currentUrl.slice(-3);

        if (type === 'ios')
        {
            window.location.href = baseUrl + 'android';
        }else
        {
            window.location.href = baseUrl + 'ios';
        }
    }

</script>
</body>
</html>