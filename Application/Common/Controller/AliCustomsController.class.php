<?php
namespace Common\Controller;

use Common\Model\BaseModel;
use Common\Model\PayModel;
use PlugInUnit\AliPayCustoms\lib\AlipaySubmit;
use Think\Controller;


class AliCustomsController extends AlipaySubmit{

    public function aliCutoms($order_id){

        header("Content-type:text/html;charset=utf-8");

        $this->getAlipayConfig();

        $alipay_config = $this->alipay_config;

        $co = C('CUSTOMS');
        $order = M('order')->field('id,price_sum,trade_no')->where(['id'=>$order_id])->find();

        $out_request_no        = date('Ymd').rand(1000,9999);//报关流水号
        $trade_no              = $order['trade_no'];//支付宝交易号
        $amount                = $order['price_sum'];//报关金额

        $merchant_customs_code = $co['coCode'];//商户海关备案编号
        $merchant_customs_name = $co['coName'];//商户海关备案名称
        $customs_place         = $co['custCode'];//海关编号

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service"               => "alipay.acquire.customs",
            "partner"               => trim($alipay_config['partner']),
            "out_request_no"        => $out_request_no,
            "trade_no"              => $trade_no,
            "merchant_customs_code" => $merchant_customs_code,
            "merchant_customs_name" => $merchant_customs_name,
            "amount"                => $amount,
            "customs_place"         => $customs_place,
            "_input_charset"        => trim(strtolower($alipay_config['input_charset']))
        );

        //建立请求
        $html_text = $this->buildRequestHttp($parameter);

        //处理操作+++++

    }

    public function getAlipayConfig(){

        $alipay_config['sign_type']    = strtoupper('MD5');//签名方式 不需修改
        $alipay_config['input_charset']= strtolower('utf-8');//字符编码格式 目前支持 gbk 或 utf-8
        $alipay_config['cacert']    =  APP_PATH . 'PlugInUnit/AliPayCustoms/cacert.pem';//ca证书路径地址，用于curl中ssl校验
        $alipay_config['transport']    = 'http';//访问模式,根据自己的服务器是否支持ssl访问，


        $payModel = BaseModel::getInstance(PayModel::class);
        $data = $payModel->getPayInfo(2,0);
        $alipay_config['partner'] = $data[PayModel::$payAccount_d];//合作身份者id，以2088开头的16位纯数字
        $alipay_config['key']	  = $data[PayModel::$payKey_d];//安全检验码，以数字和字母组成的32位字符

        $this->AlipaySubmit($alipay_config);

    }


}