<?php
// +----------------------------------------------------------------------
// | OnlineRetailers [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2003-2023 www.yisu.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 亿速网络（http://www.yisu.cn）
// +----------------------------------------------------------------------
// | Author: 王强 <opjklu@126.com>
// +----------------------------------------------------------------------
namespace Common\Pay;

use Common\Model\PayModel;
use PlugInUnit\PCAlipay\RSA\Lib\AlipaySubmit;
use Common\TraitClass\PayTrait;

class Alipay extends AlipaySubmit
{
    use PayTrait;

    /**
     * 支付宝支付
     */
    public function pay($obj)
    {
        header("Content-type:text/html;charset=utf-8");
        if (! is_object($obj)) {
            throw new \Exception('参数错误');
        }
        
        $info = $obj->getInfo();
        
        if (empty($info['price_sum']) || empty($info['order_sn_id'])) {
            $obj->showMessage('参数错误');
        }
        
        // $goodsModel = $obj->getGoodsModel();
        
        // $title = $goodsModel->getUserNameById($info['goods_id'], $goodsModel::$title_d);
        
        // $obj->promptParse($title, '参数错误');die();

        $pay_order_id = $info['order_sn_id'];
        $type = explode('-', $pay_order_id)[2];
        $order_id = explode('-', $pay_order_id)[0];

        $info['order_sn_id'] = $order_id.'-'.$type;
        
        // 获取支付宝配置
        $alipay_config = C('ALIPAY_CONFIG');
        if (empty($alipay_config)) {
            $obj->error('参数错误');
            die();
        }
        
        $data = $this->getPayData();

        if (empty($data)) {

            $obj->showMessage($data, '参数错误');
        }
        $alipay_config['partner'] = $data[PayModel::$payAccount_d];
        $alipay_config['seller_id'] = $data[PayModel::$mchid_d];
        $alipay_config['key'] = $data[PayModel::$payKey_d];
        
        $alipay_config['private_key'] =  $data[PayModel::$privatePem_d];
        
        $alipay_config['alipay_public_key'] = $data[PayModel::$publicPem_d];
        
        $urlNofity = $obj->getNofityURL();
        
        $urlNofity = empty($urlNofity) ? $alipay_config['return_url'] : U('RechargeNofity/nofity', [
            'callBack' => 'rechargeAl'
        ], true, true);
            
        $parameter = array(
            "_input_charset" => trim(strtoupper($alipay_config['input_charset'])),
            "anti_phishing_key" => $alipay_config['anti_phishing_key'],
            "body" => $data['id'],
            "exter_invoke_ip" => $alipay_config['exter_invoke_ip'],
            "notify_url" => $alipay_config['notify_url'],
            "out_trade_no" => $info['order_sn_id'],
            "partner" => $data[PayModel::$payAccount_d],
            "passback_params" => urlencode( json_encode(['pay_order_id'=>$info['order_id_str'],'user_id'=>$_SESSION['user_id']])) ,
            "payment_type" => $alipay_config['payment_type'],
            "return_url" => $urlNofity,
            "seller_id" => $data[PayModel::$mchid_d],
            "service" => $alipay_config['service'],
            "show_url"  => 'http://' . $_SERVER[ 'SERVER_NAME' ],
            "sign_type" => $alipay_config['sign_type'],
            "subject" => '2天清仓',
            "sys_service_provider_id" => $data['id'],
            "total_fee" => $info['price_sum'],

        );
        // 其他业务参数根据在线开发文档，添加参数.文档地址:
        // https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.kiX33I&treeId=62&articleId=103740&docType=1
        // 如"参数名"=>"参数值"

        
        $this->setAlipay_config($alipay_config);
        // 建立请求
        $html_text = $this->buildRequestForm($parameter, "get", "确认");
        echo $html_text;
        die();
    }
}