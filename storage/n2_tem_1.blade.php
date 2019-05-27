<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
	<meta content="telephone=no" name="format-detection">
	<title>{{$data['artarr']->subject}}</title>
	<meta name="keywords" content="{{isset($data['artarr']->keywords)?$data['artarr']->keywords:''}}" />
	<meta name="description" content="{{ !empty($data['artarr']->summary) ? $data['artarr']->summary : $data['artarr']->subject }}" />
	<link rel="stylesheet" type="text/css" href="http://mipcache.bdstatic.com/static/mipmain-v1.0.1.css?{{$data['js_css_ver']}}">
	<link href="http://static.iissbbs.com/wap/chinaiiss_touch/style/mcss.v3.3.css?{{$data['js_css_ver']}}" rel="stylesheet" type="text/css" />
	<link href="http://static.iissbbs.com/wap/chinaiiss_touch/style/lun.css?{{$data['js_css_ver']}}" rel="stylesheet" type="text/css" />
	<script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js?{{$data['js_css_ver']}}"></script>
	<script type="text/javascript" src="http://static.iissbbs.com/wap/chinaiiss_touch/js/slide.js?{{$data['js_css_ver']}}"></script>
	<script type="text/javascript" src="http://static.iissbbs.com/wap/chinaiiss_touch/js/home.js?{{$data['js_css_ver']}}"></script>
	<link href="{{asset('css/n2_tem_1.css')}}?{{$data['js_css_ver']}}" rel="stylesheet" type="text/css">
	<script type="text/javascript" src="{{asset('js/n2_tem_1_head.js')}}?{{$data['js_css_ver']}}"></script>
</head>
<body>

<div style="display:none;" >n1 h5.uc v2.0 0.1 欧朋百度</div>
<div id="TB_overlay" style="display:none;" class="TB_overlayBG"></div>
<div class="xiazai_p" style="display:none;position: fixed;">
<a href="javascript:close_download();" class="close_right"><img src="http://static.iissbbs.com/wap/chinaiiss_touch/images/close01.png?{{$data['js_css_ver']}}"></a>
<div>

</div>
</div>

	<div style="display:none;" class="fanhui"><a href="#"></a></div>
	<div id="bg" style="display:none;"></div>
	<div class="tankuang" style="display:none;">
		<div class="close"><a href="javascript:void(0);"></a></div>
		<div class="xiazai_btn">
			<a href="http://wap.iissbbs.com/touch/download/index"></a>
		</div>
	</div>
	{{--<div id="wai_head_top" class="header_h">--}}
		{{--<div class="nav_top" id="head_top02">--}}
				{{--<div class="down_icon ">--}}
					{{--<a onclick="showWin();" href="javascript:void(0);"><img src="http://static.iissbbs.com/wap/chinaiiss_touch/images/download.png?{{$data['js_css_ver']}}"></a>--}}
				{{--</div>--}}
				{{--<h2 class="p_dialoghead_tit">--}}
					{{--<a href="javascript:void(0);"><img src="http://static.iissbbs.com/wap/chinaiiss_touch/v1.0/images/text_logo.png?{{$data['js_css_ver']}}" style="vertical-align: middle;border: 0;width:76px;height:44px;"></a>--}}
				{{--</h2>--}}
				{{--<div class="logo_pic"></div>--}}
				{{--<div class="left_fanhui">--}}
				{{--<a href="javascript:history.back();"></a>--}}
				{{--</div>--}}
		{{--</div>--}}
	{{--</div>--}}
<div class="clear"></div>

