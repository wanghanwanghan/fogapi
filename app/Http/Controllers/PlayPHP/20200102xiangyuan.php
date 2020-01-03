<?php

interface Way
{
    public function way($user);
}

class Bike implements Way
{
    public function way($user)
    {
        echo $user.'骑车'.PHP_EOL;
    }
}

class Walk implements Way
{
    public function way($user)
    {
        echo $user.'走路'.PHP_EOL;
    }
}

class CanFactory
{
    private $obj=[];

    public function getObj($way)
    {
        if (isset($this->obj[$way])) return $this->obj[$way];

        //反射new这个类
        $create=new ReflectionClass($way);

        $this->obj[$way]=$create->newInstance();

        return $this->obj[$way];
    }
}

$wanghan=new CanFactory();

$wanghan->getObj('Walk')->way('wanghan');


