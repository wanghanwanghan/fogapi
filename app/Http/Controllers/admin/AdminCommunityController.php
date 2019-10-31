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
use Illuminate\Support\Facades\Schema;
use Intervention\Image\Facades\Image;

class AdminCommunityController extends AdminBaseController
{
    public $communityDB='communityDB';
    public $db='communityDB';

    //虚拟用户uid
    public $uid='103595,104994,191662,138283,106241,187126,18656,137544,18658,18657,104563,22357,137545,97105,31794';
    public $uidArr=[103595,104994,191662,138283,106241,187126,18656,137544,18658,18657,104563,22357,137545,97105,31794];

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

                Redis::connection('UserInfo')->hincrby($uid,'CommunityArticleTotal',-1);

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
                if ($order['column']==3) $cond='created_at';
                if ($order['column']==4) $cond='isTop';
                if ($order['column']==5) $cond='theBest';
                if ($order['column']==6) $cond='likes';
                if ($order['column']==7) $cond='comments';
                if ($order['column']==8) $cond='content';

                //搜索
                $search=$request->search;

                //相当于limit
                $length=$request->length;

                //相当于offset
                $start=$request->start;

                $suffix=Carbon::now()->year;

                ArticleModel::suffix($suffix);

                if ($search['value']!='')
                {
                    $sql=<<<Eof
SELECT
	aid,
	uid,
	gName,
	created_at,
	isTop,
	theBest,
	sum(likes) AS likes,
	sum(comments) AS comments,
	content
FROM
	(
		(
			SELECT
				t1.aid,
				t1.uid,
				t1.gName,
				t1.created_at,
				t1.isTop,
				t1.theBest,
				t1.content,
				CASE
			WHEN t2.isLike IS NULL OR t2.uid IN ({$this->uid}) THEN
				0
			ELSE
				t2.isLike
			END AS likes,
			0 AS comments
		FROM
			community_article_{$suffix} AS t1
		LEFT JOIN community_article_like_{$suffix} AS t2 ON t1.aid = t2.aid
		WHERE
			t1.uid LIKE '%{$search['value']}%'
		OR t1.gName LIKE '%{$search['value']}%'
		OR t1.content LIKE '%{$search['value']}%'
		)
		UNION ALL
			(
				SELECT
					t1.aid,
					t1.uid,
					t1.gName,
					t1.created_at,
					t1.isTop,
					t1.theBest,
					t1.content,
					0 AS likes,
					CASE
				WHEN t3.uid IS NULL OR t3.uid IN ({$this->uid}) THEN
					0
				ELSE
					1
				END AS comments
				FROM
					community_article_{$suffix} AS t1
				LEFT JOIN community_article_comment_{$suffix} AS t3 ON t1.aid = t3.aid
				WHERE
			        t1.uid LIKE '%{$search['value']}%'
		        OR t1.gName LIKE '%{$search['value']}%'
		        OR t1.content LIKE '%{$search['value']}%'
			)
	) AS tmp
GROUP BY
	tmp.aid
ORDER BY
	{$cond} {$order['dir']}
LIMIT {$start},
 {$length}
Eof;
                }else
                {
                    $sql=<<<Eof
SELECT
	aid,
	uid,
	gName,
	created_at,
	isTop,
	theBest,
	sum(likes) AS likes,
	sum(comments) AS comments,
	content
FROM
	(
		(
			SELECT
				t1.aid,
				t1.uid,
				t1.gName,
				t1.created_at,
				t1.isTop,
				t1.theBest,
				t1.content,
				CASE
			WHEN t2.isLike IS NULL OR t2.uid IN ({$this->uid}) THEN
				0
			ELSE
				t2.isLike
			END AS likes,
			0 AS comments
		FROM
			community_article_{$suffix} AS t1
		LEFT JOIN community_article_like_{$suffix} AS t2 ON t1.aid = t2.aid
		)
		UNION ALL
			(
				SELECT
					t1.aid,
					t1.uid,
					t1.gName,
					t1.created_at,
					t1.isTop,
					t1.theBest,
					t1.content,
					0 AS likes,
					CASE
				WHEN t3.uid IS NULL OR t3.uid IN ({$this->uid}) THEN
					0
				ELSE
					1
				END AS comments
				FROM
					community_article_{$suffix} AS t1
				LEFT JOIN community_article_comment_{$suffix} AS t3 ON t1.aid = t3.aid
			)
	) AS tmp
GROUP BY
	tmp.aid
ORDER BY
	{$cond} {$order['dir']}
LIMIT {$start},
 {$length}
Eof;
                }

                $res=DB::connection($this->db)->select($sql);

