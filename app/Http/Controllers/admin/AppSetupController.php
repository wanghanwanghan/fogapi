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

                $androidVer=trim($request->androidVer);

                if ($androidVer=='') break;

                if (!preg_match('/\d+\.\d+\.\d+/',$androidVer)) break;

                Redis::connection('default')->set('tssjAndroidAppVersion',$androidVer);

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