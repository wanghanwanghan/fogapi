<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\QuanMinZhanLing\Community\CommunityController;
use App\Model\Community\ArticleLabelModel;
use App\Model\Community\ArticleModel;
use App\Model\Community\CommentsModel;
use App\Model\Community\LabelModel;
use App\Model\Community\LikesModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Intervention\Image\Facades\Image;

class AdminCommunityController extends AdminBaseController
{
    public $communityDB='communityDB';
    public $db='communityDB';

    public function ajax(Request $request)
    {
        if (isset($request->mytype) && $request->mytype='modifyAvatar')
        {
            //修改头像
            $uid=trim($request->uid);

            $suffix=$uid%5;

            foreach ($request->all() as $one)
            {
                if ($one instanceof UploadedFile)
                {
                    //获取缓存在tmp目录下的文件名，带后缀，如php8933.tmp
                    $filaName=$one->getFilename();

                    //获取上传的文件缓存在tmp文件夹下的绝对路径
                    $realPath=$one->getRealPath();

                    //存头像
                    Image::make($realPath)->resize(200,200,function($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })->crop(200,200)->save(public_path("img/{$suffix}/{$uid}_avatar.jpg"));

                    Redis::connection('UserInfo')->hset($uid,'avatar',"/img/{$suffix}/{$uid}_avatar.jpg");
                }
            }

            return ['resCode'=>200];
        }

        switch ($request->type)
        {
            case 'pass':

                $aid=$request->aid;

                $suffix=date('Y',substr($aid,0,10));

                ArticleModel::suffix($suffix);

                $res=ArticleModel::where('aid',$aid)->first();

                $res->isShow=1;

                $res->save();

                return ['resCode'=>200];

                break;

            case 'nopass':

                $aid=$request->aid;

                $suffix=date('Y',substr($aid,0,10));

                ArticleModel::suffix($suffix);

                $res=ArticleModel::where('aid',$aid)->first();

                if ($res==null) return ['resCode'=>200];

                //视频有源文件和缩略图
                //图片有源文件和缩略图
                for ($i=1;$i<=9;$i++)
                {
                    $t="picOrVideo{$i}";

                    if ($res->$t==null || $res->$t=='') continue;

                    @unlink(public_path().$res->$t);
                }

                //删标签
                ArticleLabelModel::suffix($suffix);
                ArticleLabelModel::where('aid',$res->aid)->delete();

                //删赞
                LikesModel::suffix($suffix);
                LikesModel::where('aid',$res->aid)->delete();

                //删评论
                CommentsModel::suffix($suffix);
                CommentsModel::where('aid',$res->aid)->delete();

                //最后记录一下这人发了几次黄图了
                $uid=$res->uid;

                //删印象主体
                $res->delete();

                return ['resCode'=>200];

                break;

            case 'communityIndex':

                //排序方法
                //column：0是印象主键，1是用户主键，2是格子编号，3是时间，4是置顶，5是精华，6是内容
                //dir   ：asc是升序，desc是降序
                $order=current($request->order);

                if ($order['column']==0) $cond='aid';
                if ($order['column']==1) $cond='uid';
                if ($order['column']==2) $cond='gName';
                if ($order['column']==3) $cond='unixTime';
                if ($order['column']==4) $cond='isTop';
                if ($order['column']==5) $cond='theBest';
                if ($order['column']==6) $cond='content';

                //搜索
                $search=$request->search;

                //相当于limit
                $length=$request->length;

                //相当于offset
                $start=$request->start;

                ArticleModel::suffix(Carbon::now()->year);

                if ($search['value']!='')
                {
                    //含有搜索条件
                    $res=ArticleModel::where('uid','like',"%{$search['value']}%")
                        ->orWhere('gName','like',"%{$search['value']}%")
                        ->orWhere('content','like',"%{$search['value']}%")
                        ->orderBy($cond,$order['dir'])->limit($length)->offset($start)->get()->toArray();
                }else
                {
                    $res=ArticleModel::orderBy($cond,$order['dir'])->limit($length)->offset($start)->get()->toArray();
                }


                $tmp['draw']=$request->draw;
                $tmp['recordsTotal']=ArticleModel::count();//数据总数
                $tmp['recordsFiltered']=ArticleModel::count();//数据筛选后
                $tmp['data']=[];

                $i=1;
                foreach ($res as $one)
                {
                    $tmp['data'][]=[
                        'aid'=>$one['aid'],
                        'uid'=>$one['uid'],
                        'gName'=>$one['gName'],
                        'created_at'=>$one['created_at'],
                        'isTop'=>$one['isTop']     > 0 ? "<a href='javascript:void(0);' id={$one['aid']} onclick=cancleTop($(this).attr('id')) class='btn btn-success btn-circle btn-sm'><i class='fas fa-check'></i></a>" : "<a href='javascript:void(0);' id={$one['aid']} onclick=setTop($(this).attr('id')) class='btn btn-danger btn-circle btn-sm'><i class='fas fa-times'></i></a>",
                        'theBest'=>$one['theBest'] > 0 ? "<a href='javascript:void(0);' id={$one['aid']} onclick=cancleTheBest($(this).attr('id')) class='btn btn-success btn-circle btn-sm'><i class='fas fa-check'></i></a>" : "<a href='javascript:void(0);' id={$one['aid']} onclick=setTheBest($(this).attr('id')) class='btn btn-danger btn-circle btn-sm'><i class='fas fa-times'></i></a>",
                        'content'=>$one['content'],
                    ];

                    $i++;
                }

                return $tmp;

                break;

            case 'setTop':

                //置顶
                $suffix=date('Y',substr($request->aid,0,10));

                ArticleModel::suffix($suffix);

                $res=ArticleModel::where('aid',$request->aid)->first();

                try
                {
                    $res->isTop=$res->isTop+20;

                    $aid=$res->aid;

                    $res->save();

                }catch (\Exception $e)
                {
                    return ['resCode'=>500];
                }

                return ['resCode'=>200,'aid'=>$aid];

                break;

            case 'cancleTop':

                //取消置顶
                $suffix=date('Y',substr($request->aid,0,10));

                ArticleModel::suffix($suffix);

                $res=ArticleModel::where('aid',$request->aid)->first();

                try
                {
                    $res->isTop=0;

                    $aid=$res->aid;

                    $res->save();

                }catch (\Exception $e)
                {
                    return ['resCode'=>500];
                }

                return ['resCode'=>200,'aid'=>$aid];

                break;

            case 'setTheBest':

                //加精
                $suffix=date('Y',substr($request->aid,0,10));

                ArticleModel::suffix($suffix);

                $res=ArticleModel::where('aid',$request->aid)->first();

                try
                {
                    $res->theBest=$res->theBest+20;

                    $aid=$res->aid;

                    $res->save();

                    ArticleLabelModel::suffix($suffix);

                    $labelArray=array_flatten(current(ArticleLabelModel::where('aid',$res->aid)->get(['labelId'])->toArray()));

                    //标签入redis
                    foreach ($labelArray as $oneLabel)
                    {
                        Redis::connection('HotArticleInfo')->zadd("TheBestArticle_{$oneLabel}",20,$aid);
                    }

                }catch (\Exception $e)
                {
                    return ['resCode'=>500];
                }

                return ['resCode'=>200,'aid'=>$aid];

                break;

            case 'cancleTheBest':

                //取消加精
                $suffix=date('Y',substr($request->aid,0,10));

                ArticleModel::suffix($suffix);

                $res=ArticleModel::where('aid',$request->aid)->first();

                try
                {
                    $res->theBest=0;

                    $aid=$res->aid;

                    $res->save();

                    ArticleLabelModel::suffix($suffix);

                    $labelArray=array_flatten(current(ArticleLabelModel::where('aid',$res->aid)->get(['labelId'])->toArray()));

                    //删除
                    foreach ($labelArray as $oneLabel)
                    {
                        Redis::connection('HotArticleInfo')->zrem("TheBestArticle_{$oneLabel}",$aid);
                    }

                }catch (\Exception $e)
                {
                    return ['resCode'=>500];
                }

                return ['resCode'=>200,'aid'=>$aid];

                break;

            case 'getVideoOrPic':

                $suffix=date('Y',substr($request->aid,0,10));

                ArticleModel::suffix($suffix);

                $res=ArticleModel::where('aid',$request->aid)->first();

                $url[]=$res->picOrVideo1;
                $url[]=$res->picOrVideo2;
                $url[]=$res->picOrVideo3;
                $url[]=$res->picOrVideo4;
                $url[]=$res->picOrVideo5;
                $url[]=$res->picOrVideo6;
                $url[]=$res->picOrVideo7;
                $url[]=$res->picOrVideo8;
                $url[]=$res->picOrVideo9;

                if (empty(array_filter($url))) return ['resCode'=>201,'url'=>$url];

                return ['resCode'=>200,'url'=>$url];

                break;

            case 'createArticle':

                //后台发布印象
                $aid=(new CommunityController)->getArticlePrimary();

                $uid=trim($request->uid);

                if (!is_numeric($uid) || $uid <= 0) return response()->json(['resCode'=>Config::get('resCode.601')]);

                $gName=trim($request->gName);

                if ($gName=='') return response()->json(['resCode'=>Config::get('resCode.605')]);

                $myself=0;

                //标签
                $labels=explode(',',trim($request->vals));

                if (empty($labels) || $labels=='') return response()->json(['resCode'=>Config::get('resCode.665')]);

                sort($labels);

                $includeText=0;
                $includePic=0;
                $includeVideo=0;

                //内容
                $content=trim($request->text);

                if ($content!='') $includeText=1;

                //img src
                $imgSrc=trim($request->html);

                preg_match_all('/(?<=(src="))[^"]*?(?=")/',$imgSrc,$res);

                $res=current($res);

                if (!empty($res)) $includePic=1;

                //全都是空发什么发，发你麻痹
                if ($includeText==0 && $includePic==0 && $includeVideo==0) return response()->json(['resCode'=>Config::get('resCode.664')]);

                $picNum=1;
                $readyToInsertForPicAndVideo=[];
                foreach ($res as $one)
                {
                    //存到public/community/pic/当年/thum/articleID%5/
                    $year=Carbon::now()->year;
                    $suffix=string2Number($aid)%5;

                    //生成文件名，picNum是该印象的第几张图
                    $fileName=$aid.$picNum.'.jpg';

                    //存缩略图的目录
                    $storePathForThum  =public_path("community/pic/{$year}/thum/{$suffix}/");
                    $returnPathForThum="/community/pic/{$year}/thum/{$suffix}/";
                    $storePathForOrigin=public_path("community/pic/{$year}/origin/{$suffix}/");

                    if (!is_dir($storePathForThum)) mkdir($storePathForThum,0777,true);
                    if (!is_dir($storePathForOrigin)) mkdir($storePathForOrigin,0777,true);

                    $one=explode('?',$one);
                    $one=current($one);

                    //thum
                    Image::make(public_path(ltrim($one,'/')))->save($storePathForThum.$fileName,100);

                    //origin
                    Image::make(public_path(ltrim(str_replace('thum','origin',$one),'/')))->save($storePathForOrigin.$fileName,100);

                    $readyToInsertForPicAndVideo["picOrVideo{$picNum}"]=$returnPathForThum.$fileName;

                    $picNum++;
                }

                $readyToInsert=[
                    'aid'=>$aid,
                    'uid'=>$uid,
                    'gName'=>$gName,
                    'content'=>$content,
                    'isShow'=>1,
                    'myself'=>$myself,
                    'includeText'=>$includeText,
                    'includePic'=>$includePic,
                    'includeVideo'=>$includeVideo,
                    'unixTime'=>time(),
                ];

                $readyToInsert=$readyToInsert+$readyToInsertForPicAndVideo;

                try
                {
                    DB::connection($this->db)->beginTransaction();

                    //创建印象
                    ArticleModel::suffix(Carbon::now()->year);
                    ArticleModel::create($readyToInsert);

                    //创建印象和标签的关系
                    $suffix=Carbon::now()->year;

                    $time=time();

                    foreach ($labels as $oneLabels)
                    {
                        $data[]=[
                            'aid'=>$aid,
                            'labelId'=>$oneLabels,
                            'gName'=>$gName,
                            'unixTime'=>$time,
                            'created_at'=>date('Y-m-d H:i:s',$time),
                            'updated_at'=>date('Y-m-d H:i:s',$time),
                        ];
                    }

                    DB::connection($this->db)->table("community_article_label_{$suffix}")->insert($data);

                    //更新标签使用次数
                    $labels=implode(',',$labels);

                    $sql="update community_label set useTotal=useTotal+1 where id in ({$labels})";

                    DB::connection($this->db)->update($sql);

                }catch (\Exception $e)
                {
                    DB::connection($this->db)->rollBack();

                    return response()->json(['resCode'=>Config::get('resCode.660')]);
                }

                DB::connection($this->db)->commit();

                //记录一下这个用户一共发布了几条印象
                Redis::connection('UserInfo')->hincrby($uid,'CommunityArticleTotal',1);

                return response()->json(['resCode'=>Config::get('resCode.200')]);

                break;

            case 'modifyUserName':

                $uid=$request->uid;
                $userName=$request->userName;

                DB::connection('tssj_old')->table('tssj_member')->where('userid',$uid)->update(['username'=>$userName]);

                Redis::connection('UserInfo')->hset($uid,'name',$userName);

                return ['resCode'=>200];

                break;

            case 'modifyAvatar':

                //废止

                break;
        }
    }

