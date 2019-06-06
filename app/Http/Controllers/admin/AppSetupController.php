<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class AppSetupController extends AdminBaseController
{
    public function ajax(Request $request)
    {
        switch ($request->type)
        {
            case 'updateVer':

                //最新版本号
                $androidVer=trim($request->androidVer);

                //最新版本下载url
                $androidUrl=trim($request->androidUrl);

                if ($androidVer=='' || $androidUrl=='') break;

                Redis::connection('default')->hset('tssjAndroidAppVersion','ver',$androidVer);
                Redis::connection('default')->hset('tssjAndroidAppVersion','url',$androidUrl);

                return ['resCode'=>200];

                break;

            case 'appleUpdateVer':

                //最新版本号
                $appleVer=trim($request->appleVer);

                if ($appleVer=='') break;

                Redis::connection('default')->hset('tssjAppleAppVersion','ver',$appleVer);
                Redis::connection('default')->hset('tssjAppleAppVersion','url',0);

                return ['resCode'=>200];

                break;

            default:

                break;
        }

        return ['resCode'=>400];
    }

    public function appSetupIndex()
    {
        return view('admin.appsetup.index');
    }











}