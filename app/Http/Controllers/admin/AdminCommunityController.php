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
    public $uid='103595,104994,191662,138283,106241,187126,18656,137544,18658,18657,104563,22357,137545,97105,31794,18419,17767,18463,18464,18475';
    public $uidArr=[103595,104994,191662,138283,106241,187126,18656,137544,18658,18657,104563,22357,137545,97105,31794,18419,17767,18463,18464,18475];

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

                if ($gName=='')
                {
                    //随机发一个
                    //return response()->json(['resCode'=>Config::get('resCode.605')]);

                    $res=DB::connection('masterDB')->table('grid')->where('price','>=',1000)->limit(500)->get(['name']);

                    $res=jsonDecode($res);

                    shuffle($res);

                    $res=current($res);

                    $gName=$res['name'];
                }

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
                $content=str_replace('&nbsp;','',$content);

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

            case 'getTargetGridName':

                $cond=$request->cond;

                if ($cond=='北京')
                {
                    $gName='n4w5,n4w4,n4w3,n4w2,n4w1,n4,n4e1,n4e2,n4e3,n4e4,n3w5,n3w4,n3w3,n3w2,n3w1,n3,n3e1,n3e2,n3e3,n3e4,n2w5,n2w4,n2w3,n2w2,n2w1,n2,n2e1,n2e2,n2e3,n2e4,n1w5,n1w4,n1w3,n1w2,n1w1,n1,n1e1,n1e2,n1e3,n1e4,w5,w4,w3,w2,w1,c0,e1,e2,e3,e4,s1w5,s1w4,s1w3,s1w2,s1w1,s1,s1e1,s1e2,s1e3,s1e4,s2w5,s2w4,s2w3,s2w2,s2w1,s2,s2e1,s2e2,s2e3,s2e4,s3w5,s3w4,s3w3,s3w2,s3w1,s3,s3e1,s3e2,s3e3,s3e4';
                }elseif ($cond=='上海')
                {
                    $gName='s346e141,s346e142,s346e143,s346e144,s346e145,s346e146,s346e147,s346e148,s346e149,s346e150,s346e151,s346e152,s346e153,s347e141,s347e142,s347e143,s347e144,s347e145,s347e146,s347e147,s347e148,s347e149,s347e150,s347e151,s347e152,s347e153,s348e141,s348e142,s348e143,s348e144,s348e145,s348e146,s348e147,s348e148,s348e149,s348e150,s348e151,s348e152,s348e153,s348e154,s349e141,s349e142,s349e143,s349e144,s349e145,s349e146,s349e147,s349e148,s349e149,s349e150,s349e151,s349e152,s349e153,s349e154,s350e141,s350e142,s350e143,s350e144,s350e145,s350e146,s350e147,s350e148,s350e149,s350e150,s350e151,s350e152,s350e153,s350e154,s350e155,s351e141,s351e142,s351e143,s351e144,s351e145,s351e146,s351e147,s351e148,s351e149,s351e150,s351e151,s351e152,s351e153,s351e154,s351e155,s352e141,s352e142,s352e143,s352e144,s352e145,s352e146,s352e147,s352e148,s352e149,s352e150,s352e151,s352e152,s352e153,s352e154,s352e155,s353e141,s353e142,s353e143,s353e144,s353e145,s353e146,s353e147,s353e148,s353e149,s353e150,s353e151,s353e152,s353e153,s353e154,s353e155,s353e156,s354e141,s354e142,s354e143,s354e144,s354e145,s354e146,s354e147,s354e148,s354e149,s354e150,s354e151,s354e152,s354e153,s354e154,s354e155,s354e156,s355e141,s355e142,s355e143,s355e144,s355e145,s355e146,s355e147,s355e148,s355e149,s355e150,s355e151,s355e152,s355e153,s355e154,s355e155,s355e156,s355e157,s356e141,s356e142,s356e143,s356e144,s356e145,s356e146,s356e147,s356e148,s356e149,s356e150,s356e151,s356e152,s356e153,s356e154,s356e155,s356e156,s356e157,s357e141,s357e142,s357e143,s357e144,s357e145,s357e146,s357e147,s357e148,s357e149,s357e150,s357e151,s357e152,s357e153,s357e154,s357e155,s357e156,s357e157,s357e158,s358e141,s358e142,s358e143,s358e144,s358e145,s358e146,s358e147,s358e148,s358e149,s358e150,s358e151,s358e152,s358e153,s358e154,s358e155,s358e156,s358e157,s358e158';
                }elseif ($cond=='广州')
                {
                    $gName='s669w94,s669w93,s669w92,s669w91,s669w90,s669w89,s669w88,s669w87,s669w86,s669w85,s670w94,s670w93,s670w92,s670w91,s670w90,s670w89,s670w88,s670w87,s670w86,s670w85,s671w94,s671w93,s671w92,s671w91,s671w90,s671w89,s671w88,s671w87,s671w86,s671w85,s672w94,s672w93,s672w92,s672w91,s672w90,s672w89,s672w88,s672w87,s672w86,s672w85,s673w94,s673w93,s673w92,s673w91,s673w90,s673w89,s673w88,s673w87,s673w86,s673w85,s674w94,s674w93,s674w92,s674w91,s674w90,s674w89,s674w88,s674w87,s674w86,s674w85,s675w94,s675w93,s675w92,s675w91,s675w90,s675w89,s675w88,s675w87,s675w86,s675w85,s676w94,s676w93,s676w92,s676w91,s676w90,s676w89,s676w88,s676w87,s676w86,s676w85,s677w94,s677w93,s677w92,s677w91,s677w90,s677w89,s677w88,s677w87,s677w86,s677w85,s678w94,s678w93,s678w92,s678w91,s678w90,s678w89,s678w88,s678w87,s678w86,s678w85,s679w94,s679w93,s679w92,s679w91,s679w90,s679w89,s679w88,s679w87,s679w86,s679w85,s680w94,s680w93,s680w92,s680w91,s680w90,s680w89,s680w88,s680w87,s680w86,s680w85,s681w94,s681w93,s681w92,s681w91,s681w90,s681w89,s681w88,s681w87,s681w86,s681w85,s682w94,s682w93,s682w92,s682w91,s682w90,s682w89,s682w88,s682w87,s682w86,s682w85,s683w94,s683w93,s683w92,s683w91,s683w90,s683w89,s683w88,s683w87,s683w86,s683w85,s684w94,s684w93,s684w92,s684w91,s684w90,s684w89,s684w88,s684w87,s684w86,s684w85';
                }elseif ($cond=='深圳')
                {
                    $gName='s691w74,s691w73,s691w72,s691w71,s691w70,s691w69,s691w68,s691w67,s691w66,s691w65,s691w64,s691w63,s691w62,s692w74,s692w73,s692w72,s692w71,s692w70,s692w69,s692w68,s692w67,s692w66,s692w65,s692w64,s692w63,s692w62,s693w73,s693w72,s693w71,s693w70,s693w69,s693w68,s693w67,s693w66,s693w65,s693w64,s693w63,s693w62,s694w73,s694w72,s694w71,s694w70,s694w69,s694w68,s694w67,s694w66,s694w65,s694w64,s694w63,s694w62,s695w73,s695w72,s695w71,s695w70,s695w69,s695w68,s695w67,s695w66,s695w65,s695w64,s695w63,s695w62';
                }elseif ($cond=='成都')
                {
                    $gName='s367w356,s367w355,s367w354,s367w353,s367w352,s367w351,s367w350,s368w356,s368w355,s368w354,s368w353,s368w352,s368w351,s368w350,s369w356,s369w355,s369w354,s369w353,s369w352,s369w351,s369w350,s370w356,s370w355,s370w354,s370w353,s370w352,s370w351,s370w350,s371w356,s371w355,s371w354,s371w353,s371w352,s371w351,s371w350,s372w356,s372w355,s372w354,s372w353,s372w352,s372w351,s372w350,s373w356,s373w355,s373w354,s373w353,s373w352,s373w351,s373w350,s374w356,s374w355,s374w354,s374w353,s374w352,s374w351,s374w350';
                }else
                {
                    $gName='';
                }

                $myGridName=$gName;

                $suffix=Carbon::now()->year;

                $gName=explode(',',$gName);

                $tmp='';
                foreach ($gName as $one)
                {
                    $tmp.='"'.$one.'",';
                }

                $tmp=rtrim($tmp,',');

                $gName=$tmp;

                $sql="select gName,count(1) as total from community_article_{$suffix} where gName in ({$gName}) group by gName order by total asc";

                $res=DB::connection('communityDB')->select($sql);

                $gName=explode(',',$myGridName);

                $gName=array_flip($gName);

                foreach ($gName as &$row)
                {
                    $row=0;
                }
                unset($row);

                //遍历sql返回的结果
                foreach ($res as $one)
                {
                    $gName[$one->gName]+=$one->total;
                }

                return ['resCode'=>200,'data'=>$gName];

                break;
        }

        return true;
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
        $arr=[103595,104994,191662,138283,106241,187126,18656,137544,18658,18657,104563,22357,137545,97105,31794,18419,17767,18463,18464,18475];

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

                //webAdmin目录不用删除图片，每次后台发布印象图片时候会覆盖
                $url[]="/webAdmin/temp/{$uid}thum{$num}.jpg?".time();

                $num++;
            }
        }

        return ['errno'=>0,'data'=>$url];
    }






}
