<?php

namespace App\Http\Controllers\QuanMinZhanLing\Community;

use App\Http\Controllers\QuanMinZhanLing\BaseController;
use App\Http\Controllers\Server\ContentCheckBase;
use App\Http\Controllers\Server\StoreVideoBase;
use App\Model\Community\ArticleModel;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Intervention\Image\Facades\Image;

class CommunityController extends BaseController
{
    //建表
    public function createTable($tableType)
    {
        $db='communityDB';
        $now=Carbon::now();

        //$suffix=$now->quarter;

        if ($tableType=='article')
        {
            //印象表预估每天5000条，一年就是200万条
            //所以按照年分表
            $suffix=$now->year;

            $table="community_article_{$suffix}";

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
        $picInfo=getimagesize($storePathForOrigin.$fileName);
        $width=$height=null;
        $picInfo[0] > $picInfo[1] ? $height=200 : $width=200;

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
            $fileExtension=$file->getClientOriginalExtension();
            $file->move($storePathForOrigin,$filaName.'.'.$fileExtension);

        }catch (\Exception $e)
        {
            return 'move video error';
        }

        return [$returnPathForOrigin.$filaName.'.'.$fileExtension];
    }

    //发布一条印象
    public function createArticle(Request $request)
    {
        $this->createTable('article');

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

        $includeText=0;
        $includePic=0;
        $includeVideo=0;

        //每个图片100k或者200k
        if ($request->picArr instanceof UploadedFile)
        {
            //直接传的图片，测试用的
            $picArr=$request->file('picArr');

            dd('you got it');

        }elseif (is_array($request->picArr))
        {
            //base64的数组，处理图片时候需要base64_decode()
            $picArr=$request->picArr;
            foreach ($picArr as $one);
            {
                $tmp[]=base64_decode($one);
            }
            $picArr=$tmp;

        }else
        {
            $picArr=[];
        }

        //存储图片或这视频的数组
        $readyToInsertForPicAndVideo=[];

        //有一张图片解析失败，就不让发布这条印象
        if (is_array($picArr) && !empty($picArr))
        {
            $num=1;
            $hasError=false;
            foreach ($picArr as $one)
            {
                $res=$this->storePic($one,$num,$articlePrimary);

                if (!is_array($res))
                {
                    $hasError=true;
                    break;
                }

                $readyToInsertForPicAndVideo["picOrVideo1{$num}"]=current($res);

                $num++;
            }

            //有图片处理出错，印象发布失败
            if ($hasError) return response()->json(['resCode'=>Config::get('resCode.650')]);

            $includePic=1;
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
            ArticleModel::suffix(Carbon::now()->year);
            ArticleModel::create($readyToInsert);

            return response()->json(['resCode'=>Config::get('resCode.200')]);

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.660')]);
        }





    }













}