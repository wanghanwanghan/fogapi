<?php

namespace App\Http\Controllers\admin;

use GoogleAuthWithApp;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminLoginController extends AdminBaseController
{
    public function createGoogleAuth()
    {
        $obj=GoogleAuthWithApp::CreateSecret();

        isset($obj['secret'])  ? $code=$obj['secret'] : $code='没生成出来';
        isset($obj['codeurl']) ? $url=$obj['codeurl'] : $url='http://www.baidu.com';

        $qr=QrCode::size(300)->margin(1)->generate($url);

        return ['code'=>$code,'qrCode'=>$qr];
    }

    public function adminLogin()
    {
        return view('admin.login.login_index')->with($this->createGoogleAuth());
    }









}