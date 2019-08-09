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

namespace Home\Controller;

use Common\Controller\MsmFactory;
use Common\Controller\RebateLogController;
use Common\Tool\Tool;
use PlugInUnit\UnionPayB2c\sdk\AcpService;
use Think\Controller;
use Common\Model\BaseModel;
use Home\Model\OrderModel;
use Common\Model\OrderWxpayModel;
use Common\TraitClass\NoticeTrait;
use Common\TraitClass\InternetTopTrait;
use Common\TraitClass\SmsVerification;
use Think\Hook;
use Common\Behavior\WangJinTing;
use Common\TraitClass\WxNofityTrait;
use Common\TraitClass\OrderNoticeTrait;
use Common\Behavior\Decorate;
use Common\TraitClass\AlipayNotifyTrait;
use Common\Behavior\AlipaySerialNumber;
use Common\Behavior\Balance;
use Common\TraitClass\WxListenResTrait;
use Think\Log;

class NofityController extends Controller
{
    use NoticeTrait;
    use SmsVerification;
    use InternetTopTrait;
    use WxNofityTrait;
    use OrderNoticeTrait;
    use AlipayNotifyTrait;
    use WxListenResTrait;
    private $url = '';

    /**
     * 支付成功订单页
     * @var string
     */
    const RELEVANT = 'relevant';

    public function __construct()
    {
        parent::__construct();

        Hook::add( 'reade',WangJinTing::class );

        $information = $this->getIntnetInformation();
        $this->assign( 'hot_words',self::keyWord() );

        $this->assign( 'intnetTitle',$information[ 'intnet_title' ] );

        $this->assign( 'str',$this->getFamily() );

        $this->assign( $information );
    }

    public function wxNotify()
    {
        // 获取通知的数据
//        $xml = $GLOBALS[ 'HTTP_RAW_POST_DATA' ];
        $xml = file_get_contents('php://input');

        file_put_contents('./Uploads/wxpay1.txt',"xmlData-------".date('Y-m-d H:i:s')."\r\n".\print_r($xml,true),8);

        Tool::connect( 'Token' );

        $data = Tool::init( $xml,self::PARTNER_ID );

        file_put_contents('./Uploads/wxpay1.txt',"saveData-------".date('Y-m-d H:i:s')."\r\n".\print_r($data,true),8);

        ob_start();

        $ssd = ob_get_clean();

        file_put_contents( './Uploads/orderId.txt',$ssd );



        if( empty( $data[ 'out_trade_no' ] ) ){
            echo 'ERROR';
            die();
        }

        $orderModel = BaseModel::getInstance( OrderModel::class );

        //分单订单处理
        $pay_order_id = $data[ 'out_trade_no' ];
        file_put_contents( './Uploads/orderId.txt',$pay_order_id );
        if( !is_numeric( $pay_order_id ) ){
            echo 'ERROR';
            die();
        }
        $type = explode('-', $pay_order_id)[2];
        $order_id = explode('-', $pay_order_id)[1];
        if($type == 1){
            $orderId = M('pay_order')->where(['pay_order_id'=>$order_id])->getField('order_id_str');
            //获取订单状态
            $ordersStatus = M('order')->field('order_status')->where(['id'=>['in',$orderId]])->select()[0]['order_status'];
            $sql          = $orderModel->getLastSql();
        }else{
            $orderId = M('order')->where(['order_sn_id'=>$order_id])->getField('id');
            //获取订单状态
            $ordersStatus = $orderModel->getOrderStatusByUser( $orderId );
            $sql          = $orderModel->getLastSql();
        }

        file_put_contents( './Uploads/rty.txt',$sql );

        $status = false;
        if( $ordersStatus == 0 ){
            //修改状态
            $status = $orderModel->save( array(
                OrderModel::$orderStatus_d  => OrderModel::YesPaid,
                OrderModel::$payTime_d => time()
            ),array(
//                'where' => array( OrderModel::$id_d => $orderId )
                'where' => array( OrderModel::$id_d =>['in',$orderId]  ) //分单处理
            ) );

            $status = BaseModel::getInstance( OrderWxpayModel::class )->save( array( OrderWxpayModel::$status_d => 1 ),array( 'where' => array(
//                OrderWxpayModel::$orderId_d => $orderId
                OrderWxpayModel::$orderId_d => ['in',$orderId]   //分单处理
            ) ) );
            M('order')->where( [ 'id' =>['IN',$orderId]  ] )->save( ['trade_no'=>$data['transaction_id'] ] );//支付流水号

            file_put_contents('./Uploads/wxpay1.txt',"editOrder-------".date('Y-m-d H:i:s')."\r\n".\print_r($orderId,true),8);
        }
        echo $status ? "SUCCESS" : 'ERROR';

    }

