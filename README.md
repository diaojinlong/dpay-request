# dPay-Request
基于多支付平台开发的扩展包，方便开发者快速接入多支付。


# 安装

composer require diaojinlong/dpay-request


# 使用

<?php

$config = [
    'host'=>'多支付平台接口域名', //例如：http://127.0.0.1
    'appid'=>'多支付平台AppID',
    'secret'=>'多支付平台Secret'
];

//初始化类
$dPay = new \DpayRequest\Request($config);

$price = 50.00; //支付金额
$payType = 1; //支付方式:1=微信,2=支付宝
$outTradeNo = 'cs1234567890' //商户单号
$notifyUrl = 'http://xxx.xxx.xx/notify'; //请填写自己服务器接收成功推送通知的接口
$returnUrl = 'http://xxx.xxx.xx/return'; //请填写自己服务器成功页面地址
$description = '会员充值'; //填写支付说明
$endPayTime = 5; //创建的订单5分钟后失效

//创建订单
$createOrder = $dPay->createOrders($price, $payType, $outTradeNo, $notifyUrl, $returnUrl, $description, $endPayTime);
var_dump($createOrder);
if($createOrder['code'] == 200){
    echo '创建成功';
}else{
    echo '创建失败';
}
$prepayId = $createOrder['data']['prepay_id']; //支付单号

//商户单号查询订单
$queryOrder = $dPay->queryOrders($outTradeNo);

//支付单号查询订单
$queryOrder = $dPay->queryOrders('', $prepayId);



