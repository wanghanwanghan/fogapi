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

            //王瀚
            '18618457910'=>['key'=>'U4566KUVXHCHGMMU','password'=>'wanghan123'],

            //纪申
            '18600409656'=>['key'=>'6XZZWO2KV6S5NJ4H','password'=>'jishen123'],

            //小超
            '15210929119'=>['key'=>'67KMTHPZHOZTST2S','password'=>'xiaochao123'],

            //璀璀
            '18511936093'=>['key'=>'3I4RRIIVTPEO3FKN','password'=>'cuicui123'],

            //唐兆丰
            '15011372771'=>['key'=>'RIFR65PFW3JVTUYH','password'=>'zhaofeng123'],

        ];

        if (!array_key_exists($phone,$userAccount)) return false;

        if (GoogleAuthWithApp::CheckCode($userAccount[$phone]['key'],$code))
        {
            session()->put('adminLastLogin',time());

            return true;
        }

        return false;
    }

    //登陆首页
    public function adminLogin()
    {
        $res=['code'=>'纪申嘿嘿嘿','qrCode'=>QrCode::size(300)->margin(1)->generate('https://source.unsplash.com/Mv9hjnEUHR4/600x800')];

        if (0) $res=$this->createGoogleAuth();

        return view('admin.login.login_index')->with($res);
    }









}