<?php

Route::fallback(function () {return 'no page';});

Route::group(['prefix'=>'admin'],function ()
{
    //管理后台路由
    Route::get('/',function (){


        $haveCount=\Illuminate\Support\Facades\DB::connection('masterDB')->table('buy_sale_info_201904')->where('uid',111)->count();


        dd($haveCount,'321');


        return view('admin.index');

    })->name('main');

    //系统公告
    Route::match(['get','post'],'/sys/create','admin\\AdminSysController@sysCreate')->name('sysCreate');
    Route::match(['post'],'/sys/ajax','admin\\AdminSysController@sysAjax');



    //审核
    Route::match(['get','post'],'/grid/name','admin\\AdminGridController@gridName')->name('gridName');
    Route::match(['get','post'],'/grid/img','admin\\AdminGridController@gridImg')->name('gridImg');
    Route::match(['post'],'/grid/ajax','admin\\AdminGridController@gridAjax');





});



