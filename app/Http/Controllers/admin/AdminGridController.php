<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminGridController extends AdminBaseController
{
    //首页
    public function index(Request $request)
    {
        return true;
    }

    //ajax
    public function gridAjax(Request $request)
    {
        $res=DB::connection('masterDB')->table('grid_info')->orderby('updated_at')->limit(10)->get();

        return $res;
    }

    //审核格子名称页面
    public function gridName(Request $request)
    {
        return view('admin.grid.grid_name');
    }

    //审核格子图片页面
    public function gridImg(Request $request)
    {
        return view('admin.grid.grid_img');
    }












}