                $tmp['draw']=$request->draw;
                $tmp['recordsTotal']=ArticleModel::count();//数据总数
                $tmp['recordsFiltered']=ArticleModel::count();//数据筛选后
                $tmp['data']=[];

                $i=1;
                foreach ($res as $one)
                {
                    $one=jsonDecode(jsonEncode($one));

                    $tmp['data'][]=[
                        'aid'=>$one['aid'],
                        'uid'=>$one['uid'],
                        'gName'=>$one['gName'],
                        'created_at'=>$one['created_at'],
                        'isTop'=>$one['isTop']     > 0 ? "<a href='javascript:void(0);' id={$one['aid']} onclick=cancleTop($(this).attr('id')) class='btn btn-success btn-circle btn-sm'><i class='fas fa-check'></i></a>" : "<a href='javascript:void(0);' id={$one['aid']} onclick=setTop($(this).attr('id')) class='btn btn-danger btn-circle btn-sm'><i class='fas fa-times'></i></a>",
                        'theBest'=>$one['theBest'] > 0 ? "<a href='javascript:void(0);' id={$one['aid']} onclick=cancleTheBest($(this).attr('id')) class='btn btn-success btn-circle btn-sm'><i class='fas fa-check'></i></a>" : "<a href='javascript:void(0);' id={$one['aid']} onclick=setTheBest($(this).attr('id')) class='btn btn-danger btn-circle btn-sm'><i class='fas fa-times'></i></a>",
                        'likes'=>$one['likes'],
                        'comments'=>$one['comments'],
                        'content'=>$one['content'],
                        'useForDelete'=>"<a href='javascript:void(0);' id={$one['aid']} onclick=deleteThisArticle($(this).attr('id')) class='btn btn-warning btn-circle btn-sm'><i class='fas fa-trash'></i></a>",
                    ];

                    $i++;
                }

                return $tmp;

                break;

            case 'communityIndexLikesComments':

                $uid=$request->uid;

                //排序方法
                //column：0是印象主键，1是用户主键，2是格子编号，3是时间，4是点赞，5是评论，5是内容
                //dir   ：asc是升序，desc是降序
                $order=current($request->order);

                if ($order['column']==0) $cond='aid';
                if ($order['column']==1) $cond='uid';
                if ($order['column']==2) $cond='gName';
                if ($order['column']==3) $cond='created_at';
                if ($order['column']==4) $cond='setLike';
                if ($order['column']==5) $cond='setComment';
                if ($order['column']==6) $cond='likes';
                if ($order['column']==7) $cond='comments';
                if ($order['column']==8) $cond='content';

                //搜索
                $search=$request->search;

                //相当于limit
                $length=$request->length;

                //相当于offset
                $start=$request->start;

                $suffix=Carbon::now()->year;

                ArticleModel::suffix($suffix);

                if ($search['value']!='')
                {
                    $sql=<<<Eof
SELECT
	aid,
	uid,
	gName,
	created_at,
	isTop,
	theBest,
	sum(likes) AS likes,
	sum(comments) AS comments,
	content
FROM
	(
		(
			SELECT
				t1.aid,
				t1.uid,
				t1.gName,
				t1.created_at,
				t1.isTop,
				t1.theBest,
				t1.content,
				CASE
			WHEN t2.isLike IS NULL OR t2.uid IN ({$this->uid}) THEN
				0
			ELSE
				t2.isLike
			END AS likes,
			0 AS comments
		FROM
			community_article_{$suffix} AS t1
		LEFT JOIN community_article_like_{$suffix} AS t2 ON t1.aid = t2.aid
		WHERE
			t1.uid LIKE '%{$search['value']}%'
		OR t1.gName LIKE '%{$search['value']}%'
		OR t1.content LIKE '%{$search['value']}%'
		)
		UNION ALL
			(
				SELECT
					t1.aid,
					t1.uid,
					t1.gName,
					t1.created_at,
					t1.isTop,
					t1.theBest,
					t1.content,
					0 AS likes,
					CASE
				WHEN t3.uid IS NULL OR t3.uid IN ({$this->uid}) THEN
					0
				ELSE
					1
				END AS comments
				FROM
					community_article_{$suffix} AS t1
				LEFT JOIN community_article_comment_{$suffix} AS t3 ON t1.aid = t3.aid
				WHERE
			        t1.uid LIKE '%{$search['value']}%'
		        OR t1.gName LIKE '%{$search['value']}%'
		        OR t1.content LIKE '%{$search['value']}%'
			)
	) AS tmp
GROUP BY
	tmp.aid
ORDER BY
	{$cond} {$order['dir']}
LIMIT {$start},
 {$length}
Eof;
                }else
                {
                    $sql=<<<Eof
SELECT
	aid,
	uid,
	gName,
	created_at,
	isTop,
	theBest,
	sum(likes) AS likes,
	sum(comments) AS comments,
	content
FROM
	(
		(
			SELECT
				t1.aid,
				t1.uid,
				t1.gName,
				t1.created_at,
				t1.isTop,
				t1.theBest,
				t1.content,
				CASE
			WHEN t2.isLike IS NULL OR t2.uid IN ({$this->uid}) THEN
				0
			ELSE
				t2.isLike
			END AS likes,
			0 AS comments
		FROM
			community_article_{$suffix} AS t1
		LEFT JOIN community_article_like_{$suffix} AS t2 ON t1.aid = t2.aid
		)
		UNION ALL
			(
				SELECT
					t1.aid,
					t1.uid,
					t1.gName,
					t1.created_at,
					t1.isTop,
					t1.theBest,
					t1.content,
					0 AS likes,
					CASE
				WHEN t3.uid IS NULL OR t3.uid IN ({$this->uid}) THEN
					0
				ELSE
					1
				END AS comments
				FROM
					community_article_{$suffix} AS t1
				LEFT JOIN community_article_comment_{$suffix} AS t3 ON t1.aid = t3.aid
			)
	) AS tmp
GROUP BY
	tmp.aid
ORDER BY
	{$cond} {$order['dir']}
LIMIT {$start},
 {$length}
Eof;
                }

