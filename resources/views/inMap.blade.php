<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />

    <!--  css & js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/social-share.js/1.0.16/css/share.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/social-share.js/1.0.16/js/social-share.min.js"></script>

</head>
<body>


<div style="margin-top: 500px"></div>


<div class="social-share" data-mode="prepend">
    <a href="javascript:;" onclick="alert(123);" class="social-share-icon icon-heart"></a>
</div>




</body>
</html>



<script>


    var $config = {
        url                 : 'https://www.baidu.com', // 网址，默认使用 window.location.href
        source              : '', // 来源（QQ空间会用到）, 默认读取head标签：<meta name="site" content="http://overtrue" />
        title               : 'test123', // 标题，默认读取 document.title 或者 <meta name="title" content="share.js" />
        origin              : '', // 分享 @ 相关 twitter 账号
        description         : '123test', // 描述, 默认读取head标签：<meta name="description" content="PHP弱类型的实现原理分析" />
        image               : 'https://laravelacademy.org/wp-content/uploads/2017/09/logo.png', // 图片, 默认取网页中第一个img标签
        sites               : ['qzone', 'qq', 'weibo','wechat', 'douban'], // 启用的站点，和禁用只会生效一个
        //disabled            : ['google', 'facebook', 'twitter'], // 禁用的站点，和禁用只会生效一个
        wechatQrcodeTitle   : '微信扫一扫：分享', // 微信二维码提示文字
        wechatQrcodeHelper  : '<p>微信里点“发现”，扫一下</p><p>二维码便可将本文分享至朋友圈。</p>'
    };

    socialShare('.social-share', $config);




</script>