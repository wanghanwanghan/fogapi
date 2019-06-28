<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <style type="text/css">
        body,
        html {
            width: 100%;
            height: 100%;
            margin: 0;
            font-family: "微软雅黑";
        }

        #allmap {
            width: 100%;
            height: 100%;
        }

        p {
            margin-left: 5px;
            font-size: 14px;
        }
    </style>
    <style>
        .auto {padding:5px 15px; border:0; background:#fff;}
    </style>
    <script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=U3q69k0Dv0GCYNiiZeHPf7BS"></script>
    <script type="text/javascript" src="http://unpkg.com/inmap/dist/inmap.min.js"></script>
    <title></title>
</head>
<body>

<div id="allmap"></div>




<script>
    var inmap = new inMap.Map({
        id: 'allmap',
        skin: "Blueness",
        center: [105.403119, 38.028658],
        zoom: {
            value: 5,
            show: true,
            max: 18,
            min: 5
        },
    });
    let data = [{
        name: '北京',
        geometry: {
            type: 'Point',
            coordinates: ['116.3', '39.9']
        },
        style: {
            color: 'rgba(200, 200, 50, 0.7)',
            speed: 0.5,
        }
    },
        {
            name: '上海',
            geometry: {
                type: 'Point',
                coordinates: ['121.29', '31.11']
            },
            style: {
                color: '#6EE7FF',
                speed: 1,
                size: 40,
            }
        },
        {
            name: '福建',
            geometry: {
                type: 'Point',
                coordinates: ['117.984943', '26.050118']
            },
            style: {
                color: '#90EE90',
                speed: 0.45,
            }
        },
        {
            name: '广东',
            geometry: {
                type: 'Point',
                coordinates: ['113.394818', '23.408004']
            },
            style: {
                color: '#f8983a',
                speed: 0.9,
            }
        },
        {
            name: '广西',
            geometry: {
                type: 'Point',
                coordinates: ['108.924274', '23.552255']
            },
            style: {
                color: '#FAFA32',
                speed: 0.8,
                size: 50,
            }
        }

    ];
    var overlay = new inMap.PointAnimationOverlay({
        style: {
            fps: 90, //动画帧数
            color: "#FAFA32",
            size: 20,
            speed: 0.15
        },
        data: data
    });
    inmap.add(overlay);
</script>






</body>
</html>