                $res=DB::connection($this->db)->select($sql);

                $tmp['draw']=$request->draw;
                $tmp['recordsTotal']=ArticleModel::count();//数据总数
                $tmp['recordsFiltered']=ArticleModel::count();//数据筛选后
                $tmp['data']=[];

                $i=1;
                foreach ($res as $one)
                {
                    $one=jsonDecode(jsonEncode($one));

                    //根据传进来的uid，看看这个虚拟用户点没点赞
                    $iLike=0;
                    if ($uid>0)
                    {
                        LikesModel::suffix(date('Y',substr($one['aid'],0,10)));

                        $iLike=LikesModel::where(['aid'=>$one['aid'],'uid'=>$uid])->first();

                        if ($iLike && $iLike->isLike) $iLike=1;
                    }

                    $tmp['data'][]=[
                        'aid'=>$one['aid'],
                        'uid'=>$one['uid'],
                        'gName'=>$one['gName'],
                        'created_at'=>$one['created_at'],
                        'setLike'=>$iLike === 1 ? "<a href='javascript:void(0);' id={$one['aid']} onclick=setLike($(this).attr('id'))><img width='35px' height='35px' src='/img/admin/like.png'></a>" : "<a href='javascript:void(0);' id={$one['aid']} onclick=setLike($(this).attr('id'))><img width='35px' height='35px' src='/img/admin/unlike.png'></a>",
                        'setComment'=>"<a href='javascript:void(0);' id={$one['aid']} onclick=setComment($(this).attr('id'))><img width='35px' height='35px' src='/img/admin/wechat.png'></a>",
                        'likes'=>$one['likes'],
                        'comments'=>$one['comments'],
                        'content'=>$one['content'],
                        'useForDelete'=>"<a href='javascript:void(0);' id={$one['aid']} onclick=deleteThisArticle($(this).attr('id')) class='btn btn-warning btn-circle btn-sm'><i class='fas fa-trash'></i></a>",
                    ];

                    $i++;
                }

                return $tmp;

                break;

            case 'setLike':

                $aid=$request->aid;

                $uid=$request->uid;

                $suffix=date('Y',substr($aid,0,10));

                ArticleModel::suffix($suffix);

                $oid=ArticleModel::where('aid',$aid)->first()->uid;

                LikesModel::suffix($suffix);

                $obj=LikesModel::firstOrNew(['aid'=>$aid,'uid'=>$uid],['tid'=>$oid,'isLike'=>0,'isRead'=>0,'unixTime'=>time()]);

                if ($obj->isLike===0)
                {
                    $obj->isLike=1;

                    //给印象加分
                    (new CommunityController())->setCommunityScore('like',$uid,$aid);

                }else
                {
                    $obj->isLike=0;
                }

                $iLike=$obj->isLike;

                $obj->save();

                return ['resCode'=>200,'iLike'=>$iLike];

                break;

            case 'sendComment':

                $aid=$request->aid;

                $oid=$request->oid;

                //当前条评论的发送者
                $uid=$request->uid;

                $arr=explode('#',$request->commtent);

