<?php

return [

	// The default gateway to use
	'default' => 'paypal',

    // Add in each gateway here
    'gateways' =>
        [
            'wodelu_app_alipay' =>
                [
                    'driver' => 'Alipay_AopApp',
                    'options' =>
                        [
                            'appId' => '2019110168840479',
                            'notifyUrl' => 'http://newfogapi.wodeluapp.com/wodelu/alipay/notify',
                            'privateKey' => storage_path('app/key/wodelu/alipay/newfogapi.wodeluapp.com_privateKey.txt'),
                            'alipayPublicKey' => storage_path('app/key/wodelu/alipay/alipayCertPublicKey_RSA2.crt'),
                            'environment' => 'production',
                            'signType' => 'RSA2',
                        ],
                ],
        ]

];
