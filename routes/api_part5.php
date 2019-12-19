<?php

Route::group(['middleware'=>['PVandUV']],function ()
{
    //探索世界支付路由================================================================================
    //探索世界安卓支付宝支付
    Route::match(['get','post'],'TssjAlipay','Server\PayBase@tssjAlipay');
    //===========================================================================================







    //有几率得到一个碎片
    Route::match(['get','post'],'GetOnePatch','QuanMinZhanLing\FoodMap\FoodMapController@getOnePatch');

    //根据碎片中文名称换取碎片详细信息
    Route::match(['get','post'],'GetPatchInfoByPatchName','QuanMinZhanLing\FoodMap\FoodMapController@getPatchInfoByPatchName');

    //购买碎片或者下架碎片
    Route::match(['get','post'],'BuyPatchOrCancel','QuanMinZhanLing\FoodMap\FoodMapController@buyPatchOrCancel');

    //出售碎片
    Route::match(['get','post'],'SaleUserPatch','QuanMinZhanLing\FoodMap\FoodMapController@saleUserPatch');

    //拍卖行
    Route::match(['get','post'],'AuctionHouse','QuanMinZhanLing\FoodMap\FoodMapController@auctionHouse');

    //许愿池
    Route::match(['get','post'],'WishPool','QuanMinZhanLing\FoodMap\FoodMapController@wishPool');

    //获取用户已经收集到宝物个数
    Route::match(['get','post'],'GetUserTreasureNum','QuanMinZhanLing\FoodMap\FoodMapController@getUserTreasureNum');

    //获取用户宝物页
    Route::match(['get','post'],'GetUserTreasure','QuanMinZhanLing\FoodMap\FoodMapController@getUserTreasure')->middleware('AfterControllerMiddleware');





});

