<?php

Route::fallback(function () {return 'no page';});

Route::group(['prefix'=>'admin'],function ()
{
    //管理后台路由
    Route::get('/',function (){



        $res=\Illuminate\Support\Facades\DB::connection('masterDB')
            ->table('buy_sale_info_201904')
            ->where('uid',22357)->groupBy('gname')->select('gname','count(1)')->toSql();


        dd($res);





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



