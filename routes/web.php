<?php

Route::fallback(function () {return 'no page';});

Route::group(['prefix'=>'admin'],function ()
{
    //管理后台路由
    Route::get('/',function (){

        return view('admin.index');

    })->name('main');

    //审核
    Route::match(['get','post'],'/grid/name','admin\\AdminGridController@gridName')->name('gridName');
    Route::match(['get','post'],'/grid/img','admin\\AdminGridController@gridImg')->name('gridImg');
    Route::match(['post'],'/grid/ajax','admin\\AdminGridController@gridAjax');





});







