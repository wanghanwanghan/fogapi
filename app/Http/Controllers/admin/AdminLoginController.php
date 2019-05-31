<?php

namespace App\Http\Controllers\admin;

use GoogleAuthWithApp;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminLoginController extends AdminBaseController
{
    //新建二维码
    public function createGoogleAuth()
    {
        $obj=GoogleAuthWithApp::CreateSecret();

        isset($obj['secret'])  ? $code=$obj['secret'] : $code='没生成出来';
        isset($obj['codeurl']) ? $url=$obj['codeurl'] : $url='http://www.baidu.com';

        $qr=QrCode::size(300)->margin(1)->generate($url);

        return ['code'=>$code,'qrCode'=>$qr];
    }

    //ajax
    public function loginAjax(Request $request)
    {
        switch ($request->type)
        {
            case 'login_check':

                $phone=trim($request->phoneNum);
                $code=trim($request->googleCode);

                $res=$this->canLogin($phone,$code);

                if ($res==true)
                {
                    return ['error'=>'0'];
                }

                return ['error'=>'1'];

                break;

            case '':

                break;

            default:

                break;
        }
    }

    //允许登陆的账号密码
    public function canLogin($phone,$code)
    {
        $userAccount= [

            '18618457910'=>'wanghan123',

        ];

        if (array_key_exists($phone,$userAccount)) return true;

        return false;

        Google::CheckCode('绑定时候的密钥',$code);

    }

    //登陆首页
    public function adminLogin()
    {
        $res=['code'=>'','qrCode'=>''];

        if (0) $res=$this->createGoogleAuth();

        return view('admin.login.login_index')->with($res);
    }









}