    /**
     * pc 回调
     */
    public function pcWxNofity()
    {
        $orderId = $this->nofityWx();

        file_put_contents('./Uploads/wxpay2.txt',"saveData-------".date('Y-m-d H:i:s')."\r\n".\print_r($orderId,true),8);
        Hook::add( 'aplipaySerial',Decorate::class );

        $this->getPayIntegral();

//        $status = $this->orderNotice( $orderId );
//
//        $this->msg( $status );

        //分单处理
        $type = explode('-', $orderId)[2];
        $order_id = explode('-', $orderId)[1];
        if($type == 1){
            $orderId2 = M('pay_order')->where(['pay_order_id'=>$order_id])->getField('order_id_str');
            $orderId2 = explode(',',$orderId2);
            $status = M('order')->where(['id'=>['IN',$orderId2] ])->save([ 'order_status' => '1','pay_time' => time(),'pay_type' => 1 ,'platform'=>0,'trade_no'=>$_SESSION['trade_no']]);
            M('order_goods')->where( [ 'order_id' => ['IN',$orderId2] ] )->save( [ 'status' => 1 ] );
            $res1 =M('order_wxpay')->where(['wx_pay_id'=>$orderId])->save([ 'status' => 1 ]);

            foreach($orderId2 as $v){
                $res2 = $this->orderNotice($v);
            }
            $res =M('pay_order')->where(['pay_order_id'=>$order_id])->save([ 'status' => 1 ]);
            file_put_contents('./Uploads/wxpay2.txt',"--173order_id-------".date('Y-m-d H:i:s')."\r\n".\print_r($orderId,true),8);
        }else{
            $orderId2 = M('order')->where(['order_sn_id'=>$order_id])->getField('id');
            $status = M('order')->where(['id'=>['IN',$orderId2] ])->save([ 'order_status' => '1','pay_time' => time(),'pay_type' => 1,'platform'=>0 ,'trade_no'=>$_SESSION['trade_no']]);
            M('order_goods')->where( [ 'order_id' => ['IN',$orderId2] ] )->save( [ 'status' => 1 ] );
            $res1 =M('order_wxpay')->where(['wx_pay_id'=>$orderId])->save([ 'status' => 1 ]);

            $res2 = $this->orderNotice($orderId2);
            file_put_contents('./Uploads/wxpay2.txt',"--181order_id-------".date('Y-m-d H:i:s')."\r\n".\print_r($orderId,true),8);
        }
        M('order')->where( [ 'id' =>['IN',$orderId2]  ] )->save( ['trade_no'=>$_SESSION['trade_no'] ] );//支付流水号

        //+--------------修改订单表中  主团单已购买人数和团购状态           ---meng
//        $status1 = M('order')->updatePersonAndStatus($orderId2);

        $this->msg( $status );

        echo "SUCCESS";
        die();

    }


    /**
     * 异步通知
     */
    public function alipayNotify()
    {

        file_put_contents('./Uploads/zfbpay1.txt',"saveData-------".date('Y-m-d H:i:s')."\r\n".\print_r($_POST,true),8);
        $data = $this->alipayResultParse();

        $this->promptParse( $data,'验证失败',U( 'Order/order_myorder' ) );

//        $orderSnId = $data[ 'order_sn_id' ];

//        $orderId = substr( strrchr( $orderSnId,'-' ),1 ); // 主键编号 确保唯一性;
        file_put_contents('./Uploads/zfbpay1.txt',"saveData-------".date('Y-m-d H:i:s')."\r\n".\print_r($data,true),8);

        $this->tradeNo = $data[ 'trade_no' ];

        Hook::add( 'aplipaySerial',AlipaySerialNumber::class );

        $this->getPayIntegral();

//        $status = $this->orderNotice( $orderId );



        if( ($data['trade_status'] !='TRADE_SUCCESS') && ($data['trade_status'] != 'TRADE_FINISHED' )){
            echo 'FAIL';
            die();
        }
        //分单处理
        $pay_order_id = $data[ 'out_trade_no' ];
        $trade_no= $data[ 'trade_no' ];

//        if( empty( $pay_order_id ) ){
//            echo 'ERROR';
//            die();
//        }
        file_put_contents('./Uploads/zfbpay1.txt',"pay_order_id-------".date('Y-m-d H:i:s')."\r\n".\print_r($pay_order_id,true),8);

        //判断是否为合单
        $type = explode('-', $pay_order_id)[1];
        $order_id = explode('-', $pay_order_id)[0];
        if($type == 1){
            $orderId = M('pay_order')->where(['pay_order_id'=>$order_id])->getField('order_id_str');
            $orderId = explode(',',$orderId);
            M('order')->where(['id'=>['IN',$orderId] ])->save([ 'order_status' => '1','pay_time' => time(),'pay_type' => 2 ,'platform'=>0,'trade_no'=>$trade_no ]);
            M('order_goods')->where( [ 'order_id' => ['IN',$orderId] ] )->save( [ 'status' => 1 ] );

            foreach($orderId as $v){
                $status = $this->orderNotice($v);
            }
            $res =M('pay_order')->where(['pay_order_id'=>$order_id])->save([ 'status' => 1 ]);
        }else{
            $orderId = M('order')->where(['order_sn_id'=>$order_id])->getField('id');
            M('order')->where(['id'=>['IN',$orderId] ])->save([ 'order_status' => '1','pay_time' => time(),'pay_type' => 2 ,'platform'=>0,'trade_no'=>$trade_no ]);
            M('order_goods')->where( [ 'order_id' => ['IN',$orderId] ] )->save( [ 'status' => 1 ] );

            $status = $this->orderNotice($orderId);
        }
        M('order')->where( [ 'id' =>['IN',$orderId]  ] )->save( ['trade_no'=>$trade_no ] );//支付流水号

        file_put_contents('./Uploads/zfbpay1.txt',"orderid-------".date('Y-m-d H:i:s')."\r\n".\print_r($orderId,true),8);

        if( !$status ){
            echo 'FAIL';
            die();
        }

        echo "SUCCESS";
        die();
    }

