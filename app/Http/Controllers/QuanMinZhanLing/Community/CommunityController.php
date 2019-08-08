<?php

namespace App\Http\Controllers\QuanMinZhanLing\Community;

use App\Http\Controllers\QuanMinZhanLing\BaseController;
use App\Http\Controllers\Server\ContentCheckBase;
use App\Http\Controllers\Server\StoreVideoBase;
use App\Model\Community\ArticleModel;
use App\Model\Community\CommentsModel;
use App\Model\Community\LabelModel;
use App\Model\Community\LikesModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Intervention\Image\Facades\Image;

class CommunityController extends BaseController
{
    public $db='communityDB';

    //建表
    public function createTable($tableType)
    {
        $db=$this->db;

        $now=Carbon::now();

        if ($tableType=='article')
        {
            //印象表预估每天5000条，一年就是200万条
            //所以按照年分表
            $suffix=$now->year;

            $table="community_{$tableType}_{$suffix}";

            if (!Schema::connection($db)->hasTable($table))
            {
                //印象板表
                Schema::connection($db)->create($table, function (Blueprint $table)
                {
                    $table->string('aid',20)->comment('真正的印象主键，10位的unixTime加上6位随机字符串');
                    $table->integer('uid')->unsigned()->comment('用户主键，谁发的这条印象');
                    $table->string('gName',20)->comment('格子编号，老康命名');
                    $table->text('content')->nullable()->comment('内容');
                    $table->tinyInteger('isShow')->unsigned()->default(0)->comment('是否可以显示，1是可以，0是不可以');
                    $table->tinyInteger('myself')->unsigned()->default(0)->comment('是否可以显示，1是仅自己，0是全部用户');
                    $table->smallInteger('isTop')->unsigned()->default(0)->comment('置顶程度，0是没置顶，置顶每挡10分');
                    $table->smallInteger('theBest')->unsigned()->default(0)->comment('加精程度，0是没加精，加精每挡10分');
                    $table->tinyInteger('includeText')->unsigned()->default(0)->comment('是否有文字，1是有，0是没有');
                    $table->tinyInteger('includePic')->unsigned()->default(0)->comment('是否有图片，1是有，0是没有');
                    $table->tinyInteger('includeVideo')->unsigned()->default(0)->comment('是否有视频，1是有，0是没有');
                    $table->integer('unixTime')->unsigned()->nullable()->comment('排序用的时间');
                    $table->string('picOrVideo1',200)->nullable()->comment('图片1或视频1');
                    $table->string('picOrVideo2',200)->nullable()->comment('图片2或视频2');
                    $table->string('picOrVideo3',200)->nullable()->comment('图片3或视频3');
                    $table->string('picOrVideo4',200)->nullable()->comment('图片4或视频4');
                    $table->string('picOrVideo5',200)->nullable()->comment('图片5或视频5');
                    $table->string('picOrVideo6',200)->nullable()->comment('图片6或视频6');
                    $table->string('picOrVideo7',200)->nullable()->comment('图片7或视频7');
                    $table->string('picOrVideo8',200)->nullable()->comment('图片8或视频8');
                    $table->string('picOrVideo9',200)->nullable()->comment('图片9或视频9');
                    $table->timestamps();
                    $table->primary('aid');
                    $table->index('uid');
                    $table->index('gName');
                    $table->index('unixTime');
                });

                //以下用不到，没准以后能用到

                //重建主键
                //DB::connection($db)->statement("Alter table {$table} drop primary key,add primary key (`id`,`uid`)");

                //添加分区
                //DB::connection($db)->statement("Alter table {$table} partition by linear key(`uid`) partitions 16");
            }
        }

        if ($tableType=='label')
        {
            //标签表，如果1个用户创建100个标签，需要100万个用户创建，mysql才会满
            //所以不分表了
            $table="community_{$tableType}";

            if (!Schema::connection($db)->hasTable($table))
            {
                //建表
                $sql=<<<Eof
CREATE TABLE `{$table}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增主键',
  `uid` int(10) unsigned DEFAULT NULL COMMENT '用户主键，谁创建的这条标签',
  `labelContent` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标签内容',
  `useTotal` int(20) unsigned NOT NULL DEFAULT '0' COMMENT '该标签使用总次数',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `{$table}_uid` (`uid`),
  KEY `{$table}_useTotal` (`useTotal`),
  FULLTEXT KEY `{$table}_labelContent` (`labelContent`) with parser ngram
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
Eof;
                DB::connection($db)->statement($sql);
            }
        }

        if ($tableType=='article_label')
        {
            //印象-标签的关系表，如果每天1万个印象，每个印象3个标签，一年1000万条记录
            //所以按照年分表
            $suffix=$now->year;

            $table="community_{$tableType}_{$suffix}";

            if (!Schema::connection($db)->hasTable($table))
            {
                //印象-标签
                Schema::connection($db)->create($table, function (Blueprint $table)
                {
                    $table->string('aid',20)->comment('真正的印象主键，10位的unixTime加上6位随机字符串');
                    $table->integer('labelId')->unsigned()->comment('标签主键');
                    $table->string('gName',20)->comment('格子标签');
                    $table->integer('unixTime')->unsigned()->comment('排序用的时间');
                    $table->timestamps();
                    $table->primary(['aid','labelId']);
                    $table->index('labelId');
                    $table->index('gName');
                    $table->index('unixTime');
                });

                //重建主键
                //DB::connection($db)->statement("Alter table {$table} drop primary key,add primary key (`id`,`uid`)");

                //添加分区
                DB::connection($db)->statement("Alter table {$table} partition by linear key(`labelId`) partitions 16");
            }
        }

        if ($tableType=='article_like')
        {
            //印象-获赞的关系表，如果每天1万个印象，每个印象200个赞，一年7亿3千万条记录，每季度1亿8千万条记录
            //所以按年分表
            $suffix=$now->year;

            $table="community_{$tableType}_{$suffix}";

            if (!Schema::connection($db)->hasTable($table))
            {
                //印象-获赞
                Schema::connection($db)->create($table, function (Blueprint $table)
                {
                    $table->string('aid',20)->comment('真正的印象主键，10位的unixTime加上6位随机字符串');
                    $table->integer('uid')->unsigned()->comment('用户主键，谁给这条印象点赞了');
                    $table->integer('tid')->unsigned()->comment('用户主键，这条评论是给谁的');
                    $table->tinyInteger('isLike')->unsigned()->comment('是否点赞，1是点赞了，0是没点赞');
                    $table->tinyInteger('isRead')->unsigned()->default(0)->comment('目标用户是否已读，1是已读，0是未读');
                    $table->integer('unixTime')->unsigned()->comment('排序用的时间');
                    $table->timestamps();
                    $table->primary(['aid','uid']);
                    $table->index('uid');
                    $table->index('tid');
                });
            }
        }

        if ($tableType=='article_comment')
        {
            //印象-评论的关系表，如果每天1万个印象，每个印象200个评论，一年7亿3千万条记录，每季度1亿8千万条记录
            //所以按年分表
            $suffix=$now->year;

            $table="community_{$tableType}_{$suffix}";

            if (!Schema::connection($db)->hasTable($table))
            {
                //印象-评论
                Schema::connection($db)->create($table, function (Blueprint $table)
                {
                    $table->increments('id')->unsigned()->comment('主键');
                    $table->string('aid',20)->comment('真正的印象主键，10位的unixTime加上6位随机字符串');
                    $table->integer('uid')->unsigned()->comment('用户主键，谁给这条印象评论了');
                    $table->integer('tid')->unsigned()->comment('用户主键，这条评论是给谁的');
                    $table->tinyInteger('isShow')->unsigned()->default(1)->comment('是否可以显示，1是可以，0是不可以');
                    $table->tinyInteger('isShowTargetName')->unsigned()->default(0)->comment('是否显示目标用户姓名，1是显示，0是不显示');
                    $table->tinyInteger('isRead')->unsigned()->default(0)->comment('目标用户是否已读，1是已读，0是未读');
                    $table->tinyInteger('isOwnersRead')->unsigned()->default(0)->comment('印象发布者是否已读，1是已读，0是未读');
                    $table->text('comment')->nullable()->comment('评论内容');
                    $table->integer('unixTime')->unsigned()->comment('排序用的时间');
                    $table->timestamps();
                    $table->index('aid');
                    $table->index('uid');
                    $table->index('tid');
                });
            }
        }

        return true;
    }

    //生成印象表主键
    public function getArticlePrimary()
    {
        return time().str_random(6);
    }

    //生成缩略图，原图也存，返回缩略图路径或者error
    public function storePic($base64,$picNum,$articleID)
    {
        //存到public/community/pic/当年/thum/articleID%5/
        $year=Carbon::now()->year;
        $suffix=string2Number($articleID)%5;

        //生成文件名，picNum是该印象的第几张图
        $fileName=$articleID.$picNum.'.jpg';

        //存缩略图的目录
        $storePathForThum  =public_path("community/pic/{$year}/thum/{$suffix}/");
        $returnPathForThum="/community/pic/{$year}/thum/{$suffix}/";
        $storePathForOrigin=public_path("community/pic/{$year}/origin/{$suffix}/");

        //不存在就创建目录
        try
        {
            if (!is_dir($storePathForThum))   mkdir($storePathForThum,0777,true);
            if (!is_dir($storePathForOrigin)) mkdir($storePathForOrigin,0777,true);

        }catch (\Exception $e)
        {
            return 'mkdir error';
        }

        //存origin
        try
        {
            Image::make($base64)->save($storePathForOrigin.$fileName,100);

        }catch (\Exception $e)
        {
            return 'store origin pic error';
        }

        //pic info
        try
        {
            $picInfo=getimagesize($storePathForOrigin.$fileName);
            $width=$height=null;
            $picInfo[0] > $picInfo[1] ? $height=200 : $width=200;

        }catch (\Exception $e)
        {
            return 'get pic info error';
        }

        //存thum
        try
        {
            Image::make($base64)->resize($width,$height,function($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->crop(200,200)->save($storePathForThum.$fileName);

        }catch (\Exception $e)
        {
            return 'store thum pic error';
        }

        //返回缩略图路径
        return [$returnPathForThum.$fileName];
    }

    //储存视频
    public function storeVideo($file,$articleID)
    {
        $ffmpeg=new StoreVideoBase();

        $year=Carbon::now()->year;
        $suffix=string2Number($articleID)%5;

        //储存视频的路径
        $storePathForOrigin=public_path("community/video/{$year}/origin/{$suffix}/");
        //储存视频缩略图的路径
        $storePathForThum  =public_path("community/video/{$year}/thum/{$suffix}/");
        $returnPathForOrigin="/community/video/{$year}/origin/{$suffix}/";

        //不存在就创建目录
        try
        {
            if (!is_dir($storePathForThum))   mkdir($storePathForThum,0777,true);
            if (!is_dir($storePathForOrigin)) mkdir($storePathForOrigin,0777,true);

        }catch (\Exception $e)
        {
            return 'mkdir error';
        }

        //存缩略图
        $filaName=$articleID;

        $res=$ffmpeg->storeVideoThum($file,$storePathForThum.$filaName.'.jpg');

        if (!is_array($res)) return 'create thum error';

        //移动真正的视频
        try
        {
            //$fileExtension=$file->getClientOriginalExtension();
            //$file->move($storePathForOrigin,$filaName.'.'.$fileExtension);

            $ffmpeg->changeToMp4($file,$storePathForOrigin.$filaName.'.mp4');

        }catch (\Exception $e)
        {
            return 'move video error';
        }

        //return [$returnPathForOrigin.$filaName.'.'.$fileExtension];
        return [$returnPathForOrigin.$filaName.'.mp4'];
    }

    //发布一条印象
    public function createArticle(Request $request)
    {
        $articlePrimary=$this->getArticlePrimary();

        $uid=trim($request->uid);

        if (!is_numeric($uid) || $uid <= 0) return response()->json(['resCode'=>Config::get('resCode.601')]);

        $gName=trim($request->gName);

        if ($gName=='') return response()->json(['resCode'=>Config::get('resCode.605')]);

        $myself=(int)trim($request->myself);

        if (!is_numeric($myself)) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $content=trim($request->contents);

        //内容检测？
        if ($content!='')
        {
            $check=(new ContentCheckBase())->check($content);

            if ($check!=null) return response()->json(['resCode'=>Config::get('resCode.661')]);
        }

        $labels=array_flatten(jsonDecode($request->labels));

        if (empty($labels)) return response()->json(['resCode'=>Config::get('resCode.665')]);
        //if (empty($labels)) $labels=[9,4,5,6,2,1,10,11];

        //升序
        sort($labels);

        $includeText=0;
        $includePic=0;
        $includeVideo=0;

        //存储图片或这视频的数组
        $readyToInsertForPicAndVideo=[];

        //每个图片100k或者200k
        if ($request->picArr instanceof UploadedFile)
        {
            //直接传的图片，测试用的
            $picArr=$request->file('picArr');

            dd('you got it');

        }elseif ($request->picArr!='' && is_array(jsonDecode($request->picArr)))
        {
            //base64的数组，处理图片时候需要base64_decode()
            $picArr=jsonDecode($request->picArr);

            $num=1;
            //有一张图片解析失败，就不让发布这条印象
            foreach ($picArr as $one)
            {
                //图片内容安全检测
                $res=(new ContentCheckBase())->checkPic(base64_decode($one));

                if (!$res) return response()->json(['resCode'=>Config::get('resCode.662')]);

                $res=$this->storePic(base64_decode($one),$num,$articlePrimary);

                if (!is_array($res)) return response()->json(['resCode'=>Config::get('resCode.650')]);

                $readyToInsertForPicAndVideo["picOrVideo{$num}"]=current($res);

                $num++;
            }

            $includePic=1;

        }else
        {
            $picArr=[];
        }

        //以上，如果不出错，图片是处理完了
        //下面处理视频
        $res=$request->file('video1');

        if ($res instanceof UploadedFile)
        {
            $res=$this->storeVideo($res,$articlePrimary);

            if (!is_array($res)) return response()->json(['resCode'=>Config::get('resCode.651')]);

            $readyToInsertForPicAndVideo['picOrVideo1']=current($res);

            $includeVideo=1;
        }

        if ($content!='') $includeText=1;

        //全都是空发什么发，发你麻痹
        if ($includeText==0 && $includePic==0 && $includeVideo==0) return response()->json(['resCode'=>Config::get('resCode.664')]);

        $readyToInsert=[
            'aid'=>$articlePrimary,
            'uid'=>$uid,
            'gName'=>$gName,
            'content'=>$content,
            'isShow'=>0,
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
                    'aid'=>$articlePrimary,
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

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //返回官方创建的标签
    public function getTssjLabel(Request $request)
    {
        //101以前都是官方预留
        $res=LabelModel::where('id','<=',101)->where('labelContent','!=','amyYOEPCiph6NQr')->get(['id','labelContent']);

        return response()->json(['resCode'=>Config::get('resCode.200'),'labels'=>$res]);
    }

    //查找标签
    public function selectLabel(Request $request)
    {
        $cond=filter4(trim($request->cond));

        //$labelName最多15个字节
        if (strlen($cond) > 15) return response()->json(['resCode'=>Config::get('resCode.668')]);

        //$labelName只能是中文，字母，数字
        if (!preg_match_all('/^[\x{4e00}-\x{9fa5}A-Za-z0-9]{1,}$/u',$cond,$match)) return response()->json(['resCode'=>Config::get('resCode.667')]);

        $num=mb_strlen($cond);

        if ($num===0) return response()->json(['resCode'=>Config::get('resCode.666')]);

        if ($num===1)
        {
            //取出最热的10个
            $res=LabelModel::where('labelContent','like',"{$cond}%")
                ->where('labelContent','!=','amyYOEPCiph6NQr')
                ->orderBy('useTotal','desc')
                ->orderBy('id')
                ->limit(10)
                ->get(['id','labelContent'])->toArray();

            //当前搜索标签是不是存在
            $res=$this->currentLabelIsExistAndExecReturn($cond,$res);

            return response()->json(['resCode'=>Config::get('resCode.200'),'currentSelect'=>$res[0],'labels'=>$res[1]]);

        }elseif ($num>1)
        {
            $sql="select id,labelContent from community_label where match(labelContent) against('+{$cond}' in boolean mode) and labelContent <> 'amyYOEPCiph6NQr' order by useTotal desc,id asc limit 10";

            //取出最热的10个
            $res=DB::connection($this->db)->select($sql);

            //当前搜索标签是不是存在
            $res=$this->currentLabelIsExistAndExecReturn($cond,$res);

            return response()->json(['resCode'=>Config::get('resCode.200'),'currentSelect'=>$res[0],'labels'=>$res[1]]);

        }else
        {
            return response()->json(['resCode'=>Config::get('resCode.200'),'currentSelect'=>[],'labels'=>[]]);
        }
    }

    //查找当前搜索标签是不是存在，并且处理一下返回结果
    public function currentLabelIsExistAndExecReturn($cond,$res)
    {
        $current=LabelModel::where(['labelContent'=>$cond])->where('labelContent','!=','amyYOEPCiph6NQr')->first();

        if ($current==null)
        {
            $r[0]=['id'=>null,'allowCreate'=>1,'labelContent'=>$cond];

            $r[1]=$res;
        }

        if ($current!=null)
        {
            $r[0]=['id'=>$current->id,'allowCreate'=>0,'labelContent'=>$current->labelContent];

            $tmp=[];

            if (!empty($res))
            {
                foreach ($res as $one)
                {
                    if (is_object($one))
                    {
                        if ($one->id!=$current->id)
                        {
                            $tmp[]=$one;
                        }
                    }

                    if (is_array($one))
                    {
                        if ($one['id']!=$current->id)
                        {
                            $tmp[]=$one;
                        }
                    }
                }
            }

            $r[1]=$tmp;
        }

        return $r;
    }

    //创建标签
    public function createLabel(Request $request)
    {
        $uid=trim($request->uid);

        if (!is_numeric($uid) || $uid <= 0) return response()->json(['resCode'=>Config::get('resCode.601')]);

        $labelContent=filter4(trim($request->labelContent));

        //$labelName最多15个字节
        if (strlen($labelContent) > 15) return response()->json(['resCode'=>Config::get('resCode.668')]);

        //$labelName只能是中文，字母，数字
        if (!preg_match_all('/^[\x{4e00}-\x{9fa5}A-Za-z0-9]{1,}$/u',$labelContent,$match)) return response()->json(['resCode'=>Config::get('resCode.667')]);

        try
        {
            //内容安全检查
            $check=(new ContentCheckBase())->check($labelContent);

            if ($check!=null) return response()->json(['resCode'=>Config::get('resCode.670')]);

            //存在的不能插入
            $res=LabelModel::where(['labelContent'=>$labelContent])->first();

            if ($res!=null) return response()->json(['resCode'=>Config::get('resCode.669')]);

            //创建新标签
            $res=LabelModel::create(['uid'=>$uid,'labelContent'=>$labelContent]);

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.631')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'labelPrimaryKey'=>$res->id,'labelContent'=>$res->labelContent]);
    }

    //查看一个格子下的印象
    public function getArticleByGridName(Request $request)
    {
        $page=(int)trim($request->page);

        $page < 1 ? $page=1 : null;

        $uid=trim($request->uid);

        if (!is_numeric($uid) || $uid <= 0) return response()->json(['resCode'=>Config::get('resCode.601')]);

        $gName=trim($request->gName);

        $gridInfo=DB::connection('masterDB')->table('grid')->where('name',$gName)->first();

        //格子不存在
        if (!$gridInfo) return response()->json(['resCode'=>Config::get('resCode.605')]);

        //建表
        $this->createTable('article');
        //$this->createTable('label');
        $this->createTable('article_label');
        $this->createTable('article_like');
        $this->createTable('article_comment');

        //取出4个最热标签
        $hotLabels=[];

        //取出该格子置顶的，格子主人最后一条，加精的印象

        //只有第一页才组合这三个
        if ($page===1)
        {
            //取出4个最热标签
            $hotLabels=$this->getHotLabels($gName,4);

            //置顶
            $onTop=$this->getOnTopArticle($gName);

            //格子主人最后一条
            $gridOwners=$this->getGridOwnersLastArticle($gName,$uid);

            //加精
            $theBest=$this->getTheBestArticle($gName);
        }

        //分页取出除去置顶加精格主最后一条的印象
        $commData=$this->getArticleByPaginate($gName,$uid,$page);

        //给所有印象加赞和评论
        if (isset($onTop))
        {
            $onTop=$this->addLikesToArticle($onTop);
            $onTop=$this->addCommentsToArticle($onTop);

            //排序
            $onTop=$this->sortArticle($onTop);

        }else
        {
            $onTop=[[],[]];
        }
        if (isset($gridOwners))
        {
            $gridOwners=$this->addLikesToArticle($gridOwners);
            $gridOwners=$this->addCommentsToArticle($gridOwners);

            //排序
            $gridOwners=$this->sortArticle($gridOwners);

        }else
        {
            $gridOwners=[[],[]];
        }
        if (isset($theBest))
        {
            $theBest=$this->addLikesToArticle($theBest);
            $theBest=$this->addCommentsToArticle($theBest);

            //排序
            $theBest=$this->sortArticle($theBest);

        }else
        {
            $theBest=[[],[]];
        }
        if (isset($commData))
        {
            $commData=$this->addLikesToArticle($commData);
            $commData=$this->addCommentsToArticle($commData);

            //排序
            $commData=$this->sortArticle($commData);

        }else
        {
            $commData=[[],[]];
        }

        return response()->json([
            'resCode'=>Config::get('resCode.200'),
            'hotLabels'=>$hotLabels,
            'onTop'=>$onTop[0],
            'theBest'=>$theBest[0],
            'gridOwners'=>$gridOwners[0],
            'commData'=>$commData[0]
        ]);
    }

    //返回最热标签
    public function getHotLabels($gName,$num)
    {
        //该格子下今年的最热标签，取前4，在印象-标签关系表中
        $now=Carbon::now();

        $sql="select labelId,labelContent,count(1) as useTotal from community_article_label_{$now->year} as t1 left join community_label as t2 on t1.labelId=t2.id where gName='{$gName}' group by labelId order by useTotal desc limit {$num};";

        $res=jsonDecode(jsonEncode(DB::connection($this->db)->select($sql)));

        //不够4个，取去年的
        if (count($res) < $num)
        {
            $year=$now->year - 1;

            //只有当去年的表存在，才取去年的
            if (Schema::connection($this->db)->hasTable("community_article_label_{$year}"))
            {
                $sql="select labelId,labelContent,count(1) as useTotal from community_article_label_{$year} as t1 left join community_label as t2 on t1.labelId=t2.id where gName='{$gName}' group by labelId order by useTotal desc limit {$num};";

                $res=jsonDecode(jsonEncode(DB::connection($this->db)->select($sql)));
            }
        }

        return $res;
    }

    //返回最热40个标签
    public function getHotLabelsLimit40(Request $request)
    {
        //其实就返回最近两年链表后的前40个就行，等2020年改一下代码
        $limit=40;

        $now=Carbon::now();

        $sql="select labelId,labelContent,count(1) as useTotal from community_article_label_{$now->year} as t1 left join community_label as t2 on t1.labelId=t2.id where labelId>101 group by labelId order by useTotal desc limit {$limit}";

        $res=jsonDecode(jsonEncode(DB::connection($this->db)->select($sql)));

        return response()->json(['resCode'=>Config::get('resCode.200'),'labels'=>$res]);
    }

    //返回一个格子下的所有置顶印象
    public function getOnTopArticle($gName)
    {
        $res=[];
        $aid=[];

        $now=Carbon::now();

        for ($i=0;$i<=100;$i++)
        {
            $suffix=$now->year - $i;

            $table="community_article_{$suffix}";

            //找不到表，说明没数据了
            if (!Schema::connection($this->db)->hasTable($table)) break;

            //找到表了
            ArticleModel::suffix($suffix);

            $art=ArticleModel::where(['gName'=>$gName,'isShow'=>1])->where('isTop','>',0)->get()->toArray();

            if (empty($art)) continue;

            foreach ($art as $one)
            {
                $res[]=$one;
                $aid[]=$one['aid'];
            }
        }

        return [$res,array_flatten($aid)];
    }

    //返回一个格子的格子主人最后一条印象
    public function getGridOwnersLastArticle($gName,$uid)
    {
        $res=[];
        $aid=[];

        $now=Carbon::now();

        $gridOwnersUid=(int)DB::connection('masterDB')->table('grid')->where('name',$gName)->first()->belong;

        for ($i=0;$i<=100;$i++)
        {
            $suffix=$now->year - $i;

            $table="community_article_{$suffix}";

            //找不到表，说明没数据了
            if (!Schema::connection($this->db)->hasTable($table)) break;

            //找到表了
            ArticleModel::suffix($suffix);

            if ($uid==$gridOwnersUid)
            {
                $res=ArticleModel::where(['gName'=>$gName,'uid'=>$gridOwnersUid])->orderBy('unixTime','desc')->limit(1)->get()->toArray();

            }else
            {
                $res=ArticleModel::where(['gName'=>$gName,'uid'=>$gridOwnersUid,'isShow'=>1])->orderBy('unixTime','desc')->limit(1)->get()->toArray();
            }

            //找到数据就跳出
            if (!empty($res)) break;
        }

        foreach ($res as $one)
        {
            $aid[]=$one['aid'];
        }

        return [$res,array_flatten($aid)];
    }

    //返回一个格子下的所有加精印象
    public function getTheBestArticle($gName)
    {
        $res=[];
        $aid=[];

        $now=Carbon::now();

        for ($i=0;$i<=100;$i++)
        {
            $suffix=$now->year - $i;

            $table="community_article_{$suffix}";

            //找不到表，说明没数据了
            if (!Schema::connection($this->db)->hasTable($table)) break;

            //找到表了
            ArticleModel::suffix($suffix);

            $art=ArticleModel::where(['gName'=>$gName,'isShow'=>1])->where('theBest','>',0)->get()->toArray();

            if (empty($art)) continue;

            foreach ($art as $one)
            {
                $res[]=$one;
                $aid[]=$one['aid'];
            }
        }

        return [$res,array_flatten($aid)];
    }

    //分页返回一个格子下的印象
    public function getArticleByPaginate($gName,$uid,$page,$paginate=3)
    {
        $res=[];
        $aid=[];

        $now=Carbon::now();

        $offset=($page-1)*$paginate;

        $tableTarget=[];
        //只取得最近4年的？此处留坑
        for ($i=0;$i<4;$i++)
        {
            $suffix=$now->year - $i;

            $table="community_article_{$suffix}";

            if (!Schema::connection($this->db)->hasTable($table)) continue;

            $tableTarget[]=$table;
        }

        //整理sql
        if (empty($tableTarget)) return [$res,$aid];

        $sql='';
        foreach ($tableTarget as $oneTable)
        {
            $sql.=" union select * from {$oneTable}";
        }

        $sql=trim(ltrim(trim($sql),'union'));

        //union出来的临时表没有索引
        $reslSql="select * from ({$sql}) as tmp where gName='{$gName}' and isTop=0 and theBest=0 and ((isShow=1) or (isShow=0 and uid={$uid})) order by unixTime desc limit {$offset},{$paginate}";

        $res=DB::connection($this->db)->select($reslSql);

        if (empty($res)) return [jsonDecode(jsonEncode($res)),$aid];

        //除去格子主人最后一条
        $gridOwnersLastArticle=$this->getGridOwnersLastArticle($gName,$uid);

        //取出aid
        foreach ($res as $val)
        {
            $aid[]=$val->aid;
        }

        if (empty($gridOwnersLastArticle[1])) return [jsonDecode(jsonEncode($res)),array_flatten($aid)];

        $tmp=[];
        foreach ($res as $oneArticle)
        {
            if ($oneArticle->aid==current($gridOwnersLastArticle[1])) continue;

            $tmp[]=$oneArticle;
        }

        //取出aid
        foreach ($tmp as $val)
        {
            $aid[]=$val->aid;
        }

        return [jsonDecode(jsonEncode($tmp)),array_flatten($aid)];
    }

    //返回每条印象的赞
    public function addLikesToArticle($articleArr)
    {
        //目标印象全部数据
        $targetAllData=$articleArr[0];

        //目标印象id数组
        $targetId=$articleArr[1];

        foreach ($targetId as $oneId)
        {
            //取出日期
            //确定是哪张表
            $year=date('Y',substr($oneId,0,10));

            //从对应的表中拿出赞
            $res=DB::connection($this->db)->table("community_article_like_{$year}")->where([
                'aid'=>$oneId,
                'isLike'=>1,
            ])->orderBy('unixTime','desc')->get()->toArray();

            foreach ($targetAllData as &$one)
            {
                if ($one['aid']!=$oneId) continue;

                $one['likes']['theLast']=jsonDecode(jsonEncode($res));
                $one['likes']['total']=count(jsonDecode(jsonEncode($res)));
            }
            unset($one);
        }

        return [$targetAllData,$targetId];
    }

    //返回每条印象的回复
    public function addCommentsToArticle($articleArr)
    {
        //目标印象全部数据
        $targetAllData=$articleArr[0];

        //目标印象id数组
        $targetId=$articleArr[1];

        foreach ($targetId as $oneId)
        {
            //取出日期
            //确定是哪张表
            $year=date('Y',substr($oneId,0,10));

            //从对应的表中拿出回复
            $res=DB::connection($this->db)->table("community_article_comment_{$year}")->where([
                'aid'=>$oneId,
                'isShow'=>1,
            ])->orderBy('unixTime','desc')->get()->toArray();

            foreach ($targetAllData as &$one)
            {
                if ($one['aid']!=$oneId) continue;

                $one['comments']['theLast']=jsonDecode(jsonEncode($res));
                $one['comments']['total']=count(jsonDecode(jsonEncode($res)));
            }
            unset($one);
        }

        return [$targetAllData,$targetId];
    }

    //排序
    public function sortArticle($articleArr)
    {
        //印象的赞取最新7个
        //印象的评论取最新5个

        //目标印象全部数据
        $targetAllData=$articleArr[0];

        //目标印象id数组
        $targetId=$articleArr[1];

        if (empty($targetId)) return [$targetAllData,$targetId];

        //给印象加发布者头像和名字
        foreach ($targetAllData as &$one)
        {
            $one['uName']=(string)Redis::connection('UserInfo')->hget($one['uid'],'name');
            $one['uAvatar']=(string)Redis::connection('UserInfo')->hget($one['uid'],'avatar');

            if ($one['uName']=='')
            {
                $one['uName']='网友'.str_random(6);
            }
            if ($one['uAvatar']=='')
            {
                $one['uAvatar']='/imgModel/systemAvtar.png';
            }
        }
        unset($one);

        //处理赞
        foreach ($targetAllData as &$one)
        {
            if (empty($one['likes']['theLast'])) continue;

            $one['likes']['theLast']=array_slice(arraySort1($one['likes']['theLast'],['desc','unixTime']),0,7);

            //取出头像和用户名
            foreach ($one['likes']['theLast'] as &$oneTarget)
            {
                $avatarStr=Redis::connection('UserInfo')->hget($oneTarget['uid'],'avatar');

                if ($avatarStr)
                {
                    $oneTarget['avatar']=$avatarStr;

                }else
                {
                    $oneTarget['avatar']='/imgModel/systemAvtar.png';
                }

                $oneTarget['uidName']=(string)Redis::connection('UserInfo')->hget($oneTarget['uid'],'name');
                $oneTarget['tidName']=(string)Redis::connection('UserInfo')->hget($oneTarget['tid'],'name');
            }
            unset($oneTarget);
        }
        unset($one);

        //处理评论
        foreach ($targetAllData as &$one)
        {
            if (empty($one['comments']['theLast'])) continue;

            $one['comments']['theLast']=array_slice(arraySort1($one['comments']['theLast'],['desc','unixTime']),0,5);
        }
        unset($one);

        $targetAllData=arraySort1($targetAllData,['desc','unixTime']);

        //unixTime变成多少分钟前
        foreach ($targetAllData as &$one)
        {
            $one['dateTime']=formatDate($one['unixTime']);
        }
        unset($one);

        //处理标签
        foreach ($targetAllData as &$one)
        {
            $one['labels']=$this->addLabelsToArticle($one['aid']);
        }
        unset($one);

        return [$targetAllData,$targetId];
    }

    //返回印象中标签的中文名
    public function addLabelsToArticle($articleId)
    {
        //取出时间
        $suffix=date('Y',substr($articleId,0,10));

        $sql="select id,labelContent from community_label where id in (select labelId from community_article_label_{$suffix} where aid='{$articleId}') order by id";

        $res=DB::connection($this->db)->select($sql);

        return $res;
    }

    //给印象点赞或取消赞
    public function likeAndDontLike(Request $request)
    {
        //印象id
        $aid=trim($request->aid);

        //点赞人的id
        $uid=trim($request->uid);

        //印象发布者的id
        $tid=trim($request->tid);

        if ($aid=='' || empty($aid)) return response()->json(['resCode'=>Config::get('resCode.604')]);
        if (!is_numeric($uid) || $uid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);
        if (!is_numeric($tid) || $tid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);

        //取出时间
        $suffix=date('Y',substr($aid,0,10));

        LikesModel::suffix($suffix);

        $res=LikesModel::firstOrNew(['aid'=>$aid,'uid'=>$uid]);

        if ($res->isLike===1)
        {
            //取消赞
            $res->isLike=0;
            $res->isRead=0;

        }elseif ($res->isLike===0)
        {
            //恢复点赞
            $res->isLike=1;
            $res->isRead=0;

        }else
        {
            //第一次点赞
            $res->isLike=1;
            $res->tid=$tid;
        }

        try
        {
            $res->unixTime=time();
            $res->save();

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.671')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //获取印象的所有点赞人
    public function getArticleAllLike(Request $request)
    {
        $aid=trim($request->aid);
        $uid=trim($request->uid);

        if ($aid=='' || empty($aid)) return response()->json(['resCode'=>Config::get('resCode.604')]);
        if (!is_numeric($uid) || $uid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);

        //获取当前印象的所有点赞者
        $suffix=date('Y',substr($aid,0,10));

        LikesModel::suffix($suffix);

        $res=LikesModel::where(['aid'=>$aid,'isLike'=>1])->get(['uid','unixTime'])->toArray();

        //是空就直接返回
        if (empty($res)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>[]]);

        //从redis中获取粉丝关系，当前印象的点赞者与uid的关系
        //0：双方都没关注对方
        //1：我关注他，他没关注我
        //2：他关注我，我没关注他
        //3：相互关注

        $data=[];

        foreach ($res as $oneUid)
        {
            $tid=$oneUid['uid'];
            $unixTime=$oneUid['unixTime'];

            //不包括自己
            if ($tid==$uid) continue;

            //我关注他没
            if (Redis::connection('CommunityInfo')->zscore('follower_'.$uid,$tid)!=null)
            {
                //我关注他了
                $followerNum=1;

            }else
            {
                //我没关注他
                $followerNum=0;
            }

            //他关注我没
            if (Redis::connection('CommunityInfo')->zscore('fans_'.$uid,$tid)!=null)
            {
                //他关注我了
                $fansNum=2;

            }else
            {
                //他没关注我
                $fansNum=0;
            }

            //获取头像和用户名
            $avatarStr=Redis::connection('UserInfo')->hget($tid,'avatar');

            if ($avatarStr)
            {
                $avatar=$avatarStr;

            }else
            {
                $avatar='/imgModel/systemAvtar.png';
            }

            $name=(string)Redis::connection('UserInfo')->hget($tid,'name');

            $data[]=['uid'=>$tid,'avatar'=>$avatar,'name'=>$name,'relation'=>$followerNum+$fansNum,'unixTime'=>$unixTime,'dateTime'=>formatDate($unixTime)];
        }

        $data=arraySort1($data,['desc','unixTime']);

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$data]);
    }

    //获取印象的所有评论
    public function getArticleAllComment(Request $request)
    {
        $aid=trim($request->aid);
        $uid=trim($request->uid);
        $page=trim($request->page);

        if ($aid=='' || empty($aid)) return response()->json(['resCode'=>Config::get('resCode.604')]);
        if (!is_numeric($uid) || $uid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);
        if (!is_numeric($page) || $page < 1) $page=1;

        //每次取10条
        $limit=10;

        $offset=($page-1)*$limit;

        //获取当前印象的所有点赞者
        $suffix=date('Y',substr($aid,0,10));

        CommentsModel::suffix($suffix);

        //分页取评论，自己未过审的可以看见
        $res=CommentsModel::where(['aid'=>$aid,'isShow'=>1])->orWhere(function ($query) use ($aid,$uid) {
            $query->where(['aid'=>$aid,'uid'=>$uid,'isShow'=>0]);
        })->limit($limit)->offset($offset)->orderBy('id','desc')->get([
            'aid','uid','tid','isShowTargetName','comment','unixTime',
        ])->toArray();

        //是空就直接返回
        if (empty($res)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res]);

        //整理数组
        foreach ($res as &$oneComment)
        {
            //$b=$a??$c;相当于$b=isset($a)?$a:$c;
            //$b=$a?$a:$c则是$b=!empty($a)?$a:$c;

            //用户名
            $oneComment['uName']=(string)Redis::connection('UserInfo')->hget($oneComment['uid'],'name');
            $oneComment['tName']=(string)Redis::connection('UserInfo')->hget($oneComment['tid'],'name');

            $oneComment['uName']=$oneComment['uName']?$oneComment['uName']:'网友'.str_random(6);
            $oneComment['tName']=$oneComment['tName']?$oneComment['tName']:'网友'.str_random(6);

            //头像
            $oneComment['uAvatar']=(string)Redis::connection('UserInfo')->hget($oneComment['uid'],'avatar');
            $oneComment['tAvatar']=(string)Redis::connection('UserInfo')->hget($oneComment['tid'],'avatar');

            $oneComment['uAvatar']=$oneComment['uAvatar']?$oneComment['uAvatar']:'/imgModel/systemAvtar.png';
            $oneComment['tAvatar']=$oneComment['tAvatar']?$oneComment['tAvatar']:'/imgModel/systemAvtar.png';

            //处理时间
            $oneComment['dateTime']=formatDate($oneComment['unixTime']);
        }
        unset($oneComment);

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res]);
    }

    //发表评论
    public function createComment(Request $request)
    {
        $aid=trim($request->aid);
        $uid=trim($request->uid);
        $tid=trim($request->tid);
        $comment=trim($request->comment);
        $isShowTargetName=1;

        //从印象id中取得评论表后缀
        $suffix=date('Y',substr($aid,0,10));

        //印象id不能是空
        if ($aid=='' || empty($aid)) return response()->json(['resCode'=>Config::get('resCode.604')]);

        //发布评论人id不能是空
        if (!is_numeric($uid) || $uid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);
        if (!is_numeric($tid) || $tid < 1)
        {
            $isShowTargetName=0;
            ArticleModel::suffix($suffix);
            $tid=ArticleModel::where('aid',$aid)->first()->uid;
        }

        //评论内容不能是空
        if ($comment=='') return response()->json(['resCode'=>Config::get('resCode.672')]);

        //内容检测？
        $check=(new ContentCheckBase())->check($comment);

        //评论内容不合法
        if ($check!=null) return response()->json(['resCode'=>Config::get('resCode.661')]);

        //以下开始发表评论==============================================================================

        CommentsModel::suffix($suffix);

        try
        {
            CommentsModel::create([
                'aid'=>$aid,
                'uid'=>$uid,
                'tid'=>$tid,
                'isShow'=>1,
                'isShowTargetName'=>$isShowTargetName,
                'comment'=>$comment,
                'unixTime'=>time(),
            ]);

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.673')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }


















}