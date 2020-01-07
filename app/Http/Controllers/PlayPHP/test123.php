<?php

function rev($arr)
{
    if (count($arr)===1) return;

    for ($i=0;$i<count($arr);$i++)
    {
        if (isset($arr[$i+1])) echo $arr[0].'-'.$arr[$i+1].PHP_EOL;
    }

    unset($arr[0]);

    rev(array_values($arr));
}

$arr=[7,6,5,4];

rev($arr);
