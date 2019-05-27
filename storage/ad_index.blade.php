<html>
<head>

	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<meta content="telephone=no" name="format-detection">
	<meta name="keywords" content="军事,军事新闻,军事网,中国军事网,战略网,中国军事力量,国际军情,军事演习,军事网站,军事科技,国际军事,军事天地,历史军事,军事要闻,军事评论" />
	<meta name="description" content="战略网致力于全球政治、经济、军事研究，以增强公众对国际局势的了解。提供最新中国军事新闻、国际军事新闻，跟踪世界军事热点，透析大国军事博弈。" />
	<title>手机战略网--军事-军事新闻-中国最大的战略军事网站</title>

	<script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>
    <script src="{{asset('js/ad_index.js')}}?r=1995"></script>

	<link href="{{asset('css/index1.css')}}" rel="stylesheet" type="text/css">
	<link href="{{asset('css/index2.css')}}" rel="stylesheet" type="text/css">

</head>

<body>

<div id="bg" style="display:none;"></div>
<div class="tankuang" style="display:none;">
	<div class="close"><a href="javascript:void(0);"></a></div>
	<div class="xiazai_btn">
		<a href="http://wap.iissbbs.com/touch/download/index"></a>
	</div>
</div>
{{--<div id="warp">--}}
	{{--<div id="top">--}}
		{{--<div class="down_icon">--}}
			{{--<a onclick="showWin();" href="javascript:void(0);"><img src="{{asset('img/download.png')}}"></a>--}}
		{{--</div>--}}
		{{--<div id="logo"><a href="javascript:void(0);"><img alt="战略网" src="http://static.iissbbs.com/wap/chinaiiss_touch/v1.0/images/logo.png?1025"></a></div>--}}
		{{--<div class="zui"></div>--}}
		{{--<div class="clear"></div>--}}
	{{--</div>--}}
{{--</div>--}}
<div class="jctj">
	<div class="h2_jctj">
		<p><img src="{{asset('img/jingcai.png')}}"></p>
	</div>
	<div class="next_wen">
		<div id="adlist1">


			<div id="wanghan" style="margin-left: 17px;">
				<script type="text/javascript" smua="d=m&s=b&u=u3775588&h=200" src="//www.smucdn.com/smu0/o.js"></script>
			</div>


		</div>
		<div class="list_ad xinxiliu">
			<script type="text/javascript" smua="d=m&s=b&u=u3775588&h=200" src="//www.smucdn.com/smu0/o.js"></script>
		</div>
		<div id="adlist2"></div>
		<div class="list_ad xinxiliu">
			<script type="text/javascript" smua="d=m&s=b&u=u3775588&h=200" src="//www.smucdn.com/smu0/o.js"></script>
		</div>
		<div id="adlist3"></div>
		<div class="list_ad xinxiliu">
			<script type="text/javascript" smua="d=m&s=b&u=u3775588&h=200" src="//www.smucdn.com/smu0/o.js"></script>
		</div>
		<div id="adlist4"></div>
		<div class="list_ad xinxiliu">
			<script type="text/javascript" smua="d=m&s=b&u=u3775588&h=200" src="//www.smucdn.com/smu0/o.js"></script>
		</div>
		<div id="adlist5"></div>
		<div class="clear jiazai"></div>
		<div class="chakan"><a onclick="clicknextten(966)" href="javascript:void(0);">点击加载更多</a></div>
	</div>
</div>

<label style="display:none;">
	<script type="text/javascript" src="https://s23.cnzz.com/z_stat.php?id=1276318977&web_id=1276318977"></script>

	<script type="text/javascript">var cnzz_protocol = (("https:" == document.location.protocol) ? "https://" : "http://");document.write(unescape("%3Cspan id='cnzz_stat_icon_1276323570'%3E%3C/span%3E%3Cscript src='" + cnzz_protocol + "s96.cnzz.com/stat.php%3Fid%3D1276323570' type='text/javascript'%3E%3C/script%3E"));</script>
</label>

</body>





