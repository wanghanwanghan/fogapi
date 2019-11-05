<?php

//我的路支付回调（阿里）
Route::match(['get','post'],'wodelu/alipay/notify','Server\PayBase@wodeluAlipayNotify');