    //communityIndex
    public function communityIndex(Request $request)
    {
        //返回今年的印象，按照时间倒序
        return view('admin.community.community_index');
    }

    //设置置顶
    public function setTop(Request $request)
    {

    }

    //设置加精
    public function setTheBest(Request $request)
    {

    }

    //获取未审
    public function checkCommunity(Request $request)
    {
        //取最近两年未审核的印象，以防跨年时，前一年未审核的不显示了
        $currentYear=Carbon::now()->year;
        $lastYear=$currentYear-1;

        if ($currentYear==2019)
        {
            $sql1="select * from community_article_{$currentYear} where isShow=0 order by unixTime limit 10";
            $sql2="select count(*) from community_article_{$currentYear} where isShow=0";

        }else
        {
            $sql1="select * from (select * from community_article_{$currentYear} union select * from community_article_{$lastYear}) as tmp where isShow=0 order by unixTime limit 10";
            $sql2="select count(*) from (select * from community_article_{$currentYear} union select * from community_article_{$lastYear}) as tmp where isShow=0";
        }

        $res1=DB::connection($this->communityDB)->select($sql1);
        $res2=DB::connection($this->communityDB)->select($sql2);
        $res2=current(array_flatten(jsonDecode(jsonEncode($res2))));

        //为空
        if (empty($res1)) return view('admin.community.check_community')->with(['info'=>$res1,'waitToCheck'=>$res2]);

        //不为空
        foreach ($res1 as &$oneArticle)
        {
            //给每一个待审印象补全信息
            if ($oneArticle->includeVideo==1)
            {
                $oneArticle->realVideoPath=$oneArticle->picOrVideo1;
                $oneArticle->picOrVideo1=str_replace('origin','thum',$oneArticle->picOrVideo1);
                $oneArticle->picOrVideo1=str_replace('mp4','jpg',$oneArticle->picOrVideo1);
            }

            $oneArticle->content=substr($oneArticle->content,0,50);
        }
        unset($oneArticle);

        return view('admin.community.check_community')->with(['info'=>$res1,'waitToCheck'=>$res2]);
    }