<script type="text/javascript">

    $(function () {

        $(".close").click(function()
		{
            $("#bg").hide();
            $(".tankuang").hide();
        });

		//  wapurl+"/do.php?callback=?", {inajax:1,do:'touch', ac:'zhongzhuan',vtype:'touch'}
        $.getJSON("/zhongzhuan", {}, function(json)
		{
            var html = '';

            for(var i=0;i<3;i++)
            {
                var ad = json.data[i];

                if(ad.flag == 6)
                {
                    var duanran='';

                    duanran +='<div class="big_tu">';
                    duanran +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')"><h2>'+ad.subject+'</h2><img src="'+ad.pic+'">';
                    duanran +='<p><b></b><span>'+ad.gmdate+'</span></p>';
                    duanran +='<div class="clear"></div>';
                    duanran +='</a></div>';

					$("#wanghan").before(duanran);
                }

                if(ad.flag == 7)
                {
                    html +='<div class="pic_p"><h2><a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a></h2>';
                    html +='<ul>';
                    for(var k=0;k<3;k++)
                    {
                        html +='<li><a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="http://image1.iissbbs.com/'+ad.pics[k]+'"></a></li>';
                    }
                    html +='</ul><div class="clear"></div></div>';
                }

                if(ad.flag == 2)
                {
                    html +='<div class="hot_nr">';
                    html +='<div class="pic_p1"><a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="'+ad.pic+'"></a></div>';
                    html +='<div class="hots_pic"><a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a>';
                    html +='<p><b></b><span>'+ad.gmdate+'</span></p></div>';
                    html +='<div class="clear"></div></div>';
                }
            }

            //$("#adlist1").html(html);
            $("#wanghan").after(html);

            html = '';

            for(var i=3;i<6;i++)
            {
                var ad = json.data[i];

                if(ad.flag == 6)
                {
                    html +='<div class="big_tu">';
                    html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')"><h2>'+ad.subject+'</h2><img src="'+ad.pic+'">';
                    html +='<p><b></b><span>'+ad.gmdate+'</span></p>';
                    html +='<div class="clear"></div>';
                    html +='</a></div>';
                }

                if(ad.flag == 7)
                {
                    html +='<div class="pic_p"><h2><a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a></h2>';
                    html +='<ul>';
                    for(var k=0;k<3;k++)
                    {
                        html +='<li><a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="http://image1.iissbbs.com/'+ad.pics[k]+'"></a></li>';
                    }
                    html +='</ul><div class="clear"></div></div>';
                }

                if(ad.flag == 2)
                {
                    html +='<div class="hot_nr">';
                    html +='<div class="pic_p1"><a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="'+ad.pic+'"></a></div>';
                    html +='<div class="hots_pic"><a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a>';
                    html +='<p><b></b><span>'+ad.gmdate+'</span></p></div>';
                    html +='<div class="clear"></div></div>';
                }
            }

            $("#adlist2").html(html);

            html = '';

            for(var i=6;i<9;i++)
            {
                var ad = json.data[i];

                if(ad.flag == 6)
                {
                    html +='<div class="big_tu">';
                    html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')"><h2>'+ad.subject+'</h2><img src="'+ad.pic+'">';
                    html +='<p><b></b><span>'+ad.gmdate+'</span></p>';
                    html +='<div class="clear"></div>';
                    html +='</a></div>';
                }

                if(ad.flag == 7)
                {
                    html +='<div class="pic_p"><h2><a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a></h2>';
                    html +='<ul>';
                    for(var k=0;k<3;k++)
                    {
                        html +='<li><a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="http://image1.iissbbs.com/'+ad.pics[k]+'"></a></li>';
                    }
                    html +='</ul><div class="clear"></div></div>';
                }

                if(ad.flag == 2)
                {
                    html +='<div class="hot_nr">';
                    html +='<div class="pic_p1"><a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="'+ad.pic+'"></a></div>';
                    html +='<div class="hots_pic"><a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a>';
                    html +='<p><b></b><span>'+ad.gmdate+'</span></p></div>';
                    html +='<div class="clear"></div></div>';
                }
            }

            $("#adlist3").html(html);

            html = '';

            for(var i=9;i<13;i++)
            {
                var ad = json.data[i];

                if(ad.flag == 6)
                {
                    html +='<div class="big_tu">';
                    html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')"><h2>'+ad.subject+'</h2><img src="'+ad.pic+'">';
                    html +='<p><b></b><span>'+ad.gmdate+'</span></p>';
                    html +='<div class="clear"></div>';
                    html +='</a></div>';
                }

                if(ad.flag == 7)
                {
                    html +='<div class="pic_p"><h2><a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a></h2>';
                    html +='<ul>';
                    for(var k=0;k<3;k++)
                    {
                        html +='<li><a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="http://image1.iissbbs.com/'+ad.pics[k]+'"></a></li>';
                    }
                    html +='</ul><div class="clear"></div></div>';
                }

                if(ad.flag == 2)
                {
                    html +='<div class="hot_nr">';
                    html +='<div class="pic_p1"><a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="'+ad.pic+'"></a></div>';
                    html +='<div class="hots_pic"><a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a>';
                    html +='<p><b></b><span>'+ad.gmdate+'</span></p></div>';
                    html +='<div class="clear"></div></div>';
                }
            }

            $("#adlist4").html(html);

            html = '';

            for(var i=13;i<14;i++)
            {
                var ad = json.data[i];

                if(ad.flag == 6)
                {
                    html +='<div class="big_tu">';
                    html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')"><h2>'+ad.subject+'</h2><img src="'+ad.pic+'">';
                    html +='<p><b></b><span>'+ad.gmdate+'</span></p>';
                    html +='<div class="clear"></div>';
                    html +='</a></div>';
                }

                if(ad.flag == 7)
                {
                    html +='<div class="pic_p"><h2><a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a></h2>';
                    html +='<ul>';
                    for(var k=0;k<3;k++)
                    {
                        html +='<li><a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="http://image1.iissbbs.com/'+ad.pics[k]+'"></a></li>';
                    }
                    html +='</ul><div class="clear"></div></div>';
                }

                if(ad.flag == 2)
                {
                    html +='<div class="hot_nr">';
                    html +='<div class="pic_p1"><a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="'+ad.pic+'"></a></div>';
                    html +='<div class="hots_pic"><a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a>';
                    html +='<p><b></b><span>'+ad.gmdate+'</span></p></div>';
                    html +='<div class="clear"></div></div>';
                }
            }

            $("#adlist5").html(html);

            html = '';
        });

    });

</script>









</html>
