<?php

namespace Home\Controller;


use Common\Controller\AliCustomsController;
use Common\TraitClass\CrossBorderTrait;

class CrossBorderController extends BaseController{

    use CrossBorderTrait;

    /*
     * ====================================================================================
     * =============================海关数据处理=============================================
     * ====================================================================================
     */

    public function test(){
//        $str = date('YmdHis');
        $order_id = 290;
        $this->CustomsDocking($order_id);

    }
    //整合
    public function CustomsDocking($order_id)
    {
        //公司信息
        $co = C('CUSTOMS');     //获取公司相关信息
        $this->setco($co);
        //订单信息
        $order = $this->getOrder($order_id,$co['coCode']);
        $this->setOrder($order);
        //商品信息
        $goods = $this->getGoods($order_id);
        $this->setGoods($goods);
        //收货人信息
        $receiving = $this->getReceiving($order['address_id']);
        $this->setReceiving($receiving);
        //发货人信息
        $receiving = $this->getShipper($order['ware_id']);
        $this->setDelivery($receiving);



        $data = $this->getData();

        $datastr = json_encode($data);
//        $privateKey = C('****');
        $privateKey = ds12345;

        $signMsg = md5($datastr.$privateKey);
        $EData = urlencode($datastr);
        $EData2 = $EData.'&SignMsg='.$signMsg;
        $url = '';
//        $num = strlen($EData2);
        showData($EData2,1);
        $res = $this->getHttpResponsePOST($url,$EData2);

        showData($EData2);

    }


    //获取订单信息
    public function getOrder($order_id,$coCode){
        $field = 'id,order_sn_id,price_sum,express_id,address_id,user_id,pay_type,ware_id,remarks,pay_type,pay_time,trade_no';
        $order = M('order')->field($field)->where(['id'=>$order_id])->find();

        $strtime = date('YmdHis');
        $order['commitTime'] = $strtime;
        $order['serialNumber'] = $coCode.$strtime.rand(10000,99999);
        $order['cargoDescript'] = '';
        $order['appType'] = 1;
        $order['pay_time'] = date('YmdHis',$order['pay_time']);


        $order['buyerRegNo'] = M('user')->where(['id'=>$order['user_id']])->getField('user_name');
//showData($order,1);
        $pay = $this->getPayInfo($order['pay_type']);
        $order['payMethod'] = $pay['payMethod']; //支付方式
        $order['payMerchantNam'] = $pay['payMerchantNam']; //企业支付名称
        $order['payMerchantCode'] = $pay['payMerchantCode']; //企业支付编号

        return $order;
    }
    //获取商品信息
    public function getGoods($order_id){
//        $order_id = 290;
        $ordergoods = M('order_goods as o')
            ->join('db_goods as g on g.id=o.goods_id')
            ->join('db_spec_goods_price as s on s.goods_id=o.goods_id')
            ->field('o.id,o.order_id,o.goods_id,o.goods_num,o.goods_price,g.title,g.description,s.sku')
            ->where(['order_id'=>$order_id])
            ->select();

        return $ordergoods;


    }
    /*
     * 获取收货物流信息
     */
    public function getReceiving($address_id){
        $field = 'id,realname,mobile,user_id,create_time,update_time,prov,city,dist,address,status,zipcode,email,alias';
        $receiving = M('user_address')->field($field)->where(['id'=>$address_id])->find();
        $receiving['recCountry'] = '中国';
        $receiving['recProvince'] = M('region')->where('id='.$receiving['prov'])->getField('name');
        $receiving['recCity'] = M('region')->where('id='.$receiving['city'])->getField('name');
        $receiving['recAddress'] = M('region')->where('id='.$receiving['dist'])->getField('name').$receiving['address'];

        $receiving['realName'] = '';
        $receiving['realCode'] = '';
        return $receiving;

    }
    /*
     * 发货人信息
     */
    public function getShipper($send_id){
        $send = M('send_address')
            ->field('id,address_id,address_detail,stock_name,zipcode,company,tel')
            ->where(['id'=>$send_id])
            ->find();
        $address1 = M('region')->field('id,parentid,name')->where(['id'=>$send['address_id']])->find();
        $address2 = M('region')->field('id,parentid,name')->where(['id'=>$address1['parentid']])->find();
        $address3 = M('region')->field('id,parentid,name')->where(['id'=>$address2['parentid']])->find();
        $send['senderCountry'] = '中国';
        $send['senderProvince'] = $address3['name'];
        $send['senderCity'] = $address2['name'];

        return $send;


    }
    /*
     * 支付信息
     */
    public function getPayInfo($pay_type){
        $pay = C('CUSTOMS_PAY')[$pay_type];
        return $pay;
    }

    /*
     * ====================================================================================
     * ============================物流公司数据处理==========================================
     * ====================================================================================
     */
    public function getsend(){
        $id = 290;
        $res = $this->send($id);

        $data = $this->getSendData();

        showData($data);
    }

    //整合
    public function send($order_id){
        //物流公司账户参数
        $co = C('sendCo');      //获取物流公司账户
        $this->setSendCo($co);
        //订单信息
        $order = $this->getSendOrder($order_id,$co['coCode']);
        $this->setSendOrder($order);
//        showData($order,1);
        //发货公司信息
        $shipper = $this->getShipper($order['ware_id']);
        $this->setSendShipper($shipper);
        //收货人信息
        $receiving = $this->getReceiving($order['address_id']);
        $this->setSendConsignee($receiving);


    }



    public function getSendOrder($order_id,$coCode){
        //order
        $order = $this->getOrder($order_id,$coCode);
//        $orderData['PlatformCode'] = '';
        $order['goods'] = $this->getGoods($order_id);
        $pay = $this->getPayInfo($order['pay_type']);
        $order['payMethod'] = $pay['payMethod']; //支付方式

        return $order;

    }





/*
 * -----------------工具函数---------------------
 */
    function getHttpResponsePOST($url, $data) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Content-Length:'.strlen($data),'Connection:Keep-Alive' ));
        curl_setopt($ch, CURLOPT_POSTFIELDS , $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $output = curl_exec($ch);

        return $output;

        curl_close($ch);
    }









}