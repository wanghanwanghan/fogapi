<?php

namespace App\Http\Controllers\AD;

use Illuminate\Support\Facades\DB;
use QL\QueryList;

class ADSave extends ADBase
{
    public function save_html($res)
    {
        //下面开始create artcle
        foreach ($res as $one)
        {
            $infoid =$one->artid;
            $modelid=$one->modelid;

            //获取文章内容
            $sql="select artid,subject,source,content,pubdate,summary,flag from iiss_wap_article where artid={$infoid} and modelid={$modelid}";

            $tmp=DB::connection('mobile')->select($sql);

            $tmp=current($tmp);

            $artid=$tmp->artid;

            $tmp->artid=$artid.$modelid;

            //删除反斜杠
            $message=sstripslashes($tmp->content);

            //chinaiiss.com  替换为 iissbbs.com
            $message=str_replace('chinaiiss.com','iissbbs.com',$message);

            $tmp->content=null;

            if(preg_match_all('/<p.*>#p#(.*)#e#<\/p>|#p#(.*)#e#/Ui',$message,$match))
            {
                //分割字符串
                $message_arr=preg_split('/<p.*>#p#(.*)#e#<\/p>|#p#(.*)#e#/Ui',$message);

                $index=0;

                foreach(current($match) as $key=>$item)
                {
                    //<p align="left">#p#副标题#e#</p>

                    //主标题？
                    if(strpos($item,'align="center"')>0)
                    {
                        //好像不是主标题，是每一篇文章的意思
                        //一个center是一篇文章？
                        $index=$key+1;
                        break;
                    }
                }

                if($index)
                {
                    //从数组中的第0个开始取出，并返回之后的所有
                    $arrContent=array_slice($message_arr,0,$index);
                }else
                {
                    $arrContent=$message_arr;
                }

            }else
            {
                $arrContent=[$message];
            }

            //一共多少页
            $tmp->number=count($arrContent);

            //用于显示页码
            $tmp->number+=1;

            //array，每页是一个元素
            $tmp->message=$arrContent;

            //$message_arr=$tmp->message;

            //需要生成哪页
            if (self::$is_main_page=='main')
            {
                $message_arr=[$tmp->message[0]];
            }else
            {
                $message_arr=[$tmp->message[self::$is_main_page]];
            }

            $adText=[];

            //图下1
            $adText[]='<script type="text/javascript" smua="d=m&s=b&u=u3743584&h=20:6" src="//www.smucdn.com/smu0/o.js"></script>';
            //图下2
            $adText[]='<script type="text/javascript" smua="d=m&s=b&u=u3743584&h=20:6" src="//www.smucdn.com/smu0/o.js"></script>';

            //重头戏开始，生成页面
            foreach($message_arr as $key=>$message)
            {
                //取出所有p标签
                if(preg_match_all('/<p.*>.*<\/p>/Ui',$message,$match))
                {
                    $tmpcontent = '';
                    $imgnum = 0;

                    //每一个p标签，在图下插广告
                    foreach(current($match) as $k=>$item)
                    {
                        $tmpcontent .= $item;

                        if(strpos($item,'img')>0)
                        {
                            $imgnum++;

                            if($imgnum<=2)
                            {
                                $i=$imgnum-1;
                                $tmpcontent.=$adText[$i];//上面那两条广告
                            }
                        }
                    }
                }

                //链接左右p标签，给message
                $message=$tmpcontent;
                $message=str_replace('　','',$message);

                $pubdate=$tmp->pubdate;
                $artid=$tmp->artid;

                //确定页面url
                $url='http://n4.iissbbs.com/touch_h5/'.date('Ymd', $pubdate).'/'.$artid.'.html';

                $strtosm['url']=$url;

                //当前第几个文章？
                if (self::$is_main_page=='main')
                {
                    $num=0;
                }else
                {
                    $num=self::$is_main_page;
                }

                //$tmp->number似乎是文章有几块儿？
                if($tmp->number==1)
                {
                    $previous=$url;
                    $strtosm['previous']=$previous;

                    $next=$url;
                    $strtosm['next']=$next;

                }else
                {
                    if($num < $tmp->number)
                    {
                        $p=$num-1;
                        $n=$num+1;

                        if ($p<0||$p===0)
                        {
                            $previous=$url;
                        }else
                        {
                            $previous="http://n4.iissbbs.com/touch_h5/".date('Ymd',$pubdate)."/$artid"."_"."$p.html";
                        }

                        $strtosm['previous']=$previous;

                        $next="http://n4.iissbbs.com/touch_h5/".date('Ymd',$pubdate)."/$artid"."_"."$n.html";
                        $strtosm['next']=$next;

                    }else
                    {
                        $p=$num-1;

                        $previous="http://n4.iissbbs.com/touch_h5/".date('Ymd',$pubdate)."/$artid"."_"."$p.html";
                        $strtosm['previous']=$previous;

                        $next=$url;
                        $strtosm['next']=$next;
                    }
                }

                $num++;
                if($num >= 1 && $num <=($tmp->number))
                {
                    $strtosm['num']=$num;

                }else
                {
                    $strtosm['num']=$tmp->number;
                }

                $artlist=(new ADRead())->pattern_four();

                $strtosm['image1url']='http://image1.iissbbs.com';
                $strtosm['artlist']=$artlist;

                $page=[];

                for($i=1;$i<=$tmp->number;$i++)
                {
                    $page[$i] = $i;
                }

                $lasturl = 'http://n4.iissbbs.com/baidu-union.html';

                if(time()>1525793400)
                {
                    $lasturl = 'http://n4.iissbbs.com';
                }

                $strtosm['lasturl']=$lasturl;
                $strtosm['template']='uc';
                $strtosm['pages']=$page;
                $strtosm['messagearr']=$message;
                $strtosm['artarr']=$tmp;
                $strtosm['js_css_ver']='20190220';

                return view('n2_tem_1')->with('data',$strtosm);
            }
        }
    }
}
