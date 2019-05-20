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
        Route::match(['get','post'],'/grid/name','admin\\AdminGridController@gridName')->name('gridName');
        Route::match(['get','post'],'/grid/img','admin\\AdminGridController@gridImg')->name('gridImg');
        Route::match(['post'],'/grid/ajax','admin\\AdminGridController@gridAjax');


        //系统安全相关
        Route::match(['post'],'/security/ajax','QuanMinZhanLing\\SecurityController@ajax');

    });
});



