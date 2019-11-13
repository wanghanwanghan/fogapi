<?php

namespace App\Http\Controllers\QuanMinZhanLing;

use App\Http\Controllers\Controller;

class BaseController extends Controller
{
    //uid判断
    public function checkInputUserId($uid)
    {
        if (!is_numeric($uid) || $uid < 1)
        {
            return false;//604
        }

        return true;
    }
















}
