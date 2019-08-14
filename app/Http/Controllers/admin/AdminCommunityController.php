<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;

class AdminCommunityController extends AdminBaseController
{
    public function ajax(Request $request)
    {

    }

    public function checkCommunity(Request $request)
    {
        return view('admin.community.check_community');
    }









}