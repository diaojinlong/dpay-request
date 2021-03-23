<?php

namespace DpayRequest;

class Request
{

    private $host = ''; //请求地址，请在dPay后台获取

    private $appid = ''; //请求AppId，请在dPay后台获取

    private $secret = ''; //加密Secret，请在dPay后台获取

    private $timeout = 10; //请求超时时间

    private $path = [ // 接口地址
        'create_orders' => '/api/orders/create',
        'query_orders' => '/api/orders/query',
    ];

    /**
     * Request constructor.
     * @param $config ['host'=>'http://127.0.0.1', 'appid'=>56441210, 'secret'=>'f24223830-c348-4421-922a-f2ffb8c3869a']
     * @throws Exception
     */
    public function __construct($config)
    {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
        if (empty($this->appid)) {
            throw new Exception('请求appid必填');
        }
        if (empty($this->secret)) {
            throw new Exception('请求密钥secret必填');
        }
    }

    /**
     * 创建订单
     * @param $price
     * @param $payType
     * @param $outTradeNo
     * @param $notifyUrl
     * @param $returnUrl
     * @param string $description
     * @param int $endPayTime
     * @return bool|mixed|string
     * @throws Exception
     */
    public function createOrders($price, $payType, $outTradeNo, $notifyUrl, $returnUrl, $description = '', $endPayTime = 5)
    {
        $data = array(
            'price' => $price,
            'pay_type' => $payType,
            'end_time' => $endPayTime,
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'out_trade_no' => $outTradeNo,
            'description' => $description
        );
        return $this->methodPost($this->path['create_orders'], $data);
    }

    /**
     * 查询订单
     * @param string $outTradeNo
     * @param string $prepayId
     * @return bool|mixed|string
     * @throws Exception
     */
    public function queryOrders($outTradeNo = '', $prepayId = '')
    {
        if (empty($outTradeNo) && empty($prepayId)) {
            throw new Exception('商户单号或支付单号必填其一');
        }
        $data = array(
            'out_trade_no' => $outTradeNo,
            'prepay_id' => $prepayId
        );
        return $this->methodGet($this->path['query_orders'], $data);
    }


    /**
     * get请求
     * @param $path
     * @param $data
     * @return bool|mixed|string
     * @throws Exception
     */
    public function methodGet($path, $data)
    {
        return $this->methodRequest($path, $data, 'get');
    }

    /**
     * post请求
     * @param $path
     * @param $data
     * @return bool|mixed|string
     * @throws Exception
     */
    public function methodPost($path, $data)
    {
        return $this->methodRequest($path, $data, 'post');
    }

    /**
     * 请求
     * @param $path
     * @param $requestData
     * @param $method
     * @return bool|mixed|string
     * @throws Exception
     */
    private function methodRequest($path, $requestData, $method)
    {
        $requestData = $this->createData($requestData);
        $url = $this->host . $path;
        if ($method == 'get') {
            $url .= '?' . http_build_query($requestData);
        }
        $header = array(
            'Accept: application/json',
        );
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        if ($method == 'post') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $requestData);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($curl);
        if (curl_error($curl)) {
            throw new Exception(curl_error($curl));
        } else {
            curl_close($curl);
            $data = json_decode($data, true);
            if (is_null($data)) {
                throw new Exception('服务端返回不是JSON格式');
            }
            return $data;
        }
    }


    /**
     * 生成随机字符串
     * @param int $length
     * @return string
     */
    private function getNonceStr($length = 32)
    {
        $chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
            'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's',
            't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D',
            'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
            'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');

        $keys = array_rand($chars, $length);
        $nonceStr = '';
        for ($i = 0; $i < $length; $i++) {
            $nonceStr .= $chars[$keys[$i]];
        }
        return $nonceStr;
    }

    /**
     * 合成请求必要参数
     * @param $data
     * @return mixed
     */
    public function createData($data)
    {
        $data['app_id'] = $this->appid;
        $data['time_stamp'] = time();
        $data['nonce_str'] = $this->getNonceStr();
        $data['pay_sign'] = $this->createPaySign($data);
        return $data;
    }

    /**
     * 加密
     * @param $data
     * @return string
     */
    public function createPaySign($data)
    {
        $data = $this->unsetEmpty($data);
        ksort($data);
        $url = urldecode(http_build_query($data));
        return strtoupper(md5($url . $this->secret));
    }

    /**
     * 校验密钥
     * @param $data
     * @return bool
     */
    public function validationPaySign($data)
    {
        $paySign = $data['pay_sign'];
        unset($data['pay_sign']);
        return $paySign === $this->createPaySign($data);
    }

    /**
     * 去除空值
     * @param $data
     * @return mixed
     */
    private function unsetEmpty($data)
    {
        foreach ($data as $key => $vo) {
            if ($vo === null || $vo === '') {
                unset($data[$key]);
            }
        }
        return $data;
    }
}
