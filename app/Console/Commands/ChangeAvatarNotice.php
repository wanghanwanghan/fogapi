<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ChangeAvatarNotice extends Command
{
    protected $signature = 'Grid:ChangeAvatar';

    protected $description = '延时更新用户头像';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //取出待处理uid
        $allUid=Redis::connection('WriteLog')->smembers('ChangeAvatarAlready');

        //删掉集合
        Redis::connection('WriteLog')->del('ChangeAvatarAlready');

        foreach ($allUid as $oneUid)
        {
            if ($oneUid=='' || !is_numeric($oneUid)) continue;

            $res=DB::connection('tssj_old')->table('tssj_member')->where('userid',$oneUid)->first();

            if ($res==null || $res->avatar=='') continue;

            $userName=trim($res->username);

            //判断图片是否存在
            $check=checkFileExists('http://www.wodeluapp.com/attachment/'.$res->avatar);

            if ($check==false) continue;

            try
            {
                $img=file_get_contents('http://www.wodeluapp.com/attachment/'.$res->avatar);

                $res=storeFile($img,$oneUid,'','avatar');

                //等于空说明出错了
                if ($res=='') Redis::connection('WriteLog')->sadd('ChangeAvatarAlready',$oneUid);

                //头像存入redis
                Redis::connection('UserInfo')->hset($oneUid,'avatar',$res);

                //名字存入redis
                Redis::connection('UserInfo')->hset($oneUid,'name',$userName);

            }catch (\Exception $e)
            {
                Redis::connection('WriteLog')->sadd('ChangeAvatarAlready',$oneUid);

                continue;
            }
        }
    }
}
