<?php

Route::fallback(function () {return 'no page';});

Route::group(['prefix'=>'admin'],function ()
{
    Route::group(['middleware'=>['AdminLogin']],function (){

        //管理后台路由
        Route::get('/',function (){





            $info=\Illuminate\Support\Facades\Redis::connection('default')->get('ServerInfo');

            $info=json_decode($info,true);

            return view('admin.index')->with(['info'=>$info]);

        })->name('main');

        //系统公告
        Route::match(['get','post'],'/sys/create/grid','admin\\AdminSysController@sysCreateForGrid')->name('sysCreateForGrid');
        Route::match(['get','post'],'/sys/create/msg/grid','admin\\AdminSysController@sysCreateMsgForGrid')->name('sysCreateMsgForGrid');

        Route::match(['get','post'],'/sys/create/user','admin\\AdminSysController@sysCreateForUser')->name('sysCreateForUser');
        Route::match(['get','post'],'/sys/create/msg/user','admin\\AdminSysController@sysCreateMsgForUser')->name('sysCreateMsgForUser');

        Route::match(['get','post'],'/sys/create/msg/detail/{id}','admin\\AdminSysController@sysMsgDetail')->name('sysMsgDetail');

        Route::match(['post'],'/sys/ajax','admin\\AdminSysController@sysAjax');



        //审核
        Route::match(['get','post'],'/grid/img','admin\\AdminGridController@gridImg')->name('gridImg');
        Route::match(['get','post'],'/grid/img2','admin\\AdminGridController@gridImg2')->name('gridImg2');
        Route::match(['post'],'/grid/ajax','admin\\AdminGridController@gridAjax');



        //微信支付
        Route::match(['get'],'/wechat/index','admin\\WechatController@index')->name('wechatIndex');
        Route::match(['get'],'/wechat/makeQr','admin\\WechatController@makeQr');//请求二维码
        Route::match(['get'],'/wechat/listening','admin\\WechatController@listening');//监听是否支付成功

        //mysql
        Route::match(['get'],'/mysql/slowSelect','admin\\MysqlController@slowSelect')->name('slowSelect');

        //editor
        Route::match(['get'],'/editor/wangEditor','admin\\EditorController@wangEditor')->name('wangEditor');
        Route::match(['post'],'/editor/wangEditor/uploadPic','admin\\EditorController@uploadPic');











        //系统安全相关
        Route::match(['post'],'/security/ajax','QuanMinZhanLing\\SecurityController@ajax');

    });
});



