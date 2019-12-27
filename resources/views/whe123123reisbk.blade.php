<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no, width=device-width">
    <title>批量逆地理编码</title>
    <link rel="stylesheet" href="https://a.amap.com/jsapi_demos/static/demo-center/css/demo-center.css"/>
    <style>
        html,body,#container{
            height:100%;
            width:100%;
        }
        .btn{
            width:10rem;
            margin-left:3.8rem;
        }
        .input-item-text{
            background-color:white;
            padding-left:4px;
            width:12rem;
        }
        .mark{
            width:19px;
            height: 31px;
            color:white;
            text-align: center;
            line-height: 21px;
            background: url('https://webapi.amap.com/theme/v1.3/markers/n/mark_b.png');
        }
    </style>
</head>
<body>
<div id="container"></div>
<div class="input-card" style='width:36rem;'>
    <h4 style='color:grey'>批量逆地理编码</h4>
    <div id="postions">
        <div id='blank_item' class="input-item"><div class="input-item-prepend"><span class="input-item-text">点击地图添加经纬度</span></div><input disabled="" type="text"></div>
    </div>
    <div class="input-item">
        <input id="regeo" type="button" class="btn" value="经纬度 -> 地址" />
        <input id="clear" type="button" class="btn" value="清除" />
    </div>
</div>
<script type="text/javascript" src="https://webapi.amap.com/maps?v=1.4.15&key=您申请的key值&plugin=AMap.Geocoder"></script>
<script type="text/javascript">
    var map = new AMap.Map("container", {
        resizeEnable: true
    });
    var posDiv = document.getElementById('postions');

    var lnglats = [],markers = [];

    map.on('click',function(e){
        if(lnglats.length < 10)
        {
            lnglats.push(e.lnglat);
            var index = lnglats.length;
            var marker = new AMap.Marker({
                content:'<div class="mark">'+lnglats.length+'</div>',
                position: e.lnglat
            });
            markers.push(marker);
            map.add(marker);

            var newItem =
                '<div class="input-item">'+
                '<div class="input-item-prepend"><span class="input-item-text" >'+e.lnglat+'</span></div>'+
                '<input id="address'+index+'" disabled type="text">'+
                '</div>';
            document.getElementById('blank_item').insertAdjacentHTML('beforebegin',newItem)
        }

    });

    var geocoder;
    function regeoCode() {
        if(!geocoder){
            geocoder = new AMap.Geocoder({
                city: "010", //城市设为北京，默认：“全国”
                radius: 1000 //范围，默认：500
            });
        }
        geocoder.getAddress(lnglats, function(status, result) {
            var address = [];
            if (status === 'complete'&&result.regeocodes.length) {
                for(var i=0;i< result.regeocodes.length;i+=1){
                    document.getElementById("address"+(i+1)).value = result.regeocodes[i].formattedAddress
                }

            }else{
                alert(JSON.stringify(result))
            }
        });
    }

    function clear(){
        map.remove(markers);
        markers = [];
        lnglats = [];
        posDiv.innerHTML='<div id="blank_item" class="input-item"><div class="input-item-prepend"><span class="input-item-text">点击地图添加经纬度</span></div><input disabled="" type="text"></div>';
    }

    document.getElementById("regeo").onclick = regeoCode;
    document.getElementById("clear").onclick = clear;
</script>
</body>
</html>