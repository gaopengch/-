<?php

namespace Common\Controller;


use Common\TraitClass\WxCustoms;

class WxCustomsController {
    use WxCustoms;

    public function __construct(){

    }

    public function wxCustoms($order_id){
        $pay = M('pay')->field('pay_account,michid')->where('id = 1')->find();
        $order = M('order')->field('id,price_sum,trade_no,order_sn_id')->where(['id'=>$order_id])->find();
        $co = C('CUSTOMS');
        $data = [
            'appid' => $pay['pay_account'],
            'mch_id' => $pay['michid'],
            'out_trade_no' => $order['order_sn_id'],
            'transaction_id' => $order['trade_no'],
            'customs' => $co['custCode'],
            'mch_customs_no' => $co['coCode'],
        ];

        $url = 'https://api.mch.weixin.qq.com/cgi-bin/mch/customs/customdeclareorder';

        $res = $this->pay($url,$data);

        $result = $this->xmlToArray($res);



    }

}