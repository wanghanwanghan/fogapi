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
    </style>
    <script src="https://cdn.bootcss.com/jquery/3.4.0/jquery.min.js"></script>
</head>
<body>
<input type="hidden" id="currentLat" value="{{$info['lat']}}">
<input type="hidden" id="currentLng" value="{{$info['lng']}}">
<input type="hidden" id="currentUpdate_at" value="{{$info['update_at']}}">
<div id="container"></div>
<div class='info'>this is very important。</div>
<div class="input-card" style='width:28rem;'>
    <label labelstyle='color:grey'>   O(∩_∩)O~ (=@__@=) (*^__^*) %>_<% └(^o^)┘; ^ˇ^≡</label>
    <div class="input-item">
        <div class="input-item-prepend"><span class="input-item-text">最后更新</span></div>
        <input id='update_at' type="text" value='' disabled>
    </div>
    <div class="input-item">
        <div class="input-item-prepend"><span class="input-item-text">经纬度</span></div>
        <input id='lnglat' type="text" value='' disabled>
    </div>
    <div class="input-item">
        <div class="input-item-prepend"><span class="input-item-text" >地址</span></div>
        <input id='address' type="text" disabled>
    </div>
{{--    <input id="regeo" type="button" class="btn" value="经纬度 -> 地址" >--}}
</div>
<script src="https://a.amap.com/jsapi_demos/static/demo-center/js/demoutils.js"></script>
<script type="text/javascript" src="https://webapi.amap.com/maps?v=1.4.15&key=686bcccea2d6f038a44a88554628334a&plugin=AMap.Geocoder"></script>
<script type="text/javascript">

    var map=new AMap.Map("container",{resizeEnable:true});

    var geocoder=new AMap.Geocoder({city:'全国',radius:500});

    var marker=new AMap.Marker();

    function regeoCode(lat,lng)
    {
        var lnglat=[lng,lat];

        //一个点
        marker.setPosition(lnglat);
        map.add(marker);

        //多个点
        //var index = lnglats.length;
        //var marker = new AMap.Marker({
        //    content:'<div class="mark">'+lnglats.length+'</div>',
        //    position: e.lnglat
        //});
        //markers.push(marker);
        //map.add(markers);


        geocoder.getAddress(lnglat, function(status, result)
        {
            if (status==='complete'&&result.regeocode)
            {
                var address=result.regeocode.formattedAddress;

                $("#address").val(address);
                $("#lnglat").val(lat+','+lng);
                $("#update_at").val($("#currentUpdate_at").val());

            }else
            {
                log.error('根据经纬度查询地址失败')
            }
        });
    }

    //触发
    regeoCode($("#currentLat").val(),$("#currentLng").val());
</script>
</body>
</html>