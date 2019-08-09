<?php

namespace Common\TraitClass;


trait CrossBorderTrait{

    private $customsData = [];         //海关数据

    private $sendData = [];             //物流公司数据
    private $sendOrder = [];            //物流公司数据子数据
    private $sendShipper = [];          //物流公司数据子数据
    private $sendConsignee = [];        //物流公司数据子数据


/*
 * -------------------------------------------------------------
 * -----------------海关数据处理---------------------------------
 * -------------------------------------------------------------
 */

    /*
     * 设置公司信息
     */
    public function setco($co){
        $this->customsData['coName'] = $co['coName'];      //企业名称
        $this->customsData['coCode'] = $co['coCode'];      //企业代码
        $this->customsData['crossOrderId'] = $co['crossOrderId'];      //平台编号
        $this->customsData['merchantCode'] = $co['merchantCode'];      //商城平台编号
        $this->customsData['ebpCode'] = $co['ebpCode'];      //电商平台代码
        $this->customsData['ebpName'] = $co['ebpName'];      //电商平台名称
        $this->customsData['ebcCode'] = $co['ebcCode'];      //电商企业代码
        $this->customsData['ebcName'] = $co['ebcName'];      //电商企业名称
        $this->customsData['version'] = '2.1';      //版本号
        $this->customsData['serverType'] = $co['serverType'];      //业务类型
        $this->customsData['custCode'] = $co['custCode'];      //海关关区代码
        $this->customsData['customDeclCo'] = $co['customDeclCo'];      //物流进境申报企业
    }
    /*
     * 设置发货物流信息
     */
    public function setDelivery($delivery){
        $this->customsData['senderName'] = $delivery['company'];                //发件人姓名
        $this->customsData['senderTel'] = $delivery['tel'];                  //发件人电话
        $this->customsData['senderCompanyName'] = $delivery['company'];  //发件方公司名称
        $this->customsData['senderAddr'] = $delivery['address_detail'];                //发件人地址
        $this->customsData['senderZip'] = $delivery['zipcode'];                  //发件地邮编
        $this->customsData['senderCity'] = $delivery['senderCity'];                //发件地城市
        $this->customsData['senderProvince'] = $delivery['senderProvince'];        //发件地省/州名
        $this->customsData['senderCountry'] = $delivery['senderCountry'];          //发件地国家
    }

    /*
     * 设置收货物流信息
     */
    public function setReceiving($receiving){
        $this->customsData['recPerson'] = $receiving['realname'];     //收货人姓名
        $this->customsData['recPhone'] = $receiving['mobile'];       //收货人电话
        $this->customsData['recCountry'] = $receiving['recCountry'];   //收货地国家
        $this->customsData['recProvince'] = $receiving['recProvince']; //收货地省/州
        $this->customsData['recCity'] = $receiving['recCity'];         //收货地城市
        $this->customsData['recAddress'] = $receiving['recAddress'];   //收货人详细地址
        $this->customsData['spt01'] = $receiving['trueName'];          //订购人姓名
        $this->customsData['spt02'] = $receiving['trueCode'];          //订购人身份证
        $this->customsData['spt03'] = 'U';                             //是否通过身份认证Y-是，N-否，U-未知
    }

