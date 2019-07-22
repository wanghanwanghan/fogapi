<?php

namespace App\Http\Controllers\Server;

use Illuminate\Support\Facades\Config;

class MongoClientBase
{
    public $storeObj=null;

    public function getConfig()
    {
        return Config::get('database.connections.TssjFogMongoDB');
    }

    public function getMongoClient()
    {
        //mongo对象存在，直接返回
        if ($this->storeObj!=null) return $this->storeObj;

        $config=$this->getConfig();

        //不存在时候，new一个再返回
        $this->storeObj=new \MongoDB\Client("mongodb://{$config['host']}:{$config['port']}");

        return $this->storeObj;
    }
}