                //一个元素说明$isShowTargetName=0 两个元素说明$isShowTargetName=1
                if (count($arr)===2)
                {
                    $isShowTargetName=1;

                    //当前评论发送给谁
                    $tid=substr($arr[0],1);

                    //评论内容
                    $comment=trim($arr[1]);

                }else
                {
                    $isShowTargetName=0;

                    $tid=$oid;

                    //评论内容
                    $comment=trim($arr[0]);
                }

                $suffix=date('Y',substr($aid,0,10));

                CommentsModel::suffix($suffix);

                CommentsModel::create([
                    'aid'=>$aid,
                    'oid'=>$oid,
                    'uid'=>$uid,
                    'tid'=>$tid,
                    'isShow'=>1,
                    'isShowTargetName'=>$isShowTargetName,
                    'comment'=>$comment,
                    'unixTime'=>time(),
                ]);

                //给印象加分
                (new CommunityController())->setCommunityScore('comment',$uid,$aid);

                return ['resCode'=>200];

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

            case 'deleteThisArticle':

                $aid=trim($request->aid);

                $suffix=date('Y',substr($aid,0,10));

                ArticleModel::suffix($suffix);
                $res=ArticleModel::where('aid',$aid)->first();

                //发布者uid
                $publishUid=$res->uid;

                DB::connection($this->db)->beginTransaction();

                try
                {
                    //删除印象图片，视频
                    for ($i=1;$i<=9;$i++)
                    {
                        $varName='picOrVideo'.$i;

                        if ($res->$varName=='') continue;

                        //视频要删的
                        //community/video/2019/origin/2/1570790769DEMXcZ.mp4
                        //community/video/2019/thum/2/1570790769DEMXcZ.jpg

                        //图片要删的
                        //community/pic/2019/origin/0/1570774050d0YhwR1.jpg
                        //community/pic/2019/thum/0/1570774050d0YhwR1.jpg

                        //是不是图片
                        $isPic=1;

                        if (count(explode('video',$res->$varName))==2) $isPic=0;

                        if ($isPic)
                        {
                            //是图片
                            @unlink(public_path().$res->$varName);//缩略图
                            @unlink(public_path().str_replace('thum','origin',$res->$varName));//原图

                        }else
                        {
                            //是视频
                            @unlink(public_path().$res->$varName);//原视频
                            @unlink(public_path().str_replace('mp4','jpg',str_replace('origin','thum',$res->$varName)));//缩略图
                        }
                    }

                    //印象主表删除操作
                    $res->delete();

                    //删除印象评论
                    CommentsModel::suffix($suffix);
                    CommentsModel::where('aid',$aid)->delete();

                    //删除印象赞
                    LikesModel::suffix($suffix);
                    LikesModel::where('aid',$aid)->delete();

                    //删除印象标签
                    ArticleLabelModel::suffix($suffix);
                    ArticleLabelModel::where('aid',$aid)->delete();

                }catch (\Exception $e)
                {
                    DB::connection($this->db)->rollBack();

                    return response()->json(['resCode'=>Config::get('resCode.675')]);
                }

                DB::connection($this->db)->commit();

                Redis::connection('UserInfo')->hincrby($publishUid,'CommunityArticleTotal',-1);

                return ['resCode'=>200];

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

            case 'getUserMsg':

                //存入session
                $uid=$request->uid;

                $tmpUser['uid']=$uid;
                $tmpUser['uName']=trim(Redis::connection('UserInfo')->hget($uid,'name'));
                $tmpUser['uAvatar']='http://newfogapi.wodeluapp.com'.trim(Redis::connection('UserInfo')->hget($uid,'avatar'));

                session()->put('currentNotRealUser',jsonEncode($tmpUser));

                $now=Carbon::now();

                $likes=[];
                $comments=[];

                //取最近5年相关的点赞评论
                for ($i=0;$i<=5;$i++)
                {
                    $suffix=$now->year - $i;

                    $table="community_article_{$suffix}";

                    if (!Schema::connection($this->db)->hasTable($table)) break;

                    //找like表中的tid
                    LikesModel::suffix($suffix);
                    $tmp=LikesModel::where(['tid'=>$uid,'isLike'=>1])->get(['aid','uid','unixTime','isLike'])->toArray();

                    if (!empty($tmp)) $likes=array_merge($likes,$tmp);

                    //找comment表中的tid
                    CommentsModel::suffix($suffix);
                    $tmp=CommentsModel::where('tid',$uid)->get(['aid','uid','unixTime','comment'])->toArray();

                    if (!empty($tmp)) $comments=array_merge($comments,$tmp);
                }

                $res=array_merge($likes,$comments);

                $count=count($res);

