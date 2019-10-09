<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OneJoke extends Command
{
    protected $signature = 'Tssj:OneJoke';

    protected $description = '笑话';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $sql="select max(unixTime) as maxTime from oneJoke";

        $time=current(jsonDecode(jsonEncode(DB::connection('masterDB')->select($sql))));

        if ($time['maxTime']!=null)
        {
            $time=$time['maxTime']+1;
        }else
        {
            $time=1418816972;
        }

        $url="http://v.juhe.cn/joke/content/list.php";

        for ($page=1;$page<=20;$page++)
        {
            $arg="sort=asc&page={$page}&pagesize=20&time={$time}&key=41b914ce994bc0e57d0fce86b0041a03";

            try
            {
                $res=jsonDecode(file_get_contents($url.'?'.$arg));

                if ($res['reason']!='Success' || $res['error_code']!='0') continue;

                $text=$res['result']['data'];

                //插入
                foreach ($text as $oneJoke)
                {
                    $sql="select * from oneJoke where md5Index='{$oneJoke['hashId']}'";

                    $res=DB::connection('masterDB')->select($sql);

                    if (empty($res))
                    {
                        $time=Carbon::now()->format('Y-m-d H:i:s');

                        $sql="insert into oneJoke values (null,'{$oneJoke['hashId']}','{$oneJoke['content']}',{$oneJoke['unixtime']},'{$time}','{$time}')";

                        DB::connection('masterDB')->insert($sql);
                    }
                }

            }catch (\Exception $e)
            {

            }
        }

        return true;
    }
}
