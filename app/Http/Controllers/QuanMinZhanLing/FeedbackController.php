<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Model\UserFeedbackModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Intervention\Image\Facades\Image;

class FeedbackController extends BaseController
{
    public function createTable()
    {
        if (!Schema::connection('masterDB')->hasTable('user_feedback'))
        {
            Schema::connection('masterDB')->create('user_feedback', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');
                $table->integer('uid')->unsigned()->comment('用户主键')->index();
                $table->text('userContent')->nullable()->comment('用户意见内容');
                $table->text('tssjContent')->nullable()->comment('官方意见内容');
                $table->tinyInteger('isReply')->unsigned()->nullable()->comment('官方是否已经回复');

                $table->string('userPic1','200')->nullable()->comment('图片1');
                $table->string('userPic2','200')->nullable()->comment('图片2');
                $table->string('userPic3','200')->nullable()->comment('图片3');
                $table->string('userPic4','200')->nullable()->comment('图片4');
                $table->string('userPic5','200')->nullable()->comment('图片5');
                $table->string('userPic6','200')->nullable()->comment('图片6');

                $table->string('userVideo1','200')->nullable()->comment('视频1');
                $table->string('userVideo2','200')->nullable()->comment('视频2');
                $table->string('userVideo3','200')->nullable()->comment('视频3');

                $table->string('tssjPic1','200')->nullable()->comment('图片1');
                $table->string('tssjPic2','200')->nullable()->comment('图片2');
                $table->string('tssjPic3','200')->nullable()->comment('图片3');
                $table->string('tssjPic4','200')->nullable()->comment('图片4');
                $table->string('tssjPic5','200')->nullable()->comment('图片5');
                $table->string('tssjPic6','200')->nullable()->comment('图片6');

                $table->string('tssjVideo1','200')->nullable()->comment('视频1');
                $table->string('tssjVideo2','200')->nullable()->comment('视频2');
                $table->string('tssjVideo3','200')->nullable()->comment('视频3');

                $table->integer('partitionUseThis')->unsigned()->nullable()->comment('分区用');
                $table->timestamps();

                //Alter table user_feedback partition by linear key(partitionUseThis) partitions 32;
            });
        }

        return true;
    }

    //处理用户查看和提交
    public function feedbackHandler(Request $request)
    {
        $this->createTable();

        $uid=trim($request->uid);

        $fid=trim($request->fid);

        if (!is_numeric($uid)) return response()->json(['resCode'=>Config::get('resCode.604')]);

        //get查看自己所有的意见
        if ($request->isMethod('get'))
        {
            return $this->getFeedback($request,$uid,$fid);
        }

        //post提交自己新增的意见
        if ($request->isMethod('post'))
        {
            return $this->setFeedback($request,$uid);
        }

        return response()->json(['resCode'=>Config::get('resCode.604')]);
    }

    //获取反馈列表，或一个反馈的详情
    public function getFeedback($request,$uid,$fid)
    {
        if (!is_numeric($request->page) || trim($request->page) <= 1) $page=1;

        $limit=10;

        $offset=($page-1)*$limit;

        //$msg=Cache::remember('feedback_'.$uid,5,function() use ($uid,$limit,$offset)
        //{
        //    return UserFeedbackModel::where('uid',$uid)->limit($limit)->offset($offset)->orderBy('isReply','desc')->orderBy('updated_at','desc')->get()->toArray();
        //});

        if (is_numeric($fid))
        {
            $msg=UserFeedbackModel::where('id',$fid)->first()->toArray();

        }else
        {
            $msg=UserFeedbackModel::where('uid',$uid)->limit($limit)->offset($offset)->orderBy('isReply','desc')->orderBy('updated_at','desc')->get()->toArray();
        }

        return response()->json(['resCode'=>Config::get('resCode.200'),'data'=>jsonEncode($msg)]);
    }

    //用户提交一个反馈
    public function setFeedback($request,$uid)
    {
        $redayToInsert=[
            'uid'=>$uid,
            'userContent'=>trim($request->content),
            'isReply'=>0,
        ];

        //拿图片
        $picArr=$request->picArr;

        if (is_array($picArr))
        {

        }else
        {
            $picArr=jsonDecode($picArr);
        }

        if (!empty($picArr) && $picArr!='')
        {
            //pic计数器
            $i=1;

            //一个一个存图片
            foreach ($picArr as $onePic)
            {
                $thisPicPath=$this->storeImg(uploadMyImg($onePic));

                $redayToInsert['userPic'.$i]=$thisPicPath;

                $i++;
            }
        }

        //拿视频
        $videoArr=$request->videoArr;

        if (is_array($videoArr))
        {

        }else
        {
            $videoArr=jsonDecode($videoArr);
        }

        if (!empty($videoArr) && $videoArr!='')
        {
            //pic计数器
            $i=1;

            //一个一个存视频
            $this->storeVideo();

            $i++;
        }

        try
        {
            UserFeedbackModel::create($redayToInsert);

        }catch (\Exception $e)
        {
            return response()->json(['resCode'=>Config::get('resCode.604')]);
        }

        return response()->json(['resCode'=>Config::get('resCode.200')]);
    }

    //存储图片
    public function storeImg($base64)
    {
        if ($base64===false) return null;

        $Ym=date('Ym',time());

        //in mysql
        $storePath=DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'feedback'.DIRECTORY_SEPARATOR.$Ym.DIRECTORY_SEPARATOR;

        //real path
        $path=public_path(DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'feedback'.DIRECTORY_SEPARATOR.$Ym.DIRECTORY_SEPARATOR);

        //创建目录
        if (!is_dir($path)) mkdir($path,0777,true);

        //创建文件名
        $filename=str_replace('.','',microtime(true)).str_random(5).'.jpg';

        try
        {
            Image::make($base64)->save($storePath.$filename);

            return $storePath.$filename;

        }catch (\Exception $e)
        {
            @unlink($path.$filename);

            sleep(1);

            try
            {
                Image::make($base64)->save($storePath.$filename);

                return $storePath.$filename;

            }catch (\Exception $w)
            {
                return null;
            }
        }
    }

    //存储视频
    public function storeVideo()
    {

    }











}