                if (!empty($res)) $res=arraySort1($res,['desc','unixTime']);

                $request->page=='' ? $page=1 : $page=$request->page;

                if ($page>0 && !empty($res)) $res=myPage($res,5,$page);

                //处理数据
                foreach ($res as &$one)
                {
                    $one['uName']=trim(Redis::connection('UserInfo')->hget($one['uid'],'name'));
                    $one['uAvatar']='http://newfogapi.wodeluapp.com'.trim(Redis::connection('UserInfo')->hget($one['uid'],'avatar'));
                    $one['unixTime']=formatDate($one['unixTime']);

                    if (isset($one['comment']) && mb_strlen($one['comment'])>30) $one['comment']=mb_substr($one['comment'],0,30).'...';
                }
                unset($one);

                return ['resCode'=>200,'res'=>$res,'count'=>$count];

                break;
        }
    }

    //置顶加精
    public function communityIndex(Request $request)
    {
        //返回今年的印象，按照时间倒序
        return view('admin.community.community_index');
    }

    //点赞评论
    public function communityIndexLikesComments(Request $request)
    {
        //session里有没有已经选过的用户
        $user=session()->get('currentNotRealUser');

        if (!$user)
        {
            $user['uid']=0;
            $user['uAvatar']='http://newfogapi.wodeluapp.com/favicon.ico';
            $user['uName']='请选择虚拟用户';
        }else
        {
            $user=jsonDecode($user);
        }

        $arr=[];

        foreach ($this->uidArr as $one)
        {
            $arr[]=[
                'uid'=>$one,
                'uName'=>trim(Redis::connection('UserInfo')->hget($one,'name')),
                'uAvatar'=>trim(Redis::connection('UserInfo')->hget($one,'avatar'))
            ];
        }

        //返回今年的印象，按照时间倒序
        return view('admin.community.community_index_likesComments')->with(['notRealUser'=>$arr,'currentUser'=>$user]);
    }

    //虚拟用户评论
    public function communitySetComment($aid,$uid)
    {
        //打开评论页

        $suffix=date('Y',substr($aid,0,10));

        //印象详情
        ArticleModel::suffix($suffix);

        $article=ArticleModel::where('aid',$aid)->first()->toArray();
        $article['name']=Redis::connection('UserInfo')->hget($article['uid'],'name');
        $article['avatar']='http://newfogapi.wodeluapp.com'.Redis::connection('UserInfo')->hget($article['uid'],'avatar');

        //所有评论
        CommentsModel::suffix($suffix);

        $comments=CommentsModel::where('aid',$aid)->orderBy('id','desc')->get(['oid','uid','tid','isShowTargetName','unixTime','comment','created_at'])->toArray();

        foreach ($comments as &$one)
        {
            //取得用户名和头像
            $one['oName']=trim(Redis::connection('UserInfo')->hget($one['oid'],'name'));
            if ($one['oName']=='') $one['oName']=randomUserName();
            if (Redis::connection('UserInfo')->hget($one['oid'],'avatar')!='')
            {
                $one['oAvatar']='http://newfogapi.wodeluapp.com'.Redis::connection('UserInfo')->hget($one['oid'],'avatar');
            }else
            {
                $one['oAvatar']=randomUserAvatar();
            }

            $one['uName']=trim(Redis::connection('UserInfo')->hget($one['uid'],'name'));
            if ($one['uName']=='') $one['uName']=randomUserName();
            if (Redis::connection('UserInfo')->hget($one['uid'],'avatar')!='')
            {
                $one['uAvatar']='http://newfogapi.wodeluapp.com'.Redis::connection('UserInfo')->hget($one['uid'],'avatar');
            }else
            {
                $one['uAvatar']=randomUserAvatar();
            }

            $one['tName']=trim(Redis::connection('UserInfo')->hget($one['tid'],'name'));
            if ($one['tName']=='') $one['tName']=randomUserName();
            if (Redis::connection('UserInfo')->hget($one['tid'],'avatar')!='')
            {
                $one['tAvatar']='http://newfogapi.wodeluapp.com'.Redis::connection('UserInfo')->hget($one['tid'],'avatar');
            }else
            {
                $one['tAvatar']=randomUserAvatar();
            }
        }
        unset($one);

        return view('admin.community.set_comment')->with(['article'=>$article,'comments'=>$comments,'userId'=>$uid]);
    }

    //虚拟用户的小红点，加载更多
    public function communityIndexMoreDetail($uid)
    {
        //废止

        dd($uid);








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
        //虚拟用户uid
        $arr=[103595,104994,191662,138283,106241,187126,18656,137544,18658,18657,104563,22357,137545,97105,31794];

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
