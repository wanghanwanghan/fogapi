<?php

namespace App\Http\Controllers\QuanMinZhanLing\Community;

use App\Http\Controllers\QuanMinZhanLing\BaseController;
use App\Http\Controllers\QuanMinZhanLing\UserController;
use App\Http\Controllers\Server\ContentCheckBase;
use App\Http\Controllers\Server\StoreVideoBase;
use App\Http\Controllers\TanSuoShiJie\FogController;
use App\Model\Community\ArticleLabelModel;
use App\Model\Community\ArticleModel;
use App\Model\Community\CommentsModel;
use App\Model\Community\LabelForPeopleModel;
use App\Model\Community\LabelModel;
use App\Model\Community\LikesModel;
use App\Model\Community\PeopleLabelModel;
use App\Model\Community\PrivateMailModel;
use App\Model\Community\RubbishModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
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

        if ($tableType=='label_for_people')
        {
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

        if ($tableType=='people_label')
        {
            $table="community_{$tableType}";

            if (!Schema::connection($db)->hasTable($table))
            {
                //用户-标签
                Schema::connection($db)->create($table, function (Blueprint $table)
                {
                    $table->integer('uid')->unsigned()->comment('用户主键');
                    $table->integer('labelId')->unsigned()->comment('标签主键');
                    $table->timestamps();
                    $table->primary(['uid','labelId']);
                });

                //重建主键
                //DB::connection($db)->statement("Alter table {$table} drop primary key,add primary key (`id`,`uid`)");

                //添加分区
                DB::connection($db)->statement("Alter table {$table} partition by linear key(`uid`) partitions 8");
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
                    $table->integer('tid')->unsigned()->comment('用户主键，赞是给谁的');
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
                    $table->integer('oid')->unsigned()->comment('用户主键，印象的拥有者');
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
                    $table->index('oid');
                    $table->index('uid');
                    $table->index('tid');
                });
            }
        }

        if ($tableType=='rubbish')
        {
            $table="community_{$tableType}";

            if (!Schema::connection($db)->hasTable($table))
            {
                Schema::connection($db)->create($table, function (Blueprint $table)
                {
                    $table->increments('id')->unsigned()->comment('主键');
                    $table->string('aid',20)->comment('主键');
                    $table->integer('uid')->unsigned()->comment('举报人');
                    $table->integer('tid')->unsigned()->comment('被举报人');
                    $table->tinyInteger('type')->unsigned()->default(1)->comment('举报类型，1是举报印象，2是...3是...');
                    $table->integer('unixTime')->unsigned()->comment('排序用的时间');
                    $table->timestamps();
                });
            }
        }

        if ($tableType=='private_mail')
        {
            //私信表，根据uid + tid分5个表
            $suffix=[0,1,2,3,4];

            $table="community_{$tableType}_{$suffix[0]}";

            if (!Schema::connection($db)->hasTable($table))
            {
                foreach ($suffix as $one)
                {
                    $table="community_{$tableType}_{$suffix[$one]}";

                    //私信表
                    Schema::connection($db)->create($table, function (Blueprint $table)
                    {
                        $table->increments('id')->unsigned()->comment('主键');
                        $table->integer('uid')->unsigned()->comment('发送者主键');
                        $table->integer('tid')->unsigned()->comment('接收者主键');
                        $table->tinyInteger('isRead')->unsigned()->default(0)->comment('是否已读');
                        $table->string('content')->nullable()->comment('内容');
                        $table->integer('unixTime')->unsigned()->nullable()->comment('排序用的时间');
                        $table->timestamps();
                        $table->index('uid');
                        $table->index('tid');
                        $table->index('unixTime');
                    });
                }
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

        }elseif ($request->picArr!='' && is_array(jsonDecode($request->picArr)) && !empty(jsonDecode($request->picArr)))
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

        //记录一下这个用户一共发布了几条印象
        Redis::connection('UserInfo')->hincrby('ZzZzZzZzZzZzZzZz','CommunityArticleTotal',1);

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

        //可以通过标签筛选
        $label=trim($request->label);

        if (!is_numeric($label) || $label < 1)
        {
            //代表查看全部标签
            $label=0;
        }

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
            $onTop=$this->getOnTopArticle($gName,$label);

            //格子主人最后一条
            $gridOwners=$this->getGridOwnersLastArticle($gName,$uid,$label);

            //加精
            $theBest=$this->getTheBestArticle($gName,$label);
        }

        //分页取出除去置顶加精格主最后一条的印象
        $commData=$this->getArticleByPaginate($gName,$uid,$label,$page);

        //给所有印象加赞和评论
        if (isset($onTop))
        {
            $onTop=$this->addLikesToArticle($onTop);
            $onTop=$this->addCommentsToArticle($onTop);

            //排序
            $onTop=$this->sortArticle($onTop,$uid);

        }else
        {
            $onTop=[[],[]];
        }
        if (isset($gridOwners))
        {
            $gridOwners=$this->addLikesToArticle($gridOwners);
            $gridOwners=$this->addCommentsToArticle($gridOwners);

            //排序
            $gridOwners=$this->sortArticle($gridOwners,$uid);

        }else
        {
            $gridOwners=[[],[]];
        }
        if (isset($theBest))
        {
            $theBest=$this->addLikesToArticle($theBest);
            $theBest=$this->addCommentsToArticle($theBest);

            //排序
            $theBest=$this->sortArticle($theBest,$uid);

        }else
        {
            $theBest=[[],[]];
        }
        if (!empty($commData[0]))
        {
            $commData=$this->addLikesToArticle($commData);
            $commData=$this->addCommentsToArticle($commData);

            //排序
            $commData=$this->sortArticle($commData,$uid);

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
    public function getOnTopArticle($gName,$label)
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

            if ($label!=0)
            {
                $sql="select * from community_article_{$suffix} as t1 LEFT JOIN community_article_label_{$suffix} as t2 on t1.aid = t2.aid where t1.gName='{$gName}' and t1.isTop>0 and labelId={$label} and isShow=1;";

                $art=DB::connection($this->db)->select($sql);

                $art=jsonDecode(jsonEncode($art));

            }else
            {
                $art=ArticleModel::where(['gName'=>$gName,'isShow'=>1])->where('isTop','>',0)->get()->toArray();
            }

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
    public function getGridOwnersLastArticle($gName,$uid,$label)
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

        if ($label!=0 && !empty(current($res)))
        {
            $w=current($res);

            $suffix=date('Y',substr($w['aid'],0,10));

            ArticleLabelModel::suffix($suffix);

            $r=ArticleLabelModel::where(['aid'=>$w['aid'],'labelId'=>$label])->first();

            if ($r==null) return [[],[]];
        }

        return [$res,array_flatten($aid)];
    }

    //返回一个格子下的所有加精印象
    public function getTheBestArticle($gName,$label)
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

            if ($label!=0)
            {
                $sql="select * from community_article_{$suffix} as t1 LEFT JOIN community_article_label_{$suffix} as t2 on t1.aid = t2.aid where t1.gName='{$gName}' and t1.theBest>0 and labelId={$label} and isShow=1;";

                $art=DB::connection($this->db)->select($sql);

                $art=jsonDecode(jsonEncode($art));

            }else
            {
                $art=ArticleModel::where(['gName'=>$gName,'isShow'=>1])->where('theBest','>',0)->get()->toArray();
            }

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
    public function getArticleByPaginate($gName,$uid,$label,$page,$paginate=3)
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

        //union出来的临时表没有索引
        if ($label!=0)
        {
            //先从community_article_label中过滤出aid
            $sql='';
            foreach ($tableTarget as $oneTable)
            {
                $oneTable=str_replace('article','article_label',$oneTable);
                $sql.=" union select * from {$oneTable}";
            }

            $sql=trim(ltrim(trim($sql),'union'));

            $reslSql="select aid from ({$sql}) as tmp where gName='{$gName}' and labelId={$label} order by unixTime desc limit {$offset},{$paginate}";

            $res=DB::connection($this->db)->select($reslSql);

            if (empty($res)) return [jsonDecode(jsonEncode($res)),$aid];

            //循环取印象详情
            foreach ($res as $oneAid)
            {
                $suffix=date('Y',substr($oneAid->aid,0,10));

                ArticleModel::suffix($suffix);

                $w=ArticleModel::where(['aid'=>$oneAid->aid,'isTop'=>0,'theBest'=>0,'isShow'=>1])
                    ->orWhere(function ($query) use ($oneAid,$uid) {
                        $query->where(['aid'=>$oneAid->aid,'isTop'=>0,'theBest'=>0,'isShow'=>0,'uid'=>$uid]);
                    })->first();

                if (empty($w))
                {
                    $tmp[]=[];
                }else
                {
                    $tmp[]=$w->toArray();
                }
            }

            $res=array_filter($tmp);

        }else
        {
            $sql='';
            foreach ($tableTarget as $oneTable)
            {
                $sql.=" union select * from {$oneTable}";
            }

            $sql=trim(ltrim(trim($sql),'union'));

            $reslSql="select * from ({$sql}) as tmp where gName='{$gName}' and isTop=0 and theBest=0 and ((isShow=1) or (isShow=0 and uid={$uid})) order by unixTime desc limit {$offset},{$paginate}";

            $res=DB::connection($this->db)->select($reslSql);
        }

        if (empty($res)) return [jsonDecode(jsonEncode($res)),$aid];

        //除去格子主人最后一条
        $gridOwnersLastArticle=$this->getGridOwnersLastArticle($gName,$uid,$label);

        //取出aid
        foreach ($res as $val)
        {
            if (is_object($val))
            {
                $aid[]=$val->aid;
            }else
            {
                $aid[]=$val['aid'];
            }
        }

        if (empty($gridOwnersLastArticle[1])) return [jsonDecode(jsonEncode($res)),array_flatten($aid)];

        $tmp=[];
        foreach ($res as $oneArticle)
        {
            if (is_object($oneArticle))
            {
                if ($oneArticle->aid==current($gridOwnersLastArticle[1])) continue;

            }else
            {
                if ($oneArticle['aid']==current($gridOwnersLastArticle[1])) continue;
            }

            $tmp[]=$oneArticle;
        }

        //取出aid
        foreach ($tmp as $val)
        {
            if (is_object($val))
            {
                $aid[]=$val->aid;
            }else
            {
                $aid[]=$val['aid'];
            }
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
    public function sortArticle($articleArr,$uid)
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

            //查看传入的uid点没点赞
            foreach ($one['likes']['theLast'] as $oneTarget)
            {
                if ($oneTarget['uid']==$uid) $iLike=1;
            }

            (isset($iLike) && $iLike===1) ? $one['likes']['iLike']=1 : $one['likes']['iLike']=0;

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

                if ($oneTarget['uidName']=='')
                {
                    $oneTarget['uidName']='网友'.str_random(6);
                }

                if ($oneTarget['tidName']=='')
                {
                    $oneTarget['tidName']='网友'.str_random(6);
                }
            }
            unset($oneTarget);
        }
        unset($one);

        //处理评论
        foreach ($targetAllData as &$one)
        {
            if (empty($one['comments']['theLast'])) continue;

            $one['comments']['theLast']=array_slice(arraySort1($one['comments']['theLast'],['desc','unixTime']),0,5);

            //取出头像和用户名
            foreach ($one['comments']['theLast'] as &$oneTarget)
            {
                $oneTarget['uidName']=(string)Redis::connection('UserInfo')->hget($oneTarget['uid'],'name');
                $oneTarget['tidName']=(string)Redis::connection('UserInfo')->hget($oneTarget['tid'],'name');

                if ($oneTarget['uidName']=='')
                {
                    $oneTarget['uidName']='网友'.str_random(6);
                }

                if ($oneTarget['tidName']=='')
                {
                    $oneTarget['tidName']='网友'.str_random(6);
                }
            }
            unset($oneTarget);
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

            //给印象加分
            $this->setCommunityScore('like',$uid,$aid);

        }else
        {
            //第一次点赞
            $res->isLike=1;
            $res->tid=$tid;

            //给印象加分
            $this->setCommunityScore('like',$uid,$aid);
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
        //4：自己？

        $data=[];

        foreach ($res as $oneUid)
        {
            $tid=$oneUid['uid'];
            $unixTime=$oneUid['unixTime'];

            //我关注他没
            Redis::connection('CommunityInfo')->zscore('follower_'.$uid,$tid)!=null ? $followerNum=1 : $followerNum=0;

            //他关注我没
            Redis::connection('CommunityInfo')->zscore('fans_'.$uid,$tid)!=null ? $fansNum=2 : $fansNum=0;

            //获取头像和用户名
            $avatarStr=Redis::connection('UserInfo')->hget($tid,'avatar');

            $avatarStr=='' ? $avatar='/imgModel/systemAvtar.png' : $avatar=$avatarStr;

            $name=(string)Redis::connection('UserInfo')->hget($tid,'name');

            $name=='' ? $name='网友'.str_random(6) : null;

            //自己
            $tid==$uid ? $relation=4 : $relation=$followerNum + $fansNum;

            $data[]=['uid'=>$tid,'avatar'=>$avatar,'name'=>$name,'relation'=>$relation,'unixTime'=>$unixTime,'dateTime'=>formatDate($unixTime)];
        }

        //$data是空说明没人点赞，或者自己点赞自己的印象
        if (empty($data)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>[]]);

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
        ArticleModel::suffix($suffix);
        CommentsModel::suffix($suffix);

        try
        {
            CommentsModel::create([
                'aid'=>$aid,
                'oid'=>ArticleModel::where('aid',$aid)->first()->uid,
                'uid'=>$uid,
                'tid'=>$tid,
                'isShow'=>1,
                'isShowTargetName'=>$isShowTargetName,
                'comment'=>$comment,
                'unixTime'=>time(),
            ]);

            //给印象加分
            $this->setCommunityScore('comment',$uid,$aid);

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.673')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //举报
    public function rubbish(Request $request)
    {
        $aid=trim($request->aid);
        $uid=trim($request->uid);
        $tid=trim($request->tid);
        $type=trim($request->type);

        //印象id不能是空
        if ($aid=='' || empty($aid)) return response()->json(['resCode'=>Config::get('resCode.604')]);

        //举报人和被举报人id不能是空
        if (!is_numeric($uid) || $uid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);
        if (!is_numeric($tid) || $tid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);

        //举报类型不能是空
        if (!is_numeric($type) || $type < 1) $type=1;

        $this->createTable('rubbish');

        RubbishModel::create(['aid'=>$aid,'uid'=>$uid,'tid'=>$tid,'type'=>$type,'unixTime'=>time()]);

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //获取描述用户的的标签，也就是用户有哪些标签
    public function getPeopleLabels($uid)
    {
        $res=PeopleLabelModel::with('peopleLabelName')->where('uid',$uid)->get(['uid','labelId'])->toArray();

        foreach ($res as &$one)
        {
            $one['labelContent']=$one['people_label_name']['labelContent'];
            unset($one['people_label_name']);
        }
        unset($one);

        return $res;
    }

    //获取用户得到多少个赞
    public function getPeopleLikes($uid)
    {
        $res=Cache::remember("func_getPeopleLikes_cache_{$uid}",5,function() use ($uid)
        {
            $count=0;

            $tableTarget=[];

            for ($i=0;$i<100;$i++)
            {
                $suffix=Carbon::now()->year - $i;

                $table="community_article_like_{$suffix}";

                if (!Schema::connection($this->db)->hasTable($table)) break;

                $tableTarget[]=$table;
            }

            foreach ($tableTarget as $oneTable)
            {
                $count+=DB::connection($this->db)->table($oneTable)->where('tid',$uid)->count();
            }

            return $count;
        });

        return $res;
    }

    //获取用户印象和印象数，分页显示
    public function getArticleByUid($uid,$label,$page,$paginate=5)
    {
        $res=[];
        $aid=[];

        $now=Carbon::now();

        $offset=($page-1)*$paginate;

        $tableTarget=[];
        $totle=(int)Redis::connection('UserInfo')->hget($uid,'CommunityArticleTotal');

        //只取得最近4年的？此处留坑
        for ($i=0;$i<4;$i++)
        {
            $suffix=$now->year - $i;

            $table="community_article_{$suffix}";

            if (!Schema::connection($this->db)->hasTable($table)) continue;

            $tableTarget[]=$table;
        }

        //整理sql
        if (empty($tableTarget)) return [$res,$aid,$totle];

        //union出来的临时表没有索引
        $sql='';
        foreach ($tableTarget as $oneTable)
        {
            $sql.=" union select * from {$oneTable}";
        }

        $sql=trim(ltrim(trim($sql),'union'));

        $reslSql="select * from ({$sql}) as tmp where uid={$uid} order by unixTime desc limit {$offset},{$paginate}";

        $res=DB::connection($this->db)->select($reslSql);

        if (empty($res)) return [jsonDecode(jsonEncode($res)),$aid,$totle];

        //取出aid
        foreach ($res as $val)
        {
            if (is_object($val))
            {
                $aid[]=$val->aid;
            }else
            {
                $aid[]=$val['aid'];
            }
        }

        return [jsonDecode(jsonEncode($res)),array_flatten($aid),$totle];
    }

    //获取用户有多少个迷雾点
    public function getPeopleArea($uid)
    {
        $suffix=(new FogController())->getDatabaseNoOrTableNo($uid);

        $res=Cache::remember("func_getPeopleArea_cache_{$uid}",5,function() use ($uid,$suffix)
        {
            return DB::connection("TssjFog{$suffix['db']}")->table("user_fog_{$suffix['table']}")->where('uid',$uid)->count();
        });

        return $res;
    }

    //获取用户关注数、粉丝数和详情
    public function getPeopleFollowerFansAndDetail($uid,$page=0)
    {
        //获取集合成员数
        $followerNum=(int)Redis::connection('CommunityInfo')->zcard('follower_'.$uid);
        $fansNum=(int)Redis::connection('CommunityInfo')->zcard('fans_'.$uid);

        if ($page==0)
        {
            return ['followerNum'=>$followerNum,'fansNum'=>$fansNum];
        }

        $follower=Redis::connection('CommunityInfo')->zrevrange('follower_'.$uid,0,-1,'withscores');
        $fans=Redis::connection('CommunityInfo')->zrevrange('fans_'.$uid,0,-1,'withscores');

        $followerInfo=[];
        $fansInfo=[];

        foreach ($follower as $key=>$value)
        {
            //key是uid，value是关注时间time()

            $uName=Redis::connection('UserInfo')->hget($key,'name');
            $uAvatar=Redis::connection('UserInfo')->hget($key,'avatar');

            if ($uName==null) $uName='网友'.str_random(6);
            if ($uAvatar==null) $uAvatar='/imgModel/systemAvtar.png';

            //我关注他了
            $followerType=1;

            //他关注我没
            if (Redis::connection('CommunityInfo')->zscore('fans_'.$uid,$key)!=null)
            {
                //他关注我了
                $fansType=2;

            }else
            {
                //他没关注我
                $fansType=0;
            }

            $followerInfo[]=[
                'uid'=>$key,
                'uName'=>$uName,
                'uAvatar'=>$uAvatar,
                'level'=>(int)Redis::connection('UserInfo')->hget($key,'level'),
                'fans'=>(int)Redis::connection('CommunityInfo')->zcard('fans_'.$key),
                'CommunityArticle'=>(int)Redis::connection('UserInfo')->hget($key,'CommunityArticleTotal'),
                'relation'=>$followerType+$fansType
            ];
        }

        foreach ($fans as $key=>$value)
        {
            //key是uid，value是关注时间time()

            $uName=Redis::connection('UserInfo')->hget($key,'name');
            $uAvatar=Redis::connection('UserInfo')->hget($key,'avatar');

            if ($uName==null) $uName='网友'.str_random(6);
            if ($uAvatar==null) $uAvatar='/imgModel/systemAvtar.png';

            //我关注他没
            if (Redis::connection('CommunityInfo')->zscore('follower_'.$uid,$key)!=null)
            {
                //我关注他了
                $followerType=1;

            }else
            {
                //我没关注他
                $followerType=0;
            }

            //他关注我了
            $fansType=2;

            $fansInfo[]=[
                'uid'=>$key,
                'uName'=>$uName,
                'uAvatar'=>$uAvatar,
                'level'=>(int)Redis::connection('UserInfo')->hget($key,'level'),
                'fans'=>(int)Redis::connection('CommunityInfo')->zcard('fans_'.$key),
                'CommunityArticle'=>(int)Redis::connection('UserInfo')->hget($key,'CommunityArticleTotal'),
                'relation'=>$followerType+$fansType
            ];
        }

        return ['followerNum'=>$followerNum,'fansNum'=>$fansNum,'followerInfo'=>$followerInfo,'fansInfo'=>$fansInfo];
    }

    //打开用户个人页面
    public function getUserPage(Request $request)
    {
        $uid=trim($request->uid);

        if (!is_numeric($uid) || $uid <= 0) return response()->json(['resCode'=>Config::get('resCode.601')]);

        $tid=trim($request->tid);

        if (!is_numeric($tid) || $tid <= 0) return response()->json(['resCode'=>Config::get('resCode.601')]);

        $page=(int)trim($request->page);

        $page < 1 ? $page=1 : null;

        //交换两个变量
        list($tid,$uid)=[$uid,$tid];

        $uName=Redis::connection('UserInfo')->hget($uid,'name');
        $uAvatar=Redis::connection('UserInfo')->hget($uid,'avatar');

        if ($uName==null) $uName='网友'.str_random(6);
        if ($uAvatar==null) $uAvatar='/imgModel/systemAvtar.png';

        $userInfo['name']=$uName;
        $userInfo['avatar']=$uAvatar;
        $userInfo['likeTotal']=$this->getPeopleLikes($uid);

        $peopleLabel=jsonDecode(Redis::connection('UserInfo')->hget($uid,'PeopleLabels')) == null ? [] : jsonDecode(Redis::connection('UserInfo')->hget($uid,'PeopleLabels'));

        if (!empty($peopleLabel))
        {
            foreach ($peopleLabel as &$oneLabel)
            {
                $id=$oneLabel;

                $labelContent=LabelForPeopleModel::find($oneLabel)->labelContent;

                $oneLabel=['id'=>$id,'oneLabel'=>$labelContent];
            }
            unset($oneLabel);
        }

        $article=$this->getArticleByUid($uid,0,$page);
        $userInfo['communityTotal']=$article[2];
        $article=$this->addLikesToArticle($article);
        $article=$this->addCommentsToArticle($article);
        $article=$this->sortArticle($article,$uid);

        $followerFans=$this->getPeopleFollowerFansAndDetail($uid);

        //俩人关系
        if (Redis::connection('CommunityInfo')->zscore('follower_'.$tid,$uid)!=null)
        {
            //我关注他了
            $foll=1;
        }else
        {
            $foll=0;
        }
        if (Redis::connection('CommunityInfo')->zscore('fans_'.$tid,$uid)!=null)
        {
            //他关注我了
            $fans=2;
        }else
        {
            $fans=0;
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),
            'userInfo'=>$userInfo,
            'peopleLabel'=>$peopleLabel,
            'relation'=>$foll+$fans,
            'followerFans'=>$followerFans,
            'article'=>current($article)]);
    }

    //写下印象标签
    public function setUserLabel(Request $request)
    {
        $uid=trim($request->uid);

        if (!is_numeric($uid) || $uid <= 0) return response()->json(['resCode'=>Config::get('resCode.601')]);

        $label=jsonDecode($request->label);

        if (!is_array($label) || empty($label)) return response()->json(['resCode'=>Config::get('resCode.601')]);

        foreach ($label as $one)
        {
            try
            {
                if ($one=='' || empty($one) || !is_numeric($one)) continue;

                PeopleLabelModel::create(['uid'=>$uid,'labelId'=>$one]);

                $res=LabelForPeopleModel::find($one);
                $res->useTotal++;
                $res->save();

            }catch (\Exception $e)
            {

            }
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //查看所有可以对人的印象标签
    public function getUserLabel(Request $request)
    {
        $res=LabelForPeopleModel::where('labelContent','!=','amyYOEPCiph6NQr')->get()->toArray();

        return response()->json(['resCode'=>Config::get('resCode.200'),'labelContent'=>$res]);
    }

    //选择对人的印象
    public function selectUserLabel(Request $request)
    {
        //哪些可以选择
        if ($request->isMethod('get'))
        {
            $res=$this->getPeopleLabels($request->uid);

            return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>jsonDecode(jsonEncode($res))]);
        }

        //用户选择了哪些展示
        if ($request->isMethod('post'))
        {
            $uid=$request->uid;

            $labelsArr=jsonDecode($request->labels);

            Redis::connection('UserInfo')->hset($uid,'PeopleLabels',jsonEncode($labelsArr));

            return response()->json(['resCode'=>Config::get('resCode.200')]);
        }

        return true;
    }

    //关注和取消关注
    public function followerAndUnfollower(Request $request)
    {
        $uid=trim($request->uid);

        if (!is_numeric($uid) || $uid <= 0) return response()->json(['resCode'=>Config::get('resCode.601')]);

        $tid=trim($request->tid);

        if (!is_numeric($tid) || $tid <= 0) return response()->json(['resCode'=>Config::get('resCode.601')]);

        //我关注他没
        if (Redis::connection('CommunityInfo')->zscore('follower_'.$uid,$tid)!=null)
        {
            //我关注他了，那么就取消关注
            Redis::connection('CommunityInfo')->zrem('follower_'.$uid,$tid);

            //他的粉丝集合里也去掉我
            Redis::connection('CommunityInfo')->zrem('fans_'.$tid,$uid);

        }else
        {
            //我没关注他，那么就关注
            Redis::connection('CommunityInfo')->zadd('follower_'.$uid,time(),$tid);

            //他的粉丝集合里加上我
            Redis::connection('CommunityInfo')->zadd('fans_'.$tid,time(),$uid);
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //删除印象
    public function deleteArticle(Request $request)
    {
        $uid=trim($request->uid);

        if (!is_numeric($uid) || $uid <= 0) return response()->json(['resCode'=>Config::get('resCode.601')]);

        $aid=trim($request->aid);

        if (empty($aid) || !is_string($aid)) return response()->json(['resCode'=>Config::get('resCode.601')]);

        //取得年份
        $suffix=date('Y',substr($aid,0,10));

        ArticleModel::suffix($suffix);
        $res=ArticleModel::where(['aid'=>$aid,'uid'=>$uid])->first();

        if ($res===null) return response()->json(['resCode'=>Config::get('resCode.674')]);

        DB::connection($this->db)->beginTransaction();

        try
        {
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

        //记录一下这个用户一共发布了几条印象
        Redis::connection('UserInfo')->hincrby('ZzZzZzZzZzZzZzZz','CommunityArticleTotal',-1);

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //发送私信
    public function setPrivateMail(Request $request)
    {
        //发送者id
        $uid=trim($request->uid);

        //接收者id
        $tid=trim($request->tid);

        //私信内容
        $content=$request->contents;

        if (!is_numeric($uid) || $uid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);
        if (!is_numeric($tid) || $tid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);
        if ($content=='') return response()->json(['resCode'=>Config::get('resCode.604')]);

        $suffix=($uid+$tid)%5;

        PrivateMailModel::suffix($suffix);

        PrivateMailModel::create(['uid'=>$uid,'tid'=>$tid,'content'=>$content,'unixTime'=>time()]);

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //获取私信
    public function getPrivateMail(Request $request)
    {
        //发送者id
        $uid=trim($request->uid);

        //接收者id
        $tid=trim($request->tid);

        //页码
        $page=(int)trim($request->page);
        $page < 1 ? $page=1 : null;

        if (!is_numeric($uid) || $uid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);
        if (!is_numeric($tid) || $tid < 1) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $suffix=($uid+$tid)%5;

        //10%机率清理一下数据表
        if ($page===1 && random_int(1,10) > 9) $this->clearUpPrivateMailTable($uid,$tid,$suffix);

        $limit=10;
        $offset=($page-1)*$limit;

        PrivateMailModel::suffix($suffix);
        $res=PrivateMailModel::where(function ($query) use ($uid,$tid){
            $query->where(['uid'=>$uid,'tid'=>$tid])->orWhere(['uid'=>$tid,'tid'=>$uid]);
        })->orderBy('unixTime','desc')->limit($limit)->offset($offset)->get(['id','uid','tid','content','unixTime'])->toArray();

        if (empty($res)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res]);

        foreach ($res as &$oneMail)
        {
            $oneMail['uName']=Redis::connection('UserInfo')->hget($oneMail['uid'],'name');
            $oneMail['uAvatar']=Redis::connection('UserInfo')->hget($oneMail['uid'],'avatar');

            if ($oneMail['uName']==null) $oneMail['uName']='网友'.str_random(6);
            if ($oneMail['uAvatar']==null) $oneMail['uAvatar']='/imgModel/systemAvtar.png';

            $oneMail['tName']=Redis::connection('UserInfo')->hget($oneMail['tid'],'name');
            $oneMail['tAvatar']=Redis::connection('UserInfo')->hget($oneMail['tid'],'avatar');

            if ($oneMail['tName']==null) $oneMail['tName']='网友'.str_random(6);
            if ($oneMail['tAvatar']==null) $oneMail['tAvatar']='/imgModel/systemAvtar.png';
        }
        unset($oneMail);

        $res=arraySort1($res,['asc','unixTime']);

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$res]);
    }

    //删除某一个私信表，1年以前的私信
    public function clearUpPrivateMailTable($uid,$tid,$suffix)
    {
        PrivateMailModel::suffix($suffix);

        //一年前的时间
        $time=time() - 31536000 * 1;

        //我发给他的
        PrivateMailModel::where(['uid'=>$uid,'tid'=>$tid])->where('unixTime','<',$time)->delete();
        //他发给我的
        PrivateMailModel::where(['uid'=>$tid,'tid'=>$uid])->where('unixTime','<',$time)->delete();

        return true;
    }

    //查看用户消息（点赞，评论）
    public function getUserMessage(Request $request)
    {
        $uid=trim($request->uid);
        $page=(int)trim($request->page);
        $type=(int)trim($request->type);

        //type=0，刚进入这个消息页
        switch ($type)
        {
            case 0:

                //赞数
                $likeTotal=$this->getLikeTotal($uid);

                //评论数
                $commentTotal=$this->getCommentTotal($uid);

                //取我没有已读的赞
                $noReadLike=$this->getAllNoReadLike($uid);

                $data=[];

                //整理数组
                if (!empty($noReadLike))
                {
                    foreach ($noReadLike as &$one)
                    {
                        $uInfo=$this->getUserNameAndAvatar($one['uid']);

                        $one['uName']=$uInfo[0];
                        $one['uAvatar']=$uInfo[1];

                        $one['dateTime']=formatDate($one['unixTime']);

                        //然后取出pic或视频或一点内容
                        $suffix=date('Y',substr($one['aid'],0,10));

                        ArticleModel::suffix($suffix);

                        $aInfo=ArticleModel::where('aid',$one['aid'])->first();

                        $one['picOrVideo']=(string)$aInfo->picOrVideo1;
                        $one['content']=mb_substr($aInfo->content,0,8);

                        $data[]=$one;
                    }
                    unset($one);
                }

                //取我没有已读的评论
                //这块分成两部分，印象发布者可以看到自己印象下的所有评论信息isOwnersRead
                //还有就是我评论的别人印象后，别的人给我的评论回复
                //这两种数据对应的arr1和arr2
                $noReadComment=$this->getAllNoReadComment($uid);

                //整理数组
                if (!empty($noReadComment[1]))
                {
                    //如果别人评论了我的评论，会收到两条信息，去掉上面那个循环里的信息
                    $condTarget=[];

                    foreach ($noReadComment[1] as &$one)
                    {
                        $uInfo=$this->getUserNameAndAvatar($one['uid']);
                        $tInfo=$this->getUserNameAndAvatar($one['tid']);

                        $one['uName']=$uInfo[0];
                        $one['uAvatar']=$uInfo[1];
                        $one['tName']=$tInfo[0];
                        $one['tAvatar']=$tInfo[1];

                        $one['dateTime']=formatDate($one['unixTime']);

                        //然后取出pic或视频或一点内容
                        $suffix=date('Y',substr($one['aid'],0,10));

                        ArticleModel::suffix($suffix);

                        $aInfo=ArticleModel::where('aid',$one['aid'])->first();

                        $one['picOrVideo']=(string)$aInfo->picOrVideo1;
                        $one['content']=mb_substr($aInfo->content,0,8);

                        $condTarget[]=$one['id'];

                        $data[]=$one;
                    }
                    unset($one);
                }
                if (!empty($noReadComment[0]))
                {
                    foreach ($noReadComment[0] as &$one)
                    {
                        //如果别人评论了我的评论，会收到两条信息，去掉上面那个循环里的信息
                        if (isset($condTarget) && in_array($one['id'],$condTarget))
                        {
                            $one=null;
                            continue;
                        }

                        $uInfo=$this->getUserNameAndAvatar($one['uid']);
                        $tInfo=$this->getUserNameAndAvatar($one['tid']);

                        $one['uName']=$uInfo[0];
                        $one['uAvatar']=$uInfo[1];
                        $one['tName']=$tInfo[0];
                        $one['tAvatar']=$tInfo[1];

                        $one['dateTime']=formatDate($one['unixTime']);

                        //然后取出pic或视频或一点内容
                        $suffix=date('Y',substr($one['aid'],0,10));

                        ArticleModel::suffix($suffix);

                        $aInfo=ArticleModel::where('aid',$one['aid'])->first();

                        $one['picOrVideo']=(string)$aInfo->picOrVideo1;
                        $one['content']=mb_substr($aInfo->content,0,8);

                        $data[]=$one;
                    }
                    unset($one);
                }

                if (!empty($data)) $data=arraySort1($data,['desc','unixTime']);

                return response()->json(['resCode'=>Config::get('resCode.200'),
                    'likeTotal'=>$likeTotal,
                    'commentTotal'=>$commentTotal,
                    'data'=>$data]);

                break;

            case 1:

                $data=[];

                $like=$this->getReadLike($uid,$page);
                $comment=$this->getReadComment($uid,$page);

                //整理数组
                if (!empty($like))
                {
                    foreach ($like as &$one)
                    {
                        $uInfo=$this->getUserNameAndAvatar($one['uid']);

                        $one['uName']=$uInfo[0];
                        $one['uAvatar']=$uInfo[1];

                        $one['dateTime']=formatDate($one['unixTime']);

                        //然后取出pic或视频或一点内容
                        $suffix=date('Y',substr($one['aid'],0,10));

                        ArticleModel::suffix($suffix);

                        $aInfo=ArticleModel::where('aid',$one['aid'])->first();

                        $one['picOrVideo']=(string)$aInfo->picOrVideo1;
                        $one['content']=mb_substr($aInfo->content,0,8);

                        $data[]=$one;
                    }
                    unset($one);
                }
                if (!empty($comment))
                {
                    foreach ($comment as &$one)
                    {
                        $uInfo=$this->getUserNameAndAvatar($one['uid']);
                        $tInfo=$this->getUserNameAndAvatar($one['tid']);

                        $one['uName']=$uInfo[0];
                        $one['uAvatar']=$uInfo[1];
                        $one['tName']=$tInfo[0];
                        $one['tAvatar']=$tInfo[1];

                        $one['dateTime']=formatDate($one['unixTime']);

                        //然后取出pic或视频或一点内容
                        $suffix=date('Y',substr($one['aid'],0,10));

                        ArticleModel::suffix($suffix);

                        $aInfo=ArticleModel::where('aid',$one['aid'])->first();

                        $one['picOrVideo']=(string)$aInfo->picOrVideo1;
                        $one['content']=mb_substr($aInfo->content,0,8);

                        $data[]=$one;
                    }
                    unset($one);
                }

                if (!empty($data)) $data=arraySort1($data,['desc','unixTime']);

                return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$data]);

                break;

            case 2:

                //我的评论
                $comment=$this->getReadComment($uid,$page);

                if (empty($comment)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$comment]);

                $comment=arraySort1($comment,['desc','unixTime']);

                foreach ($comment as &$one)
                {
                    $uInfo=$this->getUserNameAndAvatar($one['uid']);
                    $tInfo=$this->getUserNameAndAvatar($one['tid']);
                    $oInfo=$this->getUserNameAndAvatar($one['oid']);

                    $one['uName']=$uInfo[0];
                    $one['uAvatar']=$uInfo[1];
                    $one['tName']=$tInfo[0];
                    $one['tAvatar']=$tInfo[1];
                    $one['oName']=$oInfo[0];
                    $one['oAvatar']=$oInfo[1];

                    $one['dateTime']=formatDate($one['unixTime']);

                    //然后取出pic或视频或一点内容
                    $suffix=date('Y',substr($one['aid'],0,10));

                    ArticleModel::suffix($suffix);

                    $aInfo=ArticleModel::where('aid',$one['aid'])->first();

                    $one['picOrVideo']=(string)$aInfo->picOrVideo1;
                    $one['content']=$aInfo->content;
                    $one['gName']=$aInfo->gName;

                    LikesModel::suffix($suffix);
                    CommentsModel::suffix($suffix);
                    $one['likeTotal']=LikesModel::where('aid',$one['aid'])->count();
                    LikesModel::where(['aid'=>$one['aid'],'uid'=>$uid,'isLike'=>1])->first() == null ? $one['iLike']=0 : $one['iLike']=1;
                    $one['commentTotal']=CommentsModel::where('aid',$one['aid'])->count();
                }
                unset($one);

                return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$comment]);

                break;

            case 3:

                //我的点赞
                $like=$this->getReadLike($uid,$page);

                if (empty($like)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$like]);

                $like=arraySort1($like,['desc','unixTime']);

                foreach ($like as &$one)
                {
                    $uInfo=$this->getUserNameAndAvatar($one['uid']);
                    $tInfo=$this->getUserNameAndAvatar($one['tid']);

                    $one['uName']=$uInfo[0];
                    $one['uAvatar']=$uInfo[1];
                    $one['tName']=$tInfo[0];
                    $one['tAvatar']=$tInfo[1];

                    $one['dateTime']=formatDate($one['unixTime']);

                    //然后取出pic或视频或一点内容
                    $suffix=date('Y',substr($one['aid'],0,10));

                    ArticleModel::suffix($suffix);

                    $aInfo=ArticleModel::where('aid',$one['aid'])->first();

                    $one['includeText']=$aInfo->includeText;
                    $one['includePic']=$aInfo->includePic;
                    $one['includeVideo']=$aInfo->includeVideo;

                    $picOrVideo=[];
                    for ($i=1;$i<=9;$i++)
                    {
                        $t="picOrVideo$i";
                        $picOrVideo[]=(string)$aInfo->$t;
                    }

                    $one['picOrVideo']=array_filter($picOrVideo);
                    $one['content']=$aInfo->content;
                    $one['gName']=$aInfo->gName;
                    $one['labels']=$this->addLabelsToArticle($aInfo->aid);

                    LikesModel::suffix($suffix);
                    CommentsModel::suffix($suffix);
                    $one['likeTotal']=LikesModel::where('aid',$one['aid'])->count();
                    $one['commentTotal']=CommentsModel::where('aid',$one['aid'])->count();
                }
                unset($one);

                return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$like]);

                break;

            default:

                return response()->json(['resCode'=>Config::get('resCode.604')]);

                break;
        }



    }

    public function getLikeTotal($uid)
    {
        $now=Carbon::now();

        $count=0;

        for ($i=0;$i<100;$i++)
        {
            $suffix=$now->year - $i;

            $table="community_article_like_{$suffix}";

            if (!Schema::connection($this->db)->hasTable($table)) break;

            LikesModel::suffix($suffix);

            $count+=LikesModel::where(['uid'=>$uid,'isLike'=>1])->count();
        }

        return $count;
    }

    public function getCommentTotal($uid)
    {
        $now=Carbon::now();

        $count=0;

        for ($i=0;$i<100;$i++)
        {
            $suffix=$now->year - $i;

            $table="community_article_comment_{$suffix}";

            if (!Schema::connection($this->db)->hasTable($table)) break;

            CommentsModel::suffix($suffix);

            $count+=CommentsModel::where('uid',$uid)->count();
        }

        return $count;
    }

    //未读赞
    public function getAllNoReadLike($uid)
    {
        $now=Carbon::now();

        $arr=[];

        for ($i=0;$i<100;$i++)
        {
            $suffix=$now->year - $i;

            $table="community_article_like_{$suffix}";

            if (!Schema::connection($this->db)->hasTable($table)) break;

            LikesModel::suffix($suffix);

            $tmp=LikesModel::where(['tid'=>$uid,'isLike'=>1,'isRead'=>0])->get(['aid','uid','tid','unixTime'])->toArray();

            foreach ($tmp as $one)
            {
                $arr[]=$one;
            }

            //更改为已读
            if (!empty($tmp)) LikesModel::where(['tid'=>$uid,'isLike'=>1,'isRead'=>0])->update(['isRead'=>1]);
        }

        return $arr;
    }

    //已读赞，分页显示
    public function getReadLike($uid,$page)
    {
        $limit=5;
        $offset=($page-1)*$limit;

        $now=Carbon::now();

        //此处留坑
        for ($i=0;$i<3;$i++)
        {
            $suffix=$now->year - $i;

            $table="community_article_like_{$suffix}";

            if (!Schema::connection($this->db)->hasTable($table)) break;

            $tableTarget[]=$table;
        }

        $sql='';
        foreach ($tableTarget as $oneTable)
        {
            $sql.=" union select * from {$oneTable}";
        }
        $sql=trim(ltrim(trim($sql),'union'));

        $reslSql="select aid,uid,tid,unixTime from ({$sql}) as tmp where tid={$uid} and isLike=1 and isRead=1 order by unixTime desc limit {$offset},{$limit}";

        $tmp=DB::connection($this->db)->select($reslSql);

        $tmp=jsonDecode(jsonEncode($tmp));

        return $tmp;
    }

    //未读评论
    public function getAllNoReadComment($uid,$needUpdate=true)
    {
        $now=Carbon::now();

        $arr1=[];
        $arr2=[];

        //我发的印象，并且是我没有读过的
        for ($i=0;$i<100;$i++)
        {
            $suffix=$now->year - $i;

            $table="community_article_comment_{$suffix}";

            if (!Schema::connection($this->db)->hasTable($table)) break;

            //我发的印象，并且是我没有读过的
            $sql="select t2.id,t2.aid,t2.uid,t2.tid,t2.comment,t2.unixTime from community_article_{$suffix} as t1 left join community_article_comment_{$suffix} as t2 on t1.aid=t2.aid where t1.uid={$uid} and t2.isOwnersRead=0";

            $tmp=jsonDecode(jsonEncode(DB::connection($this->db)->select($sql)));

            foreach ($tmp as $one)
            {
                $arr1[]=$one;

                //要更新的
                $cond[]=$one['aid'];
            }

            if (!empty($tmp) && $needUpdate)
            {
                CommentsModel::suffix($suffix);
                CommentsModel::whereIn('aid',array_flatten($cond))->update(['isOwnersRead'=>1]);
            }
        }

        //别的评论，关于我的
        for ($i=0;$i<100;$i++)
        {
            $suffix=$now->year - $i;

            $table="community_article_comment_{$suffix}";

            if (!Schema::connection($this->db)->hasTable($table)) break;

            //别的评论，关于我的
            $sql="select t2.id,t2.aid,t2.uid,t2.tid,t2.comment,t2.unixTime from community_article_{$suffix} as t1 left join community_article_comment_{$suffix} as t2 on t1.aid=t2.aid where t2.tid={$uid} and t2.isRead=0";

            $tmp=jsonDecode(jsonEncode(DB::connection($this->db)->select($sql)));

            foreach ($tmp as $one)
            {
                $arr2[]=$one;

                //要更新的
                $cond[]=$one['aid'];
            }

            if (!empty($tmp) && $needUpdate)
            {
                CommentsModel::suffix($suffix);
                CommentsModel::whereIn('aid',array_flatten($cond))->update(['isRead'=>1]);
            }
        }

        return [$arr1,$arr2];
    }

    //已读评论,分页显示
    public function getReadComment($uid,$page)
    {
        $limit=5;
        $offset=($page-1)*$limit;

        $now=Carbon::now();

        //此处留坑
        for ($i=0;$i<3;$i++)
        {
            $suffix=$now->year - $i;

            $table="community_article_comment_{$suffix}";

            if (!Schema::connection($this->db)->hasTable($table)) break;

            $tableTarget[]=$table;
        }

        $sql='';
        foreach ($tableTarget as $oneTable)
        {
            $sql.=" union select id,oid,aid,uid,tid,comment,unixTime from {$oneTable}";
        }
        $sql=trim(ltrim(trim($sql),'union'));

        $reslSql="select id,aid,uid,tid,oid,comment,unixTime from ({$sql}) as tmp where (oid={$uid}) or (oid!={$uid} and tid={$uid}) order by unixTime desc limit {$offset},{$limit}";

        $tmp=jsonDecode(jsonEncode(DB::connection($this->db)->select($reslSql)));

        return jsonDecode(jsonEncode($tmp));
    }

    //获取用户名和头像
    public function getUserNameAndAvatar($uid)
    {
        $uName=(string)Redis::connection('UserInfo')->hget($uid,'name');
        $uAvatar=(string)Redis::connection('UserInfo')->hget($uid,'avatar');

        if ($uName=='')
        {
            $uName='网友'.str_random(6);
        }
        if ($uAvatar=='')
        {
            $uAvatar='/imgModel/systemAvtar.png';
        }

        return [$uName,$uAvatar];
    }

    //印象分数，广场页面用
    public function setCommunityScore($type,$uid,$articleId)
    {
        //$type是comment或这like，comment是5分，like是1分，精华是20分

        //记录5天内哪些印象被点赞和被评论

        //该条印象有哪些标签
        $suffix=date('Y',substr($articleId,0,10));
        ArticleLabelModel::suffix($suffix);

        $labelsArr=array_flatten(ArticleLabelModel::where('aid',$articleId)->get(['labelId'])->toArray());

        array_push($labelsArr,0);

        foreach ($labelsArr as $oneLabel)
        {
            if ($type=='like')
            {
                $today=Carbon::now()->format('Ymd');

                $expire=86400 * 5;

                //加入当天点赞集合
                if (Redis::connection('HotArticleInfo')->hget("Like_{$uid}_{$oneLabel}_{$today}",$articleId)==1)
                {
                    //今天已经点过赞了

                }else
                {
                    //今天没有点赞
                    Redis::connection('HotArticleInfo')->hset("Like_{$uid}_{$oneLabel}_{$today}",$articleId,1);

                    //当天分数加1
                    Redis::connection('HotArticleInfo')->zincrby("Score_{$oneLabel}_{$today}",1,$articleId);
                }

                Redis::connection('HotArticleInfo')->expire("Like_{$uid}_{$oneLabel}_{$today}",$expire);
                Redis::connection('HotArticleInfo')->expire("Score_{$oneLabel}_{$today}",$expire);
            }

            if ($type=='comment')
            {
                $today=Carbon::now()->format('Ymd');

                $expire=86400 * 5;

                //加入当天评论集合
                if (Redis::connection('HotArticleInfo')->hget("Comment_{$uid}_{$oneLabel}_{$today}",$articleId)==1)
                {
                    //今天已经评论过了

                }else
                {
                    //今天没有评论
                    Redis::connection('HotArticleInfo')->hset("Comment_{$uid}_{$oneLabel}_{$today}",$articleId,1);

                    //当天分数加1还是加5
                    //查看之前4天是否已经评论过了
                    $tmp=$today-1;
                    $day1=Redis::connection('HotArticleInfo')->hget("Comment_{$uid}_{$oneLabel}_{$tmp}",$articleId);
                    $tmp=$today-2;
                    $day2=Redis::connection('HotArticleInfo')->hget("Comment_{$uid}_{$oneLabel}_{$tmp}",$articleId);
                    $tmp=$today-3;
                    $day3=Redis::connection('HotArticleInfo')->hget("Comment_{$uid}_{$oneLabel}_{$tmp}",$articleId);
                    $tmp=$today-4;
                    $day4=Redis::connection('HotArticleInfo')->hget("Comment_{$uid}_{$oneLabel}_{$tmp}",$articleId);

                    if ($day1==1 || $day2==1 || $day3==1 || $day4==1)
                    {
                        Redis::connection('HotArticleInfo')->zincrby("Score_{$oneLabel}_{$today}",1,$articleId);
                    }else
                    {
                        Redis::connection('HotArticleInfo')->zincrby("Score_{$oneLabel}_{$today}",5,$articleId);
                    }
                }

                Redis::connection('HotArticleInfo')->expire("Comment_{$uid}_{$oneLabel}_{$today}",$expire);
                Redis::connection('HotArticleInfo')->expire("Score_{$oneLabel}_{$today}",$expire);
            }
        }

        return true;
    }

    //从redis中取广场热门页的aid
    public function getHotArticleFromRedis($label,$limit,$page)
    {
        //取最近3天的热门
        $today=Carbon::now();

        //3分钟过期
        $minute=3;

        //生成大集合
        $res=Cache::remember("func_getHotArticleFromRedis_cache_label_{$label}",$minute,function () use ($today,$minute,$label) {

            $tmpKey1="Score_{$label}_{$today->format('Ymd')}";
            $tmpKey2="Score_{$label}_{$today->subDays(1)->format('Ymd')}";
            $tmpKey3="Score_{$label}_{$today->subDays(2)->format('Ymd')}";

            //取并集后的大集合
            $key="Destination_{$label}_{$today->format('Ymd')}";

            //TheBestArticle是所有加精的aid集合
            Redis::connection('HotArticleInfo')->zunionstore($key,4,$tmpKey1,$tmpKey2,$tmpKey3,"TheBestArticle_{$label}");

            Redis::connection('HotArticleInfo')->expire($key,$minute*60*2);

            //返回所有
            $res=Redis::connection('HotArticleInfo')->zrevrange($key,0,-1,'withscores');

            //降序排序，从高到低
            if (!empty($res))
            {
                foreach ($res as $k=>$v)
                {
                    $tmp[]=[$k=>$v];
                }

                $data=$tmp;
                $aid=array_keys($res);

            }else
            {
                $aid=$data=[];
            }

            return [$data,$aid];
        });

        //先从这个数组里取，取没有了再从数据表中取
        //return response()->json(['resCode'=>Config::get('resCode.625')]);
        if (!empty($res[0]))
        {
            //从热门里取
            $hotInRedis=paginateByMyself($res[0],$page,$limit);

            //取到有热度的aid了
            if (!empty($hotInRedis)) return $hotInRedis;

            //如果取不到了，就要从mysql中取得，page需要重新算一下
            //计算如下：
            //res0里有23条数据，limit是5，用户page是1取0-4，2取5-9，3取10-14，4取15-19，5取20-24，6取不到
            //res0里有28条数据，limit是5，用户page是1取0-4，2取5-9，3取10-14，4取15-19，5取20-24，6取25-29，7取不到
            //res0里有19条数据，limit是5，用户page是1取0-4，2取5-9，3取10-14，4取15-19，5取不到
            //res0里有07条数据，limit是5，用户page是1取0-4，2取5-9，3取不到

            //例子1当page传入6的时候就应该从mysql中取了，但是这时候的offset应该是0
            //需要减去的值
            $needSub=(int)ceil(count($res[0])/$limit)+1;

            $offset=($page-$needSub)*$limit;

            $coldInMysql=$this->getColdArticleFromMysql($label,$offset,$limit,$res[1]);

            return $coldInMysql;

        }else
        {
            //从数据库中取得
            $offset=($page-1)*$limit;

            $coldInMysql=$this->getColdArticleFromMysql($label,$offset,$limit);

            return $coldInMysql;
        }
    }

    //取得没有热度的aid
    public function getColdArticleFromMysql($label,$offset,$limit,$except=[])
    {
        $minute=3;

        $res=Cache::remember("func_getColdArticleFromMysql_cache_label_{$label}_offset_{$offset}",$minute,function () use ($label,$offset,$limit,$except) {

            //union最近3年的印象，从印象标签关系表中
            $now=Carbon::now();

            //此处留坑
            for ($i=0;$i<3;$i++)
            {
                $suffix=$now->year - $i;

                $table="community_article_label_{$suffix}";

                if (!Schema::connection($this->db)->hasTable($table)) break;

                $tableTarget[]=$table;
            }

            $sql='';
            foreach ($tableTarget as $oneTable)
            {
                $sql.=" union select * from {$oneTable}";
            }
            $sql=trim(ltrim(trim($sql),'union'));

            //按不按照label筛选
            if ($label==0)
            {
                $reslSql="select distinct aid from ({$sql}) as tmp ABCDEFG order by unixTime desc limit {$offset},{$limit}";

                if (empty($except))
                {
                    $reslSql=str_replace('ABCDEFG','',$reslSql);
                }else
                {
                    $str='';
                    foreach ($except as $oneAid)
                    {
                        $str.=',\''.$oneAid.'\'';
                    }
                    $str=ltrim($str,',');

                    $str='where aid not in ('.$str.')';

                    $reslSql=str_replace('ABCDEFG',$str,$reslSql);
                }

            }else
            {
                $reslSql="select distinct aid from ({$sql}) as tmp where labelId={$label} ABCDEFG order by unixTime desc limit {$offset},{$limit}";

                if (empty($except))
                {
                    $reslSql=str_replace('ABCDEFG','',$reslSql);
                }else
                {
                    $str='';
                    foreach ($except as $oneAid)
                    {
                        $str.=',\''.$oneAid.'\'';
                    }
                    $str=ltrim($str,',');

                    $str='and aid not in ('.$str.')';

                    $reslSql=str_replace('ABCDEFG',$str,$reslSql);
                }
            }

            $tmp=DB::connection($this->db)->select($reslSql);

            return jsonDecode(jsonEncode($tmp));

        });

        return $res;
    }

    //广场页面
    public function getPublicSquarePage(Request $request)
    {
        $now=Carbon::now();

        //取uid有多少未读消息
        $uid=(int)$request->uid;

        $countLike=0;

        for ($i=0;$i<100;$i++)
        {
            $suffix=$now->year - $i;

            $table="community_article_like_{$suffix}";

            if (!Schema::connection($this->db)->hasTable($table)) break;

            LikesModel::suffix($suffix);

            $countLike+=LikesModel::where(['tid'=>$uid,'isLike'=>1,'isRead'=>0])->count();
        }

        $data=[];

        $noReadComment=$this->getAllNoReadComment($uid,false);

        //整理数组
        if (!empty($noReadComment[1]))
        {
            //如果别人评论了我的评论，会收到两条信息，去掉上面那个循环里的信息
            $condTarget=[];

            foreach ($noReadComment[1] as $one)
            {
                $condTarget[]=$one['id'];

                $data[]=$one;
            }
        }
        if (!empty($noReadComment[0]))
        {
            foreach ($noReadComment[0] as $one)
            {
                //如果别人评论了我的评论，会收到两条信息，去掉上面那个循环里的信息
                if (isset($condTarget) && in_array($one['id'],$condTarget)) continue;

                $data[]=$one;
            }
        }

        $countComment=count($data);

        //小红点
        $littleRedDot=$countLike+$countComment;
        //========================================================================================================================

        $limit=10;

        //0是热门，1是关注，2是最新
        $type=(int)$request->type;

        $label=(int)$request->label;

        $page=(int)$request->page;

        switch ($type)
        {
            case 0:

                //热门

                //首先返回47个标签
                $hotLabels=LabelModel::where('labelContent','<>','amyYOEPCiph6NQr')->orderBy('useTotal','desc')->orderBy('id')->limit(47)->get(['id','labelContent','useTotal'])->toArray();

                $res=$this->getHotArticleFromRedis($label,$limit,$page);

                if (empty($res)) return response()->json(['resCode'=>Config::get('resCode.200'),'littleRedDot'=>$littleRedDot,'hotLabels'=>$hotLabels,'data'=>$res]);

                //不为空就整理数组给前端返回
                $info=[];
                $aid=[];
                foreach ($res as $one)
                {
                    if (strlen(current($one))===16)
                    {
                        $thisAid=current($one);
                    }else
                    {
                        $thisAid=current(array_flip($one));
                    }

                    //取出印象详情
                    $suffix=date('Y',substr($thisAid,0,10));

                    ArticleModel::suffix($suffix);

                    $obj=ArticleModel::where(['aid'=>$thisAid,'isShow'=>1])->first();

                    if ($obj==null) continue;

                    $info[]=$obj->toArray();
                    $aid[]=$obj->aid;
                }

                $data=[$info,$aid];

                $data=$this->addLikesToArticle($data);
                $data=$this->addCommentsToArticle($data);
                $data=current($this->sortArticle($data,$uid));

                return response()->json(['resCode'=>Config::get('resCode.200'),'littleRedDot'=>$littleRedDot,'hotLabels'=>$hotLabels,'data'=>$data]);

                break;

            case 1:

                //关注
                $follower=array_keys(Redis::connection('CommunityInfo')->zrevrange('follower_'.$uid,0,-1,'withscores'));

                if (empty($follower)) return response()->json(['resCode'=>Config::get('resCode.676')]);

                $now=Carbon::now();

                //此处留坑
                for ($i=0;$i<3;$i++)
                {
                    $suffix=$now->year - $i;

                    $table="community_article_{$suffix}";

                    if (!Schema::connection($this->db)->hasTable($table)) break;

                    $tableTarget[]=$table;
                }

                $sql='';
                foreach ($tableTarget as $oneTable)
                {
                    $sql.=" union select * from {$oneTable}";
                }
                $sql=trim(ltrim(trim($sql),'union'));

                $offset=($page-1)*$limit;

                $uidStr='';
                foreach ($follower as $one)
                {
                    $uidStr.=','.$one;
                }
                $uidStr=ltrim($uidStr,',');

                $reslSql="select * from ({$sql}) as tmp where uid in ({$uidStr}) order by unixTime desc limit {$offset},{$limit}";

                $tmp=jsonDecode(jsonEncode(DB::connection($this->db)->select($reslSql)));

                if (empty($tmp)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$tmp]);

                foreach ($tmp as $one)
                {
                    $aidArr[]=$one['aid'];
                }

                $data=[$tmp,$aidArr];

                $data=$this->addLikesToArticle($data);
                $data=$this->addCommentsToArticle($data);
                $data=current($this->sortArticle($data,$uid));

                return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$data]);

                break;

            case 2:

                //最新
                //此处留坑
                for ($i=0;$i<3;$i++)
                {
                    $suffix=$now->year - $i;

                    $table="community_article_{$suffix}";

                    if (!Schema::connection($this->db)->hasTable($table)) break;

                    $tableTarget[]=$table;
                }

                $sql='';
                foreach ($tableTarget as $oneTable)
                {
                    $sql.=" union select * from {$oneTable}";
                }
                $sql=trim(ltrim(trim($sql),'union'));

                $offset=($page-1)*$limit;

                $reslSql="select * from ({$sql}) as tmp order by unixTime desc limit {$offset},{$limit}";

                $tmp=jsonDecode(jsonEncode(DB::connection($this->db)->select($reslSql)));

                if (empty($tmp)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$tmp]);

                foreach ($tmp as $one)
                {
                    $aidArr[]=$one['aid'];
                }

                $data=[$tmp,$aidArr];

                $data=$this->addLikesToArticle($data);
                $data=$this->addCommentsToArticle($data);
                $data=current($this->sortArticle($data,$uid));

                return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$data]);

                break;

            default:

                return response()->json(['resCode'=>Config::get('resCode.604')]);

                break;
        }
    }

    //通过aid查询印象详情
    public function articleDetail(Request $request)
    {
        $uid=(int)$request->uid;
        $aid=(string)$request->aid;

        $data=[];

        $suffix=date('Y',substr($aid,0,10));

        ArticleModel::suffix($suffix);

        $res=ArticleModel::where(['aid'=>$aid,'isShow'=>1])->orWhere(function ($query) use ($aid,$uid) {
            $query->where(['aid'=>$aid,'isShow'=>0,'uid'=>$uid]);
        })->first();

        if ($res==null) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$data]);

        $tmp=[[$res->toArray()],[$res->aid]];

        $tmp=$this->addLikesToArticle($tmp);
        $tmp=$this->addCommentsToArticle($tmp);
        $tmp=$this->sortArticle($tmp,$uid);

        $data=current($tmp);

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$data]);
    }

    //用户页的关注和粉丝详情
    public function RelationDetail(Request $request)
    {
        $uid=$request->uid;
        $type=$request->type;
        $page=$request->page;

        if (!$this->checkInputUserId($uid)) return response()->json(['resCode'=>Config::get('resCode.604')]);

        $data=[];

        switch ($type)
        {
            case 0:

                //关注，实际上就是我自己的关注，因为别人的不能点进来
                $res=Redis::connection('CommunityInfo')->zrevrange('follower_'.$uid,0,-1,'withscores');

                if (empty($res)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$data]);

                $userObj=new UserController();

                //key是uid，value是关注时间
                foreach ($res as $key=>$value)
                {
                    //该uid的用户名和头像
                    $userInfo=$userObj->getUserNameAndAvatar($key);

                    //该uid的粉丝数
                    $fansTotal=(int)Redis::connection('CommunityInfo')->zcard('fans_'.$key);

                    //该uid发了多少印象
                    $communityTotal=(int)Redis::connection('UserInfo')->hget($key,'CommunityArticleTotal');

                    //与我的关系
                    //我关注他没
                    Redis::connection('CommunityInfo')->zscore('follower_'.$uid,$key)!=null ? $followerNum=1 : $followerNum=0;
                    //他关注我没
                    Redis::connection('CommunityInfo')->zscore('fans_'.$uid,$key)!=null ? $fansNum=2 : $fansNum=0;

                    $data[]=['timestamp'=>$value,'uAvatar'=>$userInfo['avatar'],'uName'=>$userInfo['name'],'fansTotal'=>$fansTotal,'communityTotal'=>$communityTotal,'relation'=>$followerNum+$fansNum];
                }

                $data=myPage($data,10,$page);

                return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$data]);

                break;

            case 1:

                //粉丝，实际上就是我自己的粉丝，因为别人的也不能点进来
                $res=Redis::connection('CommunityInfo')->zrevrange('fans_'.$uid,0,-1,'withscores');

                if (empty($res)) return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$data]);

                $userObj=new UserController();

                //key是uid，value是关注时间
                foreach ($res as $key=>$value)
                {
                    //该uid的用户名和头像
                    $userInfo=$userObj->getUserNameAndAvatar($key);

                    //该uid的粉丝数
                    $fansTotal=(int)Redis::connection('CommunityInfo')->zcard('fans_'.$key);

                    //该uid发了多少印象
                    $communityTotal=(int)Redis::connection('UserInfo')->hget($key,'CommunityArticleTotal');

                    //与我的关系
                    //我关注他没
                    Redis::connection('CommunityInfo')->zscore('follower_'.$uid,$key)!=null ? $followerNum=1 : $followerNum=0;
                    //他关注我没
                    Redis::connection('CommunityInfo')->zscore('fans_'.$uid,$key)!=null ? $fansNum=2 : $fansNum=0;

                    $data[]=['timestamp'=>$value,'uAvatar'=>$userInfo['avatar'],'uName'=>$userInfo['name'],'fansTotal'=>$fansTotal,'communityTotal'=>$communityTotal,'relation'=>$followerNum+$fansNum];
                }

                $data=myPage($data,10,$page);

                return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>$data]);

                break;

            default:

                return response()->json(['resCode'=>Config::get('resCode.604')]);

                break;
        }

        return true;
    }





}
