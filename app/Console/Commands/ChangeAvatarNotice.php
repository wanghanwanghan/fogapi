<?php

namespace App\Console\Commands;

use App\Model\AvatarCheckModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class ChangeAvatarNotice extends Command
{
    protected $signature = 'Grid:ChangeAvatar';

    protected $description = '延时更新用户头像';

    public function __construct()
    {
        parent::__construct();
    }

    public function createTable()
    {
        //头像审核表
        if (!Schema::connection('masterDB')->hasTable('avatar_check'))
        {
            Schema::connection('masterDB')->create('avatar_check', function (Blueprint $table) {

                $table->increments('id')->unsigned()->comment('自增主键');
                $table->integer('uid')->unsigned()->comment('用户主键')->index();
                $table->string('name',100)->nullable()->comment('用户名称');
                $table->string('avatarUrl',200)->nullable()->comment('头像url地址');
                $table->tinyInteger('isCheck')->nullable()->unsigned()->comment('是否审核');
                $table->timestamps();

            });
        }

        return true;
    }

    public function handle()
    {
        $this->createTable();

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

                //头像不直接替换到redis里。需要审核，审核通过后，替换redis里的头像url
                //Redis::connection('UserInfo')->hset($oneUid,'avatar',$res);
                AvatarCheckModel::updateOrCreate(['uid'=>$oneUid],['name'=>$userName,'avatarUrl'=>$res,'isCheck'=>0]);

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
