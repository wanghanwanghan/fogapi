<?php

class ATest
{
    public function say()
    {
        echo 'Segmentfault'.PHP_EOL;
    }

    public function callSelf()
    {
        self::say();
    }

    public function callStatic()
    {
        static::say();
    }
}

class BTest extends ATest
{
    public function say()
    {
        echo 'PHP'.PHP_EOL;
    }
}

$b = new BTest();
$b->say(); // output: php
$b->callSelf(); // output: segmentfault
$b->callStatic(); // output: php
