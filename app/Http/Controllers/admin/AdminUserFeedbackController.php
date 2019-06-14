<?php

namespace App\Http\Controllers\admin;

use App\Model\UserFeedbackModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redis;

class AdminUserFeedbackController extends AdminBaseController
{
    //用户反馈首页
    public function index(Request $request)
    {
        $page=$request->page ?: 1;
        $limit=10;
        $offset=($page-1)*$limit;

        $res=UserFeedbackModel::orderBy('id','desc')->paginate($limit);

        foreach ($res as $oneFeedback)
        {
            //设置用户名称
            $oneFeedback->uName=Redis::connection('UserInfo')->hget($oneFeedback->uid,'name');

            //超过20字不显示
            $oneFeedback->userContent=mb_substr($oneFeedback->userContent,0,20).'...';
        }

        return view('admin.feedback.feedback_index')->with(['res'=>$res]);
    }

    //单条反馈详情
    public function feedbackDetail($id)
    {
        try
        {
            $res=UserFeedbackModel::findOrFail($id);

            $res->uName=Redis::connection('UserInfo')->hget($res->uid,'name');

        }catch (ModelNotFoundException $e)
        {
            abort(400);
        }

        return view('admin.feedback.feedback_detail')->with(['res'=>$res]);
    }

    //官方回复用户时候，上传的图片
    public function uploadPic(Request $request,$id)
    {
        //生成年月日
        $Ym=date('Ym',time());

        //mysql中存的路径
        $storePath=DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'feedback'.DIRECTORY_SEPARATOR.$Ym.DIRECTORY_SEPARATOR;

        //要把视频移动到这个目录
        $path=public_path(DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'feedback'.DIRECTORY_SEPARATOR.$Ym.DIRECTORY_SEPARATOR);

        //创建目录
        if (!is_dir($path)) mkdir($path,0777,true);

        //pic计数器
        $i=1;

        foreach ($request->file() as $one)
        {
            if ($i>=7) continue;

            if ($one instanceof UploadedFile)
            {
                //获取上传文件的后缀，如abc.png，获取到的为png
                $fileExtension=$one->getClientOriginalExtension();

                $newName=str_replace('.','',microtime(true)).str_random(5).'.'.$fileExtension;

                $one->move($path,$newName);

                $picUrl[]=$storePath.$newName;
            }

            $i++;
        }

        return ['errno'=>0,'data'=>$picUrl];
    }

    //ajax
    public function ajax(Request $request)
    {
        switch ($request->type)
        {
            case 'answerFeedback':

                $fid=trim($request->fid);

                if (!$fid) return ['resCode'=>201];

                $text=filter4(trim($request->text));
                $html=trim($request->html);//其中有img标签，提取src

                try
                {
                    $info=UserFeedbackModel::findOrFail($fid);

                }catch (ModelNotFoundException $e)
                {
                    return ['resCode'=>202];
                }

                $info->tssjContent=$text;

                //提取图片src
                preg_match_all('/(?<=(src="))[^"]*?(?=")/',$html,$res);

                if (!empty(current($res)))
                {
                    //如果含有图片链接

                    //pic计数器
                    $i=1;

                    foreach (current($res) as $onePic)
                    {
                        if ($i>=7) continue;

                        $tar='tssjPic'.$i;

                        $info->$tar=$onePic;

                        $i++;
                    }
                }

                $info->isReply=1;
                $info->save();

                return ['resCode'=>200];

                break;

            default:

                break;
        }
    }




}