<div class="list_text_01">

	<script type="text/javascript" smua="d=m&s=b&u=u3775588&h=200" src="//www.smucdn.com/smu0/o.js"></script>

	<div class="list_text_nr">
		<div class="timu_01">
			<div class="Blank"></div>
			<h2>{{$data['artarr']->subject}}</h2>
			<p>{{date('Y-m-d H:i:s',$data['artarr']->pubdate)}}   {{$data['artarr']->source}}</p>
		</div>
		<!-- 标题下三图 -->
		<div class="tuiguang" style="margin-left: -10px;padding-bottom:10px;">
			{{--<script type="text/javascript" src="//baidujs1.iissbbs.com/common/wuevj.js?gb=taktdhc"></script>--}}
			{{--<script type="text/javascript" smua="d=m&s=b&u=u3397032&h=20:6" src="//www.smucdn.com/smu0/o.js"></script>--}}
			{{--<script type="text/javascript">--}}
                {{--var sogou_ad_id=1012216;--}}
                {{--var sogou_ad_content_height=120;--}}
			{{--</script>--}}
			{{--<script type="text/javascript" src="http://img.lu.sogoucdn.com/wap/js/wp.js"></script>--}}

		</div>
		<div class="zhengwen">
			{!! $data['messagearr'] !!}
		</div>
		<div class="clear"></div>
		<div class="guanggao">
			<!-- 分页三图-->
			<script type="text/javascript" smua="d=m&s=b&u=u3743584&h=20:6" src="//www.smucdn.com/smu0/o.js"></script>
		</div>
		<div class="pages" id="pages" style="">
			<div class="pages">
				<a title="上一页" href="{{$data['previous']}}">上一页</a>
				<span id="page_guid" class="c333">
					<code class="green">{{$data['num']}}</code>/{{$data['artarr']->number}}
					<i></i>
				</span>

				@if ($data['artarr']->number != $data['num']+1)
					<a title="下一页" class="green" data-clipboard-action="copy" data-clipboard-target="#foo" href="{{$data['next']}}">下一页</a>
				@else
					<a title="下一页" class="green" data-clipboard-action="copy" data-clipboard-target="#foo" href="{{$data['lasturl']}}">下一页</a>
				@endif

			</div>
			<div id="yema">
				<ul id="page_list" style="display:none;">

					@foreach($data['pages'] as $page)
						<li><a href="http://n4.iissbbs.com/touch/{{$data['template']}}/{{$data['artarr']->artid}}/{{$page}}">第{{$page}}页</a></li>
					@endforeach

				</ul>
			</div>
		</div>
		<div class="guanggao">
			<!-- 分页三图-->
			<script type="text/javascript" smua="d=m&s=b&u=u3743584&h=20:6" src="//www.smucdn.com/smu0/o.js"></script>
		</div>



		<div class="clear"></div>
	</div>

	<div class="hot_new">
		<div class="block_ty">
			<h2>精彩推荐</h2>
			<div class="pic_text">
				<div id="adlist1"></div>
				<div class="hot_nr  xinxiliu" style="margin-bottom: -10px;">
					<script type="text/javascript" smua="d=m&s=b&u=u3775588&h=200" src="//www.smucdn.com/smu0/o.js"></script>
				</div>
				<div id="adlist2"></div>
				<div class="hot_nr  xinxiliu" style="margin-bottom: -10px;">
					<script type="text/javascript" smua="d=m&s=b&u=u3775588&h=200" src="//www.smucdn.com/smu0/o.js"></script>
				</div>
				<div id="adlist3"></div>
				<div class="hot_nr xinxiliu" style="    margin-bottom: -10px;" >
					<div id="containerId"></div>
					<script type="text/javascript" smua="d=m&s=b&u=u3775588&h=200" src="//www.smucdn.com/smu0/o.js"></script>
				</div>
				<div id="adlist4"></div>
				<div class="hot_nr  xinxiliu" style="margin-bottom: -10px;">
					<script type="text/javascript" smua="d=m&s=b&u=u3775588&h=200" src="//www.smucdn.com/smu0/o.js"></script>
				</div>
				<div id="adlist5"></div>
				<div class="hot_nr  xinxiliu" style="margin-bottom: -10px;">
					<script type="text/javascript" smua="d=m&s=b&u=u3775588&h=200" src="//www.smucdn.com/smu0/o.js"></script>
				</div>
				<div id="adlist6"></div>
				<div class="hot_nr  xinxiliu" style="margin: auto;" >
					<script type="text/javascript" smua="d=m&s=b&u=u3775588&h=200" src="//www.smucdn.com/smu0/o.js"></script>
				</div>
			</div>
		</div>
	</div>

	<!--标题下广告2-->
	<div  class="tuiguang2" style="margin-left: -10px;">
		<script type="text/javascript" smua="d=m&s=b&u=u3743584&h=20:6" src="//www.smucdn.com/smu0/o.js"></script>
	</div>

	<!--底部轮播-->
	<div id="banner" style="display: none">
		<div class="top_left">
			<div id="slide_image_w">
				<div style="overflow:hidden;" id="slide_image">
					<ul>
						<li>
							<!-- 12-15 新增广告 底部大图1-->

						</li>
						<li>
							<!-- 12-15 新增广告 底部大图2-->

						</li>

						<li>
							<!-- 12-15 新增广告 底部大图3-->

						</li>
						<li>
							<!-- 12-15 新增广告 底部大图4-->

						</li>
						<li>

							<!-- 12-15 新增广告 底部大图5 -->

						</li>
					</ul>
				</div>
				<div class="image_index">
					<span class="current"></span>
					<span class=""></span>
					<span class=""></span>
					<span class=""></span>
					<span class=""></span>
				</div>
			</div>
		</div>
	</div>

	<style type="text/css">
		/*焦点图*/
		.top_left { margin: 0 auto; }
		#slide_image_w {position: relative;overflow: hidden; }
		#slide_image {}

		#slide_image li { display: table-cell;}

		.image_index span.current { color: #FFFFFF; background-position: left bottom; }

	</style>

	<script type="text/javascript">

        var maxwidth = $(document.body).width();
        //	var maxheight =340;
        var maxheight =200;

        jQuery(".top_left").width(maxwidth);
        jQuery(".top_left").css({'height':maxheight});

        jQuery('#slide_image_w').width(maxwidth);
        jQuery('#slide_image_w').css({'height':maxheight});

        jQuery('#slide_image').width(maxwidth);
        jQuery('#slide_image').css({'height':maxheight});

        jQuery('#slide_image li').width(maxwidth);

	</script>
</div>

<!--底部广告  欧朋百度 用图下1-->
<div style="margin-top:-10px;height: 230px;">
	<script type="text/javascript" smua="d=m&s=b&u=u3743584&h=20:6" src="//www.smucdn.com/smu0/o.js"></script>
</div>

<!--底部悬浮广告-->
<div id="bottom_div" style="position:fixed;bottom: 0px;z-index:999999;text-align: center;width:100%;overflow:hidden;height:110px;">
	<script type="text/javascript" smua="d=m&s=l&u=u3749902&h=20:5" src="//www.smucdn.com/smu0/o.js"></script>
	<div id="adjd0.22147364163056737cls" style="display: block; position: absolute; right: 0px; top: 1px; height: 11px; line-height: 12px; width: 18px; background: rgb(232, 232, 232); font-size: 10px; text-align: center; z-index: 2147483647; cursor: pointer;" onclick="$('#bottom_div').hide();bottom_div_status=1;">X</div>
</div>

<label style="display:none;">
	<script type="text/javascript">var cnzz_protocol = (("https:" == document.location.protocol) ? "https://" : "http://");document.write(unescape("%3Cspan id='cnzz_stat_icon_1276323570'%3E%3C/span%3E%3Cscript src='" + cnzz_protocol + "s96.cnzz.com/stat.php%3Fid%3D1276323570' type='text/javascript'%3E%3C/script%3E"));</script>

	<script type="text/javascript" src="https://s23.cnzz.com/z_stat.php?id=1276318977&web_id=1276318977"></script>
</label>

<script>
	var imageurls= '{{$data['image1url']}}';
	var wapurl= 'http://n4.iissbbs.com';
	var source_dir='http://static.iissbbs.com/wap/chinaiiss_touch';
	var artnumber = '{{$data['artarr']->number}}';
	var artid = '{{$data['artarr']->artid}}'
</script>
<script type="text/javascript">
$("#tuijian_ad").html($('#div_tuijian').html());
$(document).ready(function(){
	$('#number').attr('value',0);
	var maxwidth = $(document.body).width();
	$(".zhengwen img").attr('width',maxwidth);

	$(".close").click(function(){
		$("#bg").hide();
		$(".tankuang").hide();
	});

	$("#page_guid").click(function(){
		$("#page_list").toggle();
	});

	//wapurl+"/do.php?callback=?", {inajax:1,do:'touch', ac:'jingcai',vtype:'touch'}
	$.getJSON("/jingcai", {}, function(json)
	{
		var len = json.data.length;
		var html = '';
		for(var i=0;i<2;i++){
			var ad = json.data[i];

			html +='<div class="hot_nr">';
			html +='<div class="pic_p1">';
			html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="'+ad.pic+'"></a>';
			html +='</div>';
			html +='<div class="hots_pic">';
			html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a>';
			html +='</div>';
			html +='<div class="clear"></div>';
			html +='<p>'+ad.gmdate+'</p>';
			html +='<div class="clear"></div>';
			html +='</div>';
		}
		$("#adlist1").html(html);
		html = '';
		for(var i=2;i<5;i++){
			var ad = json.data[i];
			html +='<div class="hot_nr">';
			html +='<div class="pic_p1">';
			html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="'+ad.pic+'"></a>';
			html +='</div>';
			html +='<div class="hots_pic">';
			html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a>';
			html +='</div>';
			html +='<div class="clear"></div>';
			html +='<p>'+ad.gmdate+'</p>';
			html +='<div class="clear"></div>';
			html +='</div>';
		}
		$("#adlist2").html(html);
		html = '';
		for(var i=5;i<6;i++){
			var ad = json.data[i];
			html +='<div class="hot_nr">';
			html +='<div class="pic_p1">';
			html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="'+ad.pic+'"></a>';
			html +='</div>';
			html +='<div class="hots_pic">';
			html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a>';
			html +='</div>';
			html +='<div class="clear"></div>';
			html +='<p>'+ad.gmdate+'</p>';
			html +='<div class="clear"></div>';
			html +='</div>';
		}
		$("#adlist3").html(html);
		html = '';
		for(var i=6;i<8;i++){
			var ad = json.data[i];
			html +='<div class="hot_nr">';
			html +='<div class="pic_p1">';
			html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="'+ad.pic+'"></a>';
			html +='</div>';
			html +='<div class="hots_pic">';
			html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a>';
			html +='</div>';
			html +='<div class="clear"></div>';
			html +='<p>'+ad.gmdate+'</p>';
			html +='<div class="clear"></div>';
			html +='</div>';
		}
		$("#adlist4").html(html);
		html = '';
		for(var i=8;i<11;i++){
			var ad = json.data[i];
			html +='<div class="hot_nr">';
			html +='<div class="pic_p1">';
			html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="'+ad.pic+'"></a>';
			html +='</div>';
			html +='<div class="hots_pic">';
			html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a>';
			html +='</div>';
			html +='<div class="clear"></div>';
			html +='<p>'+ad.gmdate+'</p>';
			html +='<div class="clear"></div>';
			html +='</div>';
		}
		$("#adlist5").html(html);
		html = '';
		for(var i=11;i<12;i++){
			var ad = json.data[i];
			html +='<div class="hot_nr">';
			html +='<div class="pic_p1">';
			html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')"><img src="'+ad.pic+'"></a>';
			html +='</div>';
			html +='<div class="hots_pic">';
			html +='<a href="'+ad.url+'" onclick="adclick('+ad.id+')">'+ad.subject+'</a>';
			html +='</div>';
			html +='<div class="clear"></div>';
			html +='<p>'+ad.gmdate+'</p>';
			html +='<div class="clear"></div>';
			html +='</div>';
		}
		$("#adlist6").html(html);
	});
});
function adclick(id)
{
	var posturl = 'http://sc.iissbbs.com';
	$.getJSON(posturl+"/do.php?jsoncallback=?", {json:1,inajax:1,do:'ad', ac:'ad_click',parameter:id}, function(json){
		
	});
}
function showWin()
{
	$("#bg").height($(document).height());
	$("#bg").show();
	$(".tankuang").show();
	$('.tankuang').offset({top:window.scrollY+$(window).height()/2-100});
}

//加载合作广告位js代码
function GetRequest() {
	var url = location.search; //获取url中"?"符后的字串
	var theRequest = new Object();
	if (url.indexOf("?") != -1) {
		var str = url.substr(1);
		strs = str.split("&");
		for(var i = 0; i < strs.length; i ++) {
			//theRequest[strs[i].split("=")[0]] = unescape(strs[i].split("=")[1]);
			theRequest[strs[i].split("=")[0]] = strs[i].split("=")[1];
		}
	}
	return theRequest;
}

var param = {
	inajax:1,
	do: 'touch',
	ac: 'get_coopertion_advert',
	vtype: 'touch'
};
var request = GetRequest();
var param = $.extend(param, request);

//$.getJSON(wapurl+"/do.php?callback=?", {inajax:1,do:'touch', ac:'get_coopertion_advert',vtype:'touch', testflag:'test'}, function(json){
//$.getJSON('http://n4.iissbbs.com'+"/do.php?callback=?", {inajax:1,do:'touch', ac:'get_coopertion_advert',vtype:'touch', testflag:'test', area:area}, function(json){
$.getJSON('http://n4.iissbbs.com'+"/do.php?callback=?", param, function(json){

////	<!--标题下广告-->
//	create_script('tuiguang', json.data['biao_ti']['src'], 'class_name');
//
//	create_script('tuiguang2', json.data['biao_ti']['src'], 'class_name');
//
//	//	<!--图下一广告-->
//	create_script('tuxia1', json.data['tu_xia_1']['src'], 'class_name');
//
//	//	<!--图下二广告-->
//	create_script('tuxia2', json.data['tu_xia_2']['src'], 'class_name');
//
////	<!--内容分页下广告-->
//	create_script('guanggao', json.data['sou_suo_tui_jian']['src'], 'class_name');
//
////	<!--信息流广告-->
//	create_script('xinxiliu', json.data['xin_xi_liu']['src'], 'class_name');

});


//创建script节点
function create_script( name, script_url, type ){
	//name：按name查找dom的名称
	//script_url: 脚本url
	//type: name的类型（id_name:按id查找dom，class_name：按class_name查找dom）
	var oDiv = '';
	if( type == 'id_name' ){
		//动态创建script节点
		oDiv=document.getElementById(name);
	}else if( type == 'class_name'){
		//动态创建script节点
		oDiv=document.getElementsByClassName(name);
	}

//	console.log(oDiv);
	_c = function(){
		//动态创建script节点
		var oScript=document.createElement("script");

		oScript.type="text/javascript";

		//	oScript.src="//baiduwapjs1.iissbbs.com/production/site/zhh3yy.js?dyqvn=zxen";
		oScript.src = script_url ;

		return oScript;
	}

	//是数组
	//是数组
	var childs = null;
	var child_num = 0;
	if( typeof oDiv === 'object' && !isNaN(oDiv.length) ){
		var len = oDiv.length;
		for(var i = 0; i < len; i ++){
			childs = oDiv[i].childNodes;

			for( child_num = childs.length - 1; child_num >= 0; child_num--) 			{
				//alert(childs[child_num].nodeName);
				oDiv[i].removeChild(childs[child_num]);
			}

			oDiv[i].appendChild(this._c());
		}

	}else{ //是对象
		childs = oDiv.childNodes;

		for( child_num = childs.length - 1; child_num >= 0; child_num--) {
			//alert(childs[child_num].nodeName);
			oDiv.removeChild(childs[child_num]);
		}


		oDiv.appendChild(this._c());
	}
}


</script>

<!-- hbouc -->
<script type="text/javascript" src="http://stat.hbouc.com/channel02.2019.js"></script>

<script src="https://mipcache.bdstatic.com/static/mipmain-v1.0.2.js"></script>

</body>
</html>
