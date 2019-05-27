var total = 12;
var wapurl= 'http://n4.iissbbs.com';

//点击加载下十条
function clicknextten(categoryid)
{
    var lablearr = $(".hot_nr label");

    lasttime = $($(".hot_nr label")[lablearr.length-1]).html() || 0;

    //wapurl+"/do.php?callback=?", {inajax:1,do:'touch', ac:'n5more',vtype:'touch',lasttime:lasttime,categoryid:categoryid}

    $.getJSON("/n5more", {lasttime:lasttime,categoryid:categoryid}, function(data)
    {
        var html = '';

        var len = data.artlist.length;

        if(data.artlist.length===10)
        {
            for(var i=0;i<len;i++)
            {
                total ++;

                var item = data.artlist[i];

                //添加文章
                $($(".jiazai")).before('<div class="hot_nr"><div class="pic_p1"><a href="'+item.url+'"><img src="'+item.pic+'"></a></div><div class="hots_pic"><a href="'+item.url+'">'+item.subject+'</a><p><b></b>'+item.gmdate+'</p></div><div class="clear"></div><label style="display:none;" class="lbllast">'+item.pubdate+'</label></div>');

                if((i+1)%3===0)
                {
                    // //创建节点
                    var script=document.createElement("script");
                    script.setAttribute('smua','d=m&s=b&u=u3775588&h=200');
                    script.src='//www.smucdn.com/smu0/o.js';

                    //给节点加js
                    var node = document.getElementsByClassName("jiazai")[0];
                    node.parentNode.insertBefore(script,node);
                }
            }
        }

    });
}

function showWin()
{
    $("#bg").height($(document).height());
    $("#bg").show();
    $(".tankuang").show();
    $('.tankuang').offset({top:window.scrollY+$(window).height()/2-100});
}