<?php

use Illuminate\Http\Request;

Route::post('alipay/notify', function(Request $request) {

    $gateway = \Omnipay::gateway('alipay_aoppage');
    $omnipayRequest = $gateway->completePurchase();
    $omnipayRequest->setParams($request->all());

    $response = $omnipayRequest->send();

    if (!$response->isPaid()) {
        return response('fail');
    }

    return response('success');
});


Route::get('wanghan/send', function(Request $request) {

    dd(str_random());

});
