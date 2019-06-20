<?php

Route::group(['middleware'=>['PVandUV']],function ()
{

    Route::match(['get','post'],'FogUpload','TanSuoShiJie\\FogController@fogUpload');












});

