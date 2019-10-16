<?php

Route::group(['middleware'=>['PVandUV']],function ()
{

    Route::match(['get','post'],'xxxxx','QuanMinZhanLing\Community\CommunityController@relationDetail');







});