    /*
     * 设置订单信息
     */
    public function setOrder($order){
        $this->customsData['commitTime'] = $order['commitTime'];           //提交时间
        $this->customsData['merchantOrderId'] = $order['order_sn_id'];     //商户订单号
        $this->customsData['serialNumber'] = $order['serialNumber'];       //流水号
        $this->customsData['orderCommitTime'] = $order['commitTime']; //订单提交时间
        $this->customsData['cargoDescript'] = $order['cargoDescript'];     //订单商品信息简述
        $this->customsData['buyerRegNo'] = $order['buyerRegNo']; //订购人注册号
        $this->customsData['allCargoTotalPrice'] = $order['price_sum']; //商品价格
        $this->customsData['allCargoTotalTax'] = $order['allCargoTotalTax']?$order['allCargoTotalTax']:0; //代扣税款
        $this->customsData['expressPrice'] = $order['expressPrice']?$order['expressPrice']:0; //运杂费
        $this->customsData['otherPrice'] = $order['otherPrice']?$order['otherPrice']:0; //非现金抵扣金额
        $this->customsData['appType'] = $order['appType'];        //报送类型
        $this->customsData['assBillNo'] = $order['assBillNo'];                  //物流分运单号

        $this->customsData['payMethod'] = $order['payMethod']; //支付方式
        $this->customsData['payMerchantNam'] = $order['payMerchantNam']; //企业支付名称
        $this->customsData['payMerchantCode'] = $order['payMerchantCode']; //企业支付编号
        $this->customsData['payAmount'] = $order['price_sum']; //支付总金额
        $this->customsData['payID'] = $order['trade_no']; //支付交易号
        $this->customsData['payTime'] = $order['pay_time']; //支付交易时间
        $this->customsData['payCUR'] = 142; //付款币种


    }
    /*
     * 设置商品信息
     */
    public function setGoods($goodsInfo){
        foreach($goodsInfo as $k=>$goods){
            $this->customsData['cargoes'][$k]['cargoName'] = $goods['title']; //单项购买商品名称
            $this->customsData['cargoes'][$k]['cargoCode'] = $goods['sku']; //单项购买商品编号
            $this->customsData['cargoes'][$k]['cargoNum'] = $goods['goods_num']; //单项购买商品数量
            $this->customsData['cargoes'][$k]['cargoUnitPrice'] = $goods['goods_price']; //单项购买商品单价
            $this->customsData['cargoes'][$k]['cargoTotalPrice'] = $goods['goods_num']*$goods['goods_price']; //单项购买商品总价
            $this->customsData['cargoes'][$k]['cargoTotalTax'] = $goods['cargoTotalTax']?$goods['cargoTotalTax']:0; //单项购买商品缴税总价
            $this->customsData['cargoes'][$k]['gnum'] = $k+1; //商品序号
            $this->customsData['cargoes'][$k]['itemDescribe'] = $goods['description']; //企业商品描述
            $this->customsData['cargoes'][$k]['barCode'] = ''; //条形码
            $this->customsData['cargoes'][$k]['unit'] = $goods['unit']; //单位
            $this->customsData['cargoes'][$k]['country'] = $goods['country']; //原产国
        }

    }
//    /*
//     * 设置支付信息
//     */
//    public function setPay($payInfo){
//        $this->customsData['payMethod'] = $payInfo['payMethod']; //支付方式
//        $this->customsData['payMerchantNam'] = $payInfo['payMerchantNam']; //企业支付名称
//        $this->customsData['payMerchantCode'] = $payInfo['payMerchantCode']; //企业支付编号
//        $this->customsData['payAmount'] = $payInfo['payAmount']; //支付总金额
//        $this->customsData['payID'] = $payInfo['payID']; //支付交易号
//        $this->customsData['payTime'] = $payInfo['payTime']; //支付交易时间
//        $this->customsData['payCUR'] = 142; //付款币种
//
//    }

    /*
     * 获取数据
     */
    public function getData(){
        $data = $this->customsData;
        return $data;
    }



/*
 * -------------------------------------------------------------
 * -----------------物流公司数据处理------------------------------
 * -------------------------------------------------------------
 */

    /*
     * 威盛快递分配给商户参数
     */
    public function setSendCo($co){
        $this->sendData['appname'] = $co['appname'];
        $this->sendData['appid'] = $co['appid'];
    }