    /**
     * 支付宝同步回调
     */
    public function alipayReturn()
    {
        $this->assign( 'total_fee',$_GET[ 'total_fee' ] );

//        $orderId = substr( strrchr( $_GET[ 'out_trade_no' ],'-' ),1 ); // 主键编号 确保唯一性;
        file_put_contents('./Uploads/zfbpay2.txt',"saveData-------".date('Y-m-d H:i:s')."\r\n".\print_r($_GET,true),8);

        $pay_order_id = $_GET[ 'out_trade_no' ];
        file_put_contents( './Uploads/orderId.txt',$pay_order_id );
        if( empty( $pay_order_id ) ){
            echo 'ERROR';
            die();
        }
        //判断是否为合单
        $type = explode('-', $pay_order_id)[1];
        $order_id = explode('-', $pay_order_id)[0];

        if($type == 1){
            $orderId = M('pay_order')->where(['pay_order_id'=>$order_id])->getField('order_id_str');

        }else{
            $orderId = M('order')->where(['order_sn_id'=>$order_id])->getField('id');
        }

        file_put_contents('./Uploads/zfbpay2.txt',"orderId-------".date('Y-m-d H:i:s')."\r\n".\print_r($pay_order_id,true),8);
        $this->paySuccess( $orderId );
    }

    private function notifyHtml( $status,$orderId )
    {
        if( $status ){
            $this->paySuccess( $orderId );
        }else{
            $this->assign( 'intnetTitle','支付结果' );
            $this->display( 'fail' );
        }
    }


    public function checkOrderStatus( $orderSnId )
    {
        $this->promptPjax( $orderSnId,'订单号错误' );

//        $snId = substr( strrchr( $orderSnId,'-' ),1 ); // 主键编号 确保唯一性
        $type = explode('-', $orderSnId)[2];
        $order_id = explode('-', $orderSnId)[1]; // 主键编号 确保唯一性

        if($type == 1){
            $snId = M('pay_order')->where(['pay_order_id'=>$order_id])->getField('order_id_str');
            $status = M('order')->field('order_status')->where(['id'=>['in',$snId]])->select()[0]['order_status'];
        }else{
            $status = M('order')->where(['order_sn_id'=>$order_id])->getField('order_status');
        }

//        $status = BaseModel::getInstance( OrderModel::class )->getUserNameById( $snId,OrderModel::$orderStatus_d );


        $this->url = 'wxNofityByHTML';


        $this->payNotice( $status,$snId );
    }

    /**
     * 微信支付通知页面
     * @param unknown $orderSnId
     * @param unknown $display
     */
    public function wxNofityByHTML( $orderSnId,$display )
    {
        $this->parseOrder( $orderSnId );

        $this->display( $display );
    }

    /**
     * 不需要支付的回调
     * 兑换的积分商品,且运费没有
     */
    public function noNeedPay()
    {
        $data = I( 'GET.' );
        $this->handleNotify( $data );
        $this->paySuccess( $data[ 'order_sn_id' ] );
    }

