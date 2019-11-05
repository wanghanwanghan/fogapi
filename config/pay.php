<?php

return [
    'alipay' => [
        // 支付宝分配的 APPID
        'app_id' => '2019110568930442',

        // 支付宝异步通知地址
        'notify_url' => 'http://newfogapi.wodeluapp.com/wodelu/alipay/notify',

        // 支付成功后同步通知地址
        'return_url' => '',

        // 阿里公共密钥，验证签名时使用
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtOjdLtvmH8Rs9PhEkoSWe5e0LWyweflkTWCtCTiV8yMXh8NMLGrx4dCIpfj2pyBad7cSKOAlNYaXu4rjkY+EnUFgffsA5XVbQvpFFIAVCALLmSb0z2b0X+Za6ax8Igh8Y8kZo4RS7axsix5mIe1HncVyOY7vGBVOTQxfHBsU9/kch/xvjFKq/b+M/NXOcqgE/PIdfftjvoI9eRNk7OvMy3x1kt11eYhwILZRZ/khtBXWWPMcZ4BvQEDbokWgrWR7aaL2LfpmmBmdafWUXOgmk962PdFH0688W6UcVh7NChQrUyJY7KVq2e0JCfxwiyKm9JLJvDQrtjqgAvLDsLkoLwIDAQAB',

        // 自己的私钥，签名时使用
        'private_key' => 'MIIEpAIBAAKCAQEAismpJ8xz7CE4N8oDlKAcDytIg/kQHiIH31Vj3ZWQCoJkSQuc7LixSxURqY3a0qs/YUs3/k2ro9qpEE2WbXMg8PzP/mFEi4URfwr4QawS6KESWc/aeywQ2JR1vQxH3YgOwZDT1xQslDLyLsChWyc/Ja/Q6a/pcteOGPdLWtxo8LP11Rh9l6SvGzf99vopBwLmjDNsu7qLZ2RvuvRZYLgu3NdVC+D0Oj0uXP2cSpd0TTq9EhgDlUIP+PRWyJjW6VepmpWeZnP1jmlOnhdS6K/166HIRUtSl+JEDN5Adcur2xPHO5AZBgPVIhtWiIcDzXb35xPM3MwoQTK/WQ3Ya4k2kwIDAQABAoIBAQCJ0egglW8oNXTWMc2McdJrXdgM9e+DfNfEd89L45G+Xe2oe9fBW0b0AGAht7RtL5Eo1MEtz4N6m1D315Rh68nyhZsmSQEAa4wMVKBi1rWQPSMz/KxBVGkGKachrGhRHSNKJL+4/Venc0/8DV4uXrLegdE7vmxclqGNOUWjXOz42Ltl71MN4x1NqoEM2v0N0s0CsLCSjWI2FfDqqyP4Dwqj+3YY6QFTHhIujeiqSxSmCzQmsQAmoA++LmQ7OVCMoub9tuIrY/vYdzOAjZSPWY5OIshR5os49/kjVnMoQePGzTjprurJsy3jctri1RsVP808+PAntVuHo1M85aeSJJqBAoGBAO8wYSRNYaUHw/KZGeJ8RfFfPBZpLwxWHmpksnt5Cq2MxHSBBk9TcOWvIBNB9/fvRNwFKBuVwVaAk/WiB466UvsbWbI6Aro2U1pkRJKMiM9eBqBn95z4us0YzslmFDQLQFFJZT3qCLxOWM7IjuPxYcoMK9BzwYHgijn0cqLMDpi5AoGBAJSKznl+acPdnFRon2EJHjeVxFAsWYFBtCJ5jKyEgdjY+J+y+wRUqJPHAjstvduGVfH5L5KPsVm3ktCTbaTuRecLV52ePch3AfyxJjfsMZuGRP/xiNcjV7rRoo6S5BM7+xzclJLy09NJQxZoZCJWI7U/TKKQWyqPHGjxtahD40urAoGAKq/HpmLo+jrYjYMlS3ZCRUFpx9Ydv2XfJ72w3LhNX4uvw9sC9UU+8L7ASq+LQYRCFcIV1lZzmqx3u26fSqmWBZpr95LCydjIJ2mdk0nhYKC3Lglf93OsYs36mZbOJDudzuP6XYJW7MROtRi155g8i4KAj0MZVcRs5srpaoRgVoECgYAZfVA9DPI9SBQBcAqZbiUfSLXtRA7/3TR6Df2TxRE7EtBnJyFn9tcOtMGvQgQoXX72DNqa/cljKdspq4LPIIwiP5IDXBoiAjn3ELcMNZ21oG7KtLnUoR320u5gJNi6bDqFE1zzcnPi99lpSPSV584s1fXil37taK1pXMDdZPjmDQKBgQCi1VowzV6QmgFqnK9M8JEyXdsy7VFaq6gd0IyyPaF5/vVpTnzZAopHEXsNFsv3l3ey4thMI25w+fxu72tZwnIn1KTp6CRMjzVGIdx7GsBhAkTVY0fOPfrWAi2snpkadG1ALRXD+U5CZEqZXHinNc71llxXYMvlt850FBZAIRkaoA==',

        // optional，默认 warning；日志路径为：sys_get_temp_dir().'/logs/yansongda.pay.log'
        'log' => [
            'file' => storage_path('logs/alipay.log'),
        //  'level' => 'debug'
        //  'type' => 'single', // optional, 可选 daily.
        //  'max_file' => 30,
        ],

        // optional，设置此参数，将进入沙箱模式
        // 'mode' => 'dev',
    ],

    'wechat' => [
        // 公众号 APPID
        'app_id' => env('WECHAT_APP_ID', ''),

        // 小程序 APPID
        'miniapp_id' => env('WECHAT_MINIAPP_ID', ''),

        // APP 引用的 appid
        'appid' => env('WECHAT_APPID', ''),

        // 微信支付分配的微信商户号
        'mch_id' => env('WECHAT_MCH_ID', ''),

        // 微信支付异步通知地址
        'notify_url' => 'http://requestbin.fullcontact.com/1lnzhce1',

        // 微信支付签名秘钥
        'key' => env('WECHAT_KEY', ''),

        // 客户端证书路径，退款、红包等需要用到。请填写绝对路径，linux 请确保权限问题。pem 格式。
        'cert_client' => '',

        // 客户端秘钥路径，退款、红包等需要用到。请填写绝对路径，linux 请确保权限问题。pem 格式。
        'cert_key' => '',

        // optional，默认 warning；日志路径为：sys_get_temp_dir().'/logs/yansongda.pay.log'
        'log' => [
            'file' => storage_path('logs/wechat.log'),
            'level' => 'debug',
            'type' => 'daily', // optional, 可选 daily.
            'max_file' => 30,
        ],

        // optional
        // 'dev' 时为沙箱模式
        // 'hk' 时为东南亚节点
        // 'mode' => 'dev',
    ],
];
