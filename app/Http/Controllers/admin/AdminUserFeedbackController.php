<?php

namespace App\Http\Controllers\admin;

use App\Model\UserFeedbackModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class AdminUserFeedbackController extends AdminBaseController
{
    public function index(Request $request)
    {
        $page=$request->page;
        $limit=10;
        $offset=($page-1)*$limit;

        $res=UserFeedbackModel::orderBy('id','desc')->limit($limit)->offset($offset)->get(['id','uid','userContent','isReply','updated_at']);

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






}