    //发布印象
    public function publishCommunity(Request $request)
    {
        $arr=[103595,104994,191662,138283,106241,187126,18656,137544,18658,18657,104563,22357];

        //虚拟用户
        $user=DB::connection('tssj_old')->table('tssj_member')->whereIn('userid',$arr)->get(['userid','username','avatar']);

        //官方标签
        $label=LabelModel::where('id','<=',101)->where('labelContent','<>','amyYOEPCiph6NQr')->get(['id','labelContent']);

        return view('admin.community.publishCommunity')->with(['user'=>$user,'label'=>$label]);
    }

    //上传图片
    public function uploadPic(Request $request)
    {
        $url=[];

        $uid=trim($request->uid);

        $num=1;

        foreach ($request->all() as $one)
        {
            if ($one instanceof UploadedFile)
            {
                //获取缓存在tmp目录下的文件名，带后缀，如php8933.tmp
                $filaName=$one->getFilename();

                //获取上传的文件缓存在tmp文件夹下的绝对路径
                $realPath=$one->getRealPath();

                //存origin
                Image::make($realPath)->save(public_path("webAdmin/temp/{$uid}origin{$num}.jpg"),70);

                $picInfo=getimagesize($realPath);
                $width=$height=null;
                $picInfo[0] > $picInfo[1] ? $height=200 : $width=200;

                //存thum
                Image::make($realPath)->resize($width,$height,function($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->crop(200,200)->save(public_path("webAdmin/temp/{$uid}thum{$num}.jpg"));

                $url[]="/webAdmin/temp/{$uid}thum{$num}.jpg?".time();

                $num++;
            }
        }

        return ['errno'=>0,'data'=>$url];
    }






}
