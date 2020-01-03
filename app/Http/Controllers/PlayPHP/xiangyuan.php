<?php

//抽象享元角色
interface Way
{
    public function way($user);
}

//具体享元角色
class WalkWay implements Way
{
    public function __construct($arr)
    {
    }

    public function way($user)
    {
        echo $user.'走路去公司!'.PHP_EOL;
    }
}

class BikeWay implements Way
{
    public function __construct($arr)
    {
    }

    public function way($user)
    {
        echo $user.'骑车去公司!'.PHP_EOL;
    }
}

//享元工厂
class CanFactory
{
    //共享池
    private $con=[];

    public function instance($class)
    {
        if (isset($this->con[$class])) return $this->con[$class];

        try
        {
            $c=new ReflectionClass($class);

            $this->con[$class]=$c->newInstance([]);

            return $this->con[$class];

        }catch (ReflectionException $e)
        {
            echo '你要的方式木有哦!'.PHP_EOL;

            return null;
        }
    }
}

$f=new CanFactory();
$f->instance('BikeWay')->way('111');
$f->instance('WalkWay')->way('222');
