<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GridReducePrice extends Command
{
    protected $signature = 'Grid:ReducePrice';

    protected $description = 'n天不交易的格子自动降价m%';

    protected $n=7;
    protected $m=10;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //几天前
        $daysAgo=Carbon::now()->subDays($this->n)->endOfDay()->format('Y-m-d H:i:s');

        //当天时间
        $daysNow=Carbon::now()->format('Y-m-d H:i:s');

        //降价比例
        $this->m=$this->m/100;

        //降价sql，被修改过价格的格子，update_at改成当天时间
        $sql="update grid set price=case when round(price - price * {$this->m}) < 10 then 10 else round(price - price * {$this->m}) end,updated_at={$daysNow} where updated_at <= {$daysAgo}";

        //执行sql
        try
        {
            DB::connection('masterDB')->update($sql);

        }catch (\Exception $e)
        {

        }

        return true;
    }
}
