<?php

return [

	// The default gateway to use
	'default' => 'wodelu_app_alipay',

    // Add in each gateway here
    'gateways' =>
        [
            'wodelu_app_alipay' =>
                [
                    'driver' => 'Alipay_AopApp',
                    'options' =>
                        [
                            'appId' => '2019110568930442',
                            'notifyUrl' => 'http://newfogapi.wodeluapp.com/wodelu/alipay/notify',
                            'privateKey' => storage_path('app/key/wodelu/alipay/wodeluPrivateKey.txt'),
                            'alipayPublicKey' => storage_path('app/key/wodelu/alipay/alipayPublicKey.txt'),
                            'environment' => 'production',
                            'signType' => 'RSA2',
                        ],
                ],
        ]

];