    /**
     * 余额支付通知
     */
    public function balanceNofty()
    {
        $validate = [
            'id',
            'address_id'
        ];
        Tool::checkPost( $_GET,[
            'is_numeric' => $validate
        ],true,$validate ) ? : $this->error( '参数错误' );

        Hook::add( 'aplipaySerial',Balance::class );

        $orderId = $_GET[ 'id' ];

        $this->getPayIntegral();

        $status = $this->orderNotice( $orderId );
        //余额变动短信提醒
        $this->sendBalanceSms();

        $this->notifyHtml( $status,$orderId );
    }

    /**
     * 显示支付成功页面
     */
    private function paySuccess( $sn_id )
    {
        $this->parseOrder( $sn_id );

        $this->assign( 'payRelated',self::RELEVANT );

        $this->display( 'success' );
    }

    /**
     * 支付成功处理
     * @param unknown $sn_id
     */
    private function parseOrder( $sn_id )
    {
        $orderModel = BaseModel::getInstance( OrderModel::class );
        $filed      = [
            OrderModel::$id_d,
            OrderModel::$priceSum_d,
            OrderModel::$addressId_d
        ];

        $order = $orderModel->field( $filed )
            ->where( [
//                OrderModel::$id_d => $sn_id
                OrderModel::$id_d => ['in',$sn_id] //分单处理
            ] )
            ->find();

//        $where['id'] = $sn_id;
        $where['id'] = ['in',$sn_id];//分单处理
        $data['order_status'] = 1;
        $data['pay_time'] = time();
        $data['platform'] = 0;
        $status = M('order')->where($where)->data($data)->save();
//        $result2 = M('order_goods')->where( [ 'order_id' =>$sn_id ] )->save( [ 'status' => 1 ] );
        $result2 = M('order_goods')->where( [ 'order_id' =>['in',$sn_id] ] )->save( [ 'status' => 1 ] );//分单处理

        if($_GET[ 'total_fee' ] > $order[ 'price_sum' ]){
            $order[ 'price_sum' ] = $_GET[ 'total_fee' ]; //分单处理
        }
        $address = D( 'userAddress' )->getAddrById( $order[ 'address_id' ] );
        $this->assign( 'intnetTitle','支付成功' );
        $this->assign( 'address',$address[ 'addr_alone' ]);
        $this->assign( 'total_fee',$order[ 'price_sum' ] );
        $this->assign( 'order_id',$order[ 'id' ] );
    }

    /**
     * 银联同步回调
     */
    public function UnionSynchronous()
    {
        $this->redirect( 'Home/Order/order_details',[ 'id' => (int)\substr( I( 'orderId' ),24 ) ] );
    }


    /**
     * 银联异步回调
     */
    public function UnionAsynchronous()
    {
        $data = I( 'post.' );
        if( empty( $data ) ){
            E( '非法请求' );
        }
        if( $data[ 'respCode' ] != '00' && $data[ 'respCode' ] != 'A6' ){
            die;
        }

        $info = AcpService::validate( $data );

        if( !$info ){
            die( '验签失败' );
        }
        echo '验签成功';
        $orderId   = (int)\substr( $data[ 'orderId' ],24 );
        $OrderData = BaseModel::getInstance( OrderModel::class )->where( [ 'id' => $orderId ] )->getField( 'order_status' );
        if( $OrderData !== '0' ){
            E( '订单错误' );
        }

        $status = $this->orderNotice( $orderId );

        //将部分数据写入银联退款表
        $refundData                    = [];
        $refundData[ 'order_sn_id_r' ] = $data[ 'orderId' ];
        $refundData[ 'origQryId' ]     = $data[ 'queryId' ];
        $refundData[ 'money' ]         = (float)$data[ 'txnAmt' ] / 100;
        $status2                       = M( 'unionrefund' )->add( $refundData );

        if( !$status ){
            Log::write( '订单-' . $orderId . '-修改状态失败' );
        }
        if( !$status2 ){
            Log::write( '订单-' . $orderId . '-插入退款表失败' );
        }
        die;
    }


    /**
     * 获取积分比例
     */
    private function getPayIntegral()
    {
        $this->key         = 'integral';
        $payIntegral       = $this->getGroupConfig()[ 'pay_integral' ];
        $this->payIntegral = $payIntegral;
    }

    /**
     * 余额支付通知
     */
    private function sendBalanceSms()
    {
        if( M( 'sms_check' )->where( [ 'check_title' => '余额支付提示' ] )->getField( 'status' ) ){
            $userPhone = M( 'user' )->where( [ 'id' => $_SESSION[ 'user_id' ] ] )->getField( 'mobile' );
            $sms       = new MsmFactory();
            $sms->factory( $userPhone,5 );//5为 余额通知的短信模板
        }
    }

}