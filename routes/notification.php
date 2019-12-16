<?php

//我的路支付回调（阿里）
Route::match(['get','post'],'wodelu/alipay/notify','Server\PayBase@wodeluAlipayNotify');

//我的路支付回调（苹果内购）
Route::match(['get','post'],'wodelu/applepay/notify','Server\PayBase@wodeluApplePayNotify');








//探索世界支付回调（阿里）
Route::match(['get','post'],'tssj/alipay/notify','Server\PayBase@tssjAlipayNotify');

//探索世界支付回调（苹果内购）
Route::match(['get','post'],'tssj/applepay/notify','Server\PayBase@tssjApplePayNotify');

