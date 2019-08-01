<?php

namespace App\Http\Controllers\QuanMinZhanLing\Community;

use App\Http\Controllers\QuanMinZhanLing\BaseController;
use App\Http\Controllers\Server\ContentCheckBase;
use App\Http\Controllers\Server\StoreVideoBase;
use App\Model\Community\ArticleModel;
use App\Model\Community\LabelModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
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
            //所以按季度分表
            $suffix=$now->year.'_'.$now->quarter;

            $table="community_{$tableType}_{$suffix}";

            if (!Schema::connection($db)->hasTable($table))
            {
                //印象-获赞
                Schema::connection($db)->create($table, function (Blueprint $table)
                {
                    $table->string('aid',20)->comment('真正的印象主键，10位的unixTime加上6位随机字符串');
                    $table->integer('uid')->unsigned()->comment('用户主键，谁给这条印象点赞了');
                    $table->tinyInteger('isLike')->unsigned()->comment('是否点赞，1是点赞了，0是没点赞');
                    $table->integer('unixTime')->unsigned()->comment('排序用的时间');
                    $table->timestamps();
                    $table->primary('aid');
                    $table->index('uid');
                });
            }
        }

        if ($tableType=='article_comment')
        {
            //印象-评论的关系表，如果每天1万个印象，每个印象200个评论，一年7亿3千万条记录，每季度1亿8千万条记录
            //所以按季度分表
            $suffix=$now->year.'_'.$now->quarter;

            $table="community_{$tableType}_{$suffix}";

            if (!Schema::connection($db)->hasTable($table))
            {
                //印象-评论
                Schema::connection($db)->create($table, function (Blueprint $table)
                {
                    $table->string('aid',20)->comment('真正的印象主键，10位的unixTime加上6位随机字符串');
                    $table->integer('uid')->unsigned()->comment('用户主键，谁给这条印象评论了');
                    $table->integer('tid')->unsigned()->comment('用户主键，这条评论是给谁的');
                    $table->tinyInteger('isShow')->unsigned()->default(1)->comment('是否可以显示，1是可以，0是不可以');
                    $table->tinyInteger('isRead')->unsigned()->default(0)->comment('目标用户是否已读，1是已读，0是未读');
                    $table->text('comment')->nullable()->comment('评论内容');
                    $table->integer('unixTime')->unsigned()->comment('排序用的时间');
                    $table->timestamps();
                    $table->primary('aid');
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
                $res=$this->storePic(base64_decode($one),$num,$articlePrimary);

                if (!is_array($res))
                {
                    return response()->json(['resCode'=>Config::get('resCode.650')]);
                }

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
            DB::connection('communityDB')->beginTransaction();

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

            DB::connection('communityDB')->table("community_article_label_{$suffix}")->insert($data);

            //更新标签使用次数
            $labels=implode(',',$labels);

            $sql="update community_label set useTotal=useTotal+1 where id in ({$labels})";

            DB::connection('communityDB')->update($sql);

        }catch (\Exception $e)
        {
            DB::connection('communityDB')->rollBack();

            return response()->json(['resCode'=>Config::get('resCode.660')]);
        }

        DB::connection('communityDB')->commit();

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
            $res=DB::connection('communityDB')->select($sql);

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

                    if (is_numeric($one))
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
            $res=LabelModel::where(['labelContent'=>$labelContent])->first();

            //存在的不能插入
            if ($res!=null) return response()->json(['resCode'=>Config::get('resCode.669')]);

            $check=(new ContentCheckBase())->check($labelContent);

            if ($check!=null) return response()->json(['resCode'=>Config::get('resCode.670')]);

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

        //该格子下今年的最热标签，取前4，在印象-标签关系表中
        $now=Carbon::now();

        $sql="select labelId,labelContent,count(1) as useTotal from community_article_label_{$now->year} as t1 left join community_label as t2 on t1.labelId=t2.id where gName='{$gName}' group by labelId order by useTotal desc limit 4;";

        //可以直接返回
        $hotLabels=DB::connection($this->db)->select($sql);

        //取出该格子置顶的，格子主人最后一条，加精的印象

        //只有第一页才组合这三个
        if ($page===1)
        {
            //置顶
            $onTop=$this->getOnTopArticle($gName);

            //格子主人最后一条
            $gridOwners=$this->getGridOwnersLastArticle($gName,$uid);

            //加精
            $theBest=$this->getTheBestArticle($gName);
        }

        //其他数据
        $suffix=$now->year;
        $commData=[];

        for ()
        $commData=$this->getArticleByPaginate($suffix,$gName,$uid,$page);


        dd($commData);





        dd($hotLabels,$onTop,$gridOwners,$theBest);




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

            $aid=array_flatten($aid);
        }

        return [$res,$aid];
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

        $aid=array_flatten($aid);

        return [$res,$aid];
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

            $aid=array_flatten($aid);
        }

        return [$res,$aid];
    }

    //分页返回一个格子下的印象
    public function getArticleByPaginate($suffix,$gName,$uid,$page,$paginate=3)
    {
        $res=[];

        $offset=($page-1)*$paginate;

        $table="community_article_{$suffix}";

        //找不到表，说明没数据了
        if (!Schema::connection($this->db)->hasTable($table)) return [$res,'tryNextYear'=>0];

        //找到表了
        ArticleModel::suffix($suffix);

        //查询
        $res=ArticleModel::where(['gName'=>$gName,'isShow'=>1])
            ->orWhere(function ($query) use ($gName,$uid)
            {
                //查询者自己可以看到自己未审核通过的印象
                $query->where(['uid'=>$uid,'gName'=>$gName,'isShow'=>0]);
            })
            ->orderBy('unixTime','desc')
            ->limit($paginate)
            ->offset($offset)
            ->get()->toArray();

        return [$res,'tryNextYear'=>1];
    }






}