<?php

namespace App\Http\Controllers\admin;

use App\Model\Community\ArticleLabelModel;
use App\Model\Community\ArticleModel;
use App\Model\Community\CommentsModel;
use App\Model\Community\LikesModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCommunityController extends AdminBaseController
{
    public $communityDB='communityDB';

    public function ajax(Request $request)
    {
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
        }
    }

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









}