    /*
     *威盛快递接口--订单参数
     */
    public function setSendOrder($order){
        $this->sendOrder['PlatformCode']= '';       //平台编号
        $this->sendOrder['OrderNo']= $order['order_sn_id'];     //电商订单号
        $this->sendOrder['TrackingID']= $order['exp_id'];       //威盛快递单号
        $this->sendOrder['Remark']= $order['remarks'];          //电商备注
//        $this->sendOrder['Quantity']= '';                     //商品数量
        $this->sendOrder['TotalPrice']= $order['price_sum'];    //订单总价
//        $this->sendOrder['ShippingFee']= $order['shipping_monery'];       //物流费用金额
//        $this->sendOrder['PostalTax']= 0;                     //预计税款
        $this->sendOrder['OrderDeclareStatus']= 0;              //订单申报状态
        $this->sendOrder['PayDeclareStatus']= 1;                //支付申报状态
        $this->sendOrder['PayType']= $order['payMethod'];         //支付类型
        $this->sendOrder['PayMoney']= $order['price_sum'];       //支付总金额
        $this->sendOrder['PaySerialNo']= $order['PaySerialNo']; //支付流水号
        $this->sendOrder['PayTime']= $order['pay_time'];         //支付时间
//        $this->sendOrder['OrderOrigin']= 'web';                 //订单来源
        $this->sendOrder['OtherPrice']= 0;                      //国内其他费用
        $this->sendOrder['OtherPayPrice']= 0;                   //非现金支付金额
        $this->sendOrder['GoodsPriceIncludeTax']= 0;            //商品金额是否含税
        $this->sendOrder['OrderTime']= $order['create_time'];   //下单时间
//        $this->sendOrder['Customs']= '';                        //申报关区
//        $this->sendOrder['ServerType']= $order['ServerType'];   //业务类型
//        $this->sendOrder['Portcode']= $order['Portcode'];       //口岸代码
        $this->sendOrder['Currency']= 'CNY';                    //币制
//        $this->sendOrder['SellerCountry']= $order['SellerCountry']; //发货国

//        $this->sendOrder['Packages']['PaySerialNo']= $order['PaySerialNo']; //支付流水号

        foreach($order['goods'] as $k=>$v){
            $this->sendOrder['Goods'][$k]['Gnum']= $k;          //申报序号
            $this->sendOrder['Goods'][$k]['CommodityLinkage']= $v['sku'];     //商品编号
            $this->sendOrder['Goods'][$k]['Commodity']= $v['title'];      //商品中文名称
            $this->sendOrder['Goods'][$k]['CommodityNum']= $v['goods_num'];   //数量
            $this->sendOrder['Goods'][$k]['CommodityUnitPrice']= $v['goods_price'];   //商品单价
//            $this->sendOrder['Goods'][$k]['TitleEn']= $v['TitleEn'];      //商品英文品名
            $this->sendOrder['Goods'][$k]['Commodity']= $v['Commodity'];      //商品中文名称
        }

    }
    /*
     *威盛快递接口--发货人信息
     */
    public function setSendShipper($delivery){
        $this->sendShipper['senderName'] = $delivery['company'];                //发件人姓名
        $this->sendShipper['senderCompanyName'] = $delivery['company'];         //发件方公司名称
        $this->sendShipper['senderCountry'] = $delivery['senderCountry'];       //发件地国家
        $this->sendShipper['senderProvince'] = $delivery['senderProvince'];     //发件地省/州名
        $this->sendShipper['senderCity'] = $delivery['senderCity'];             //发件地城市
        $this->sendShipper['senderAddr'] = $delivery['address_detail'];          //发件人地址
        $this->sendShipper['senderZip'] = $delivery['zipcode'];                  //发件地邮编
        $this->sendShipper['senderTel'] = $delivery['tel'];                  //发件人电话
    }
    /*
     *威盛快递接口--收货人信息
     */
    public function setSendConsignee($receiving){
        $this->sendConsignee['RecPerson'] = $receiving['realname'];     //收货人姓名
        $this->sendConsignee['RecPhone'] = $receiving['mobile'];       //收货人电话
        $this->sendConsignee['RecCountry'] = $receiving['recCountry'];   //收货地国家
        $this->sendConsignee['RecProvince'] = $receiving['recProvince']; //收货地省/州
        $this->sendConsignee['RecCity'] = $receiving['recCity'];         //收货地城市
        $this->sendConsignee['RecAddress'] = $receiving['recAddress'];   //收货人详细地址
        $this->sendConsignee['RecZip'] = $receiving['zipcode'];         //收货人邮编
        $this->sendConsignee['Name'] = $receiving['truename'];          //申报人姓名
        $this->sendConsignee['CitizenID'] = $receiving['truecode'];     //身份证号
    }

    public function getSendData(){
//        showData($this->sendOrder);
        $this->sendData['Orders'][0]['Order'] = $this->sendOrder;
        $this->sendData['Orders'][0]['Consignee'] = $this->sendConsignee;
        $this->sendData['Orders'][0]['Shipper'] = $this->sendShipper;
        return $this->sendData;
    }

}