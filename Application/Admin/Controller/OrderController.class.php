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

namespace Admin\Controller;

use Common\Controller\AuthController;
use Common\Controller\RebateLogController;
use Admin\Model\OrderModel;
use Common\Tool\Tool;
use Common\Model\UserAddressModel;
use Admin\Model\UserModel;
use Common\Model\OrderGoodsModel;
use Admin\Model\GoodsModel;
use Common\TraitClass\CancelOrder;
use Common\Model\BaseModel;
use Common\Model\ExpressModel;
use Common\Model\RegionModel;
use Admin\Model\CouponModel;
use Admin\Model\CouponListModel;
use Admin\Model\OrderReturnGoodsModel as org;
use Home\Model\GoodsImagesModel;
use Home\Model\PayTypeModel;
use Admin\Model\OrderReturnGoodsModel;
//短信工厂类->发货提示
use Common\Controller\MsmFactory;

/**
 * 订单控制器
 * @author 王强
 * @copyright 亿速网络
 * @version  v1.1.2
 * @link http://yisu.cn
 */
class OrderController extends AuthController
{
    use CancelOrder;
    //订单列表 - 全部订单
    public function orderList()
    {
       $this->condition = $this->getOrderStatus();   
       $this->display();
    }
    
    /**
     * ajax 获取数据 
     */
    public function ajaxGetData()
    {
        // 获取订单
        //初始化页数排序

        $addressModel = BaseModel::getInstance(UserAddressModel::class);
        $model = BaseModel::getInstance(OrderModel::class);

        $post = $_POST;
        $where = $this->getWhere($post);
        // 获取订单数据
        $data = $model->getOrderData($_POST, $where);

        //判断订单是否自动取消
        $data = $model->order_cancelled($data);
        //订单状态名称
        $data = $model->GetOrderStatus($data);

        $this->promptPjax($data['data'], '暂无数据');

        $this->addressModel = clone $addressModel;
        
        $data['data'] = $this->getExpress($data['data'], $model, OrderModel::$addressId_d);

        //增加订单商品数据
        $data['data'] = $this->addOrderGoodsInfo($data['data']);

        $this->expressModel = ExpressModel::class;
        $this->model        = $model;
        $this->order        = $data;
        $this->display();
    }
    /*
     * 增加订单商品数据
     */
    public function addOrderGoodsInfo($data){

        $oid = array_column($data,'id');
        $feild = 'id,order_id,goods_id,goods_num,goods_price,ware_id';
        $where = ['order_id'=>['in',$oid]];
        $orderGoods = M('order_goods')->field($feild)->where($where)->select();

        $gid = array_column($orderGoods,'goods_id');
        $gid = array_unique($gid);
        $goodsInfo = M('goods')->where(['id'=>['in',$gid]])->group('id')->getField('id,title,p_id,send_address');

        $pid = array_column($goodsInfo,'p_id');
        $goodsImg = M("GoodsImages")->where(['goods_id'=>['in',$pid] ,'is_thumb' => 1])->getField('goods_id,pic_url');

        $sid = array_column($goodsInfo,'send_address');
        $SendAddress = M("SendAddress")->where(['id'=>['in',$sid] ])->getField('id,stock_name');

        $exp_id = array_column($data,'exp_id');
        $express = M("express")->where(['id'=>['in',$exp_id] ])->getField('id,name');

        foreach($orderGoods as $k=>&$v){
            $v['title'] = $goodsInfo[ $v['goods_id'] ]['title'];
            //增加团活动标题   ---meng
            $v['group_title'] = M("group")->where(['goods_id'=>$v['goods_id'] ])->getField('title');

            $v['stock_name'] = $SendAddress[ $goodsInfo[ $v['goods_id'] ]['send_address'] ];
            $v['img'] = $goodsImg[ $goodsInfo[ $v['goods_id'] ]['p_id'] ];
        }
        foreach($data as $k1=>&$v1){
            $v1['express'] = $express[ $v1['exp_id'] ];
            foreach($orderGoods as $k2=>$v2){
                if($v1['id'] == $v2['order_id']){
                    $v1['goods'][] = $v2;
                }
            }
        }

        return $data;
    }
    
    /**
     * 订单详情
     */
    public function orderDetail()
    {
       
        $data = $this->getOrder();
        $userModel = BaseModel::getInstance(UserModel::class);
        //传递给用户模型
        $userData =$userModel->userInfoByOrder($data, array(UserModel::$userName_d, UserModel::$email_d, UserModel::$mobile_d, UserModel::$memberDiscount_d));
        
        $this->prompt($userData, null, '未知错误', false);
        
        //收货人信息
        $model = BaseModel::getInstance(UserAddressModel::class);
        
        $receive = $model->receiveManByOrder($data,  array($model::$id_d, $model::$userId_d, $model::$status_d), true);
        
        $this->promptParse($receive, null, '未知错误', false);
        
        //传递地区表
        $regionModel = BaseModel::getInstance(RegionModel::class);
        
        $receive     = $regionModel->getDefaultRegion($receive, $model);

        //传递给商品模型
        $goodsDatail = $this->getOrderGoodsInfo($data, true);
        //查询订单获得的商品积分  ---meng
        foreach ($goodsDatail as $k=>&$v){
            $v['integral']=get_level_integral($v['goods_id'],$v['user_id']);
        }
        //查询订单获得的商品积分  ---meng
        $this->userModel  = $userModel;
        $this->goods      = $goodsDatail;
        $this->userAddressModel = $model;
        $this->receive    = $receive;
        $this->order      = array_merge($data, $userData);
        $this->orderStatus  = S('order');
        $this->assign('orderStatus', S('order'));
        $this->display();
    }
    
    
    /**
     * 积分等优惠券等费用信息 
     */
    public function couponInformation ()
    {
        Tool::checkPost($_POST, ['is_numeric' => array('id', 'monery')], true, ['id', 'monery']) ? : $this->ajaxReturnData(null, 0, '操作失败');
        
//        $conpouListModel = BaseModel::getInstance(CouponListModel::class);
//
//        $userCoupon = $conpouListModel->getUserByOrder($_POST['id']);
//
//        $conpouModel = BaseModel::getInstance(CouponModel::class);
//
//        $monery = $conpouModel->getCouponById($userCoupon[CouponListModel::$cId_d]);
//
//        $this->conpouListModel = CouponListModel::class;
//
//        $this->couponMonery      = $monery;

        $couponMonery = M('order')->where(['id'=>I('post.id/d')])->getField('coupon_amount');
        $this->couponMonery      = $couponMonery;
        
        $this->display();
    }
    
    /**
     * 发货
     */
    public function sendGoods()
    {
        $order = $this->getOrder();
        
        //收货人信息
        
        $userModel = BaseModel::getInstance(UserAddressModel::class);
        
        $receive = $userModel->receiveManByOrder($order,  array($userModel::$id_d, $userModel::$userId_d, $userModel::$status_d), true);
        
        $receive = BaseModel::getInstance(RegionModel::class)->getDefaultRegion($receive, $userModel);
        
        $this->prompt($receive, null, '未知错误', false);
        
        $goodsInfo = $this->getOrderGoodsInfo($order, true);
        
        $this->prompt($goodsInfo, null, '未知错误', false);
        
        $this->order   =   array_merge($order, $receive);
        $this->goodsInfo = $goodsInfo;
        $this->userAddressModel = $userModel;
        
        $this->display();
    }
    
    
    /**
     * 填写快递单号 
     */
    
    public function delivery()
    {
       // dump($_POST);exit;
        Tool::checkPost($_POST, array('is_numeric' => array('id', 'express_id')), true, array('id', 'express_id')) ? true : $this->error('参数错误');
        
        $model = BaseModel::getInstance(OrderModel::class);

        $orderStatus = $model->getOrderStatusByUser($_POST['id']);
        
        if ($orderStatus != $model::YesPaid && $orderStatus > $model::AlreadyShipped)
        {
            $this->error('订单状态有误');
        }
        $exp_id = M('express')->where(['name'=>$_POST['shipping']])->getField('id');

        if(!$exp_id){
            $this->error('快递公司不存在');
        }
        $_POST['exp_id'] = $exp_id;
        
        $_POST[$model::$orderStatus_d] = $model::AlreadyShipped;
        $status = $model->save($_POST);
        //填写快递编号发货时返利主团单    ---meng
        $this->groupHostRebate($_POST['id']);
        //返还用户积分                   ---meng
        $this->addUserIntegral($_POST['id']);

        //加上发货提示功能
        $order_data=M('order')->where(array('id'=>$_POST['id']))->find();
        //获取发送短信数据
        $address_data=M('UserAddress')->where(array('id'=>$order_data['address_id']))->find();
        //快递名称
        $name=M('express')->where(array('id'=>$order_data['exp_id']))->find()['name'];
        $id['id']=4;
        $id['real_name']=$address_data['realname'];
        $id['order_sn_id']=$order_data['order_sn_id'];
        $id['express_id']=$order_data['express_id'];
        $id['delivery_time']=$order_data['delivery_time'];
        $id['express_name']=$name;
        $this->delivery_message_send($address_data['mobile'],$id);
        $status ? $this->success('成功',U('orderList')) : $this->error('失败');
        
    }

    /**
     * 短信发货提示
     */
    public function delivery_message_send($mobile,$type_id)
    {
        $is_start=unserialize(M('SystemConfig')->where(array('parent_key'=>'smsConfig'))->find()['config_value'])['IS_START_CONFIG'];
        //判断总开关
        if($is_start==1)
        {
            $is_start=M('SystemConfig')->where(array('parent_key'=>'smsConfig'))->find()['id'];
            //单独的开关
            $is_start=M('TemplateCategory')->where('template_category_id='.$is_start.' AND id=4')->find()['status'];
            if($is_start==1)
            {
                //实例化短信类
                $SMS=MsmFactory::factory('huaxin');
                $SMS->send_msg($mobile, $type_id);
            }
        }
    }
    
    /**
     * 公共方法 
     */
    private function getOrder()
    {
        // 检测传值
        Tool::checkPost($_GET, array('is_numeric' => array('order_id')), true, array('order_id')) ? true : $this->error('参数错误');
        $model = BaseModel::getInstance(OrderModel::class);
        //获取订单信息
        $data = $model->getOrderById($_GET['order_id']);
        $_SESSION['shippingMonery'] = $data[OrderModel::$shippingMonery_d];
        $this->prompt($data, null, '订单有误', false);
        
        //获取运送方式
        $shippingModel = BaseModel::getInstance(ExpressModel::class);
        
        $data[OrderModel::$expId_d] = $shippingModel->getExpressTitle($data[OrderModel::$expId_d ]);
        //获取支付方式
        $payType = BaseModel::getInstance(PayTypeModel::class);

        $data[OrderModel::$payType_d] = $payType->getNameById($data[OrderModel::$payType_d]);
        $this->orderModel = $model;
        return $data;
    }
    
    private function getOrderGoodsInfo(array $data, $open = FALSE)
    {
        if (!is_array($data) || empty($data))
        {
            return array();
        }
      
        //传递给商品订单模型
        
        $orderGoodsModel = BaseModel::getInstance(OrderGoodsModel::class);
        
        //去除不查询的字段
        $noSelect = $orderGoodsModel->deleteFields(array($orderGoodsModel::$id_d));
       
        $orderGoods  = $orderGoodsModel->getGoodsId($data, $noSelect);

        $goodsModel = BaseModel::getInstance(GoodsModel::class);
       
        Tool::connect('parseString');
        //传递给商品模型
        $goodsDatail = $goodsModel->getOrderInfo($orderGoods, array($goodsModel::$id_d.' as '. $orderGoodsModel::$goodsId_d, $goodsModel::$title_d));
        
        empty($open) ? : $this->goodsModel = $goodsModel;
        
        empty($open) ? : $this->orderGoodsModel = $orderGoodsModel;
        return $goodsDatail;
    }
    
    /**
     * 退货 
     */
    public function returnOrder()
    {
         $orderStatus = $this->flagOption(OrderModel::ReturnAudit, OrderModel::AuditSuccess);
         
         if (empty($orderStatus)) {
             $this->updateClient(null, '失败');
         }
        
         $url = U('cancelOrderMonery', array('idsaw' => $_POST['id']));
         
         $this->updateClient($url, '操作');
         
    }
    /**
     * 退款 
     */
    public function cancelOrderMonery()
    {
        $status = $this->cancelOrder();
      
        $update = false;
        if (!empty($status)) {
            
            $model = BaseModel::getInstance(OrderModel::class);
            
            $update = $model->save(array(
               $model::$orderStatus_d => $model::ReturnMonerySucess
            ),array(
               'where' => array($model::$id_d => $status['id'])
            ));
        }
        $this->prompt($update, null, '系统异常', false);
        
        $this->status = $status;
        $this->display();       
    }
    
    /**
     * 不予退款 
     */
    public function noReturn()
    {
        $orderStatus = $this->flagOption(OrderModel::ReturnAudit, OrderModel::AuditFalse);
         
        $this->updateClient($orderStatus, '操作');
    }
    
    /**
     * @copyright 版权所有©亿速网络
     * 退货，不退货操作 
     */
    private  function flagOption($status, $editStatus)
    {
        if (!is_numeric($status) || !is_numeric($editStatus)) {
            return false;
        }
        Tool::checkPost($_POST, array('is_numeric' => array('id')), true, array('id')) ? true : $this->error('参数错误');
        
        //获取订单
        
        $model = BaseModel::getInstance(OrderModel::class);
        
        $orderStatus = $model->find(array(
            'field' => array($model::$id_d, $model::$priceSum_d, $model::$orderStatus_d),
            'where' => array($model::$id_d => $_POST['id'])
        ));
        
      
        if ( empty($orderStatus) ||$orderStatus[$model::$orderStatus_d] != $status ) {
           return false;
        }
         
        $_POST[$model::$orderStatus_d] = $editStatus;
         
        $status = $model->save($_POST);
        
        return $status ? $status : false;
    }
    
    /**
     * 单商品退货 
     * @copyright 版权所有©亿速网络
     */
    public function returnGoods ()
    {
        $model = BaseModel::getInstance(org::class);
        
        Tool::isSetDefaultValue($_POST, [
            org::$orderId_d => null,
        ]);
        
        
        $this->returnGoodsType = C('returnGoods');
        
        $this->model = org::class;
        $this->display();
    }
    
    /**
     * ajax 获取 退货单 
     * @copyright 版权所有©亿速网络
     */
    public function ajaxGetReturnGoods ()
    {
        $model = BaseModel::getInstance(org::class);
        $listTitle = $model->getListTitle([
            org::$orderId_d,
            org::$type_d,
            org::$createTime_d,
        ]);
        Tool::isSetDefaultValue($_POST, array(//设置默认值 版权所有©亿速网络
            'orderBy' => org::$createTime_d,
            'sort'    => BaseModel::DESC
        ));
        $where = array();
        Tool::connect('ArrayChildren');
        
        $model->setNoValidate([//不检测的搜索键
            org::$orderId_d
        ]);
        
        $where = $model->buildSearch($_POST);
       
        $orderModel = BaseModel::getInstance(OrderModel::class);
        
        Tool::connect('parseString');
     
        $where[org::$orderId_d] = $orderModel->getSearch($_POST);

        
        $data = $model->getContent($_POST, $where);
        
        $data['data'] = $orderModel->getOrderByOrderReturn($data['data'], org::$orderId_d);
        
        $goodsModel   = BaseModel::getInstance(GoodsModel::class);
        
        $data['data'] = $goodsModel->getGoodsByOrderReturn($data['data'], org::$goodsId_d);
      

        //@copyright 版权所有©亿速网络
        $this->assign('orderModel', OrderModel::class);
        
        $this->assign('title', $listTitle);
        
        $this->assign('goodsModel', GoodsModel::class);
        $this->assign('typeData', C('returnGoods'));
        $this->assign('refund', C('refund'));
        $this->assign('isReceive', C('is_receive'));
        $this->assign('orderType', C('orderType'));
        $this->model = org::class;
      
        $this->data = $data;


        $this->display();
    }
    
    /**
     * 获取退货单详情 
     */
    public function getReturnGoodsInfo($id)
    {
        //检测传值
        $this->errorNotice($id);
        
        $model = BaseModel::getInstance(org::class);
        
        //退货信息
        $detail = $model->getReturnDetail($id);

        $this->prompt($detail);
        
        $orderModel = BaseModel::getInstance(OrderModel::class);
        
        //订单信息
        $detail[OrderModel::$orderSn_id_d] = $orderModel->getUserNameById($detail[org::$orderId_d], OrderModel::$orderSn_id_d);
        $detail['pay_type'] = $orderModel->where(['id'=>$detail['order_id']])->getField('pay_type');
        $goodsModel = BaseModel::getInstance(GoodsModel::class);
        if($detail['pay_type'] == 1){
            $detail['wx_pay_id'] = M('order_wxpay')->where(['order_id'=>168])->getField('wx_pay_id');
        }
        //商品信息
        $detail[GoodsModel::$title_d] = $goodsModel->getUserNameById($detail[org::$goodsId_d], GoodsModel::$title_d);
        
        //用户信息
        $userModel = BaseModel::getInstance(UserModel::class); 
        
        $detail[UserModel::$userName_d] = $userModel->getUserNameById($detail[org::$userId_d], UserModel::$userName_d);

        //退货图片
        if($detail['apply_img']){
            $imgs = explode(',',$detail['apply_img']);
        }else{
            $imgs = explode(',',$detail['voucher']);
        }
        //是否退货成功
        $status = BaseModel::getInstance(OrderGoodsModel::class)->getStatus($detail[OrderReturnGoodsModel::$orderId_d], $detail[OrderReturnGoodsModel::$goodsId_d]);
        $this->assign('org', org::class);
        $this->assign('refund', C('refund'));
        $this->assign('returnGoods', C('returnGoods'));
        $this->assign('order', OrderModel::class);
        $this->assign('status', $status);
        $this->assign('imgs', $imgs);
        $this->assign('goods', GoodsModel::class);
        $this->assign('user', UserModel::class);
        $this->data = $detail;
//        showData($detail,1);
        $this->display();
    }
    /**
     * 收货状态更改 
     */
    public function isReceive ()
    {
        $validate = ['id', 'is_receive'];
        Tool::checkPost($_POST, array('is_numeric' => $validate), true, $validate) ? true : $this->ajaxReturnData(null, 0, '操作失败');
        
        $_POST['is_receive'] =  intval($_POST['is_receive']) > 2 ? 1 :$_POST['is_receive'];
        
        $model = BaseModel::getInstance(org::class);
        
        $status = $model->save($_POST);
        
        $this->updateClient($status, '操作');
        
    }
    /**
     * 退款
     */
    public function cancelReturnOrder($type, $id)
    {
        $this->errorNotice($id);
        $this->errorNotice($type);

        $model = BaseModel::getInstance(org::class);
        
        $data = $model->getReturnData($id, $type);
      
        $this->promptParse($data, $model->getError());
        $orderModel = BaseModel::getInstance(OrderModel::class);
        //是否已支付
        $isAlipay = (int)$orderModel->getUserNameById($data[org::$orderId_d], OrderModel::$orderStatus_d);
       
        $this->promptParse($isAlipay >= OrderModel::YesPaid || $isAlipay < OrderModel::ReturnMonerySucess , '未支付');
        
        //检测状态 是否正常
        $orderGoodsModel = BaseModel::getInstance(OrderGoodsModel::class);
        
        $status = $orderGoodsModel->getIsStatus($data[org::$orderId_d], $data[org::$goodsId_d], OrderModel::ReturnAudit);
        
        $this->promptParse($status, '订单状态有误');
        
        $orderModel->setSColums([
            OrderModel::$payType_d,
            OrderModel::$platform_d
        ]);
        
        $_SESSION['org'] = $id; 
        $_SESSION['RETURN_GOODS_ID'] = $data[org::$goodsId_d];
        $payType = $orderModel->getOrderById($data[org::$orderId_d]);
        //获取退款金额
        $orderGoodsModel = BaseModel::getInstance(OrderGoodsModel::class);
       
        $monery = $orderGoodsModel->getMonery($data[org::$orderId_d], $data[org::$goodsId_d]);
        
        $this->currtModel = clone $orderModel;

        $status = $this->cancelOrder($monery, $data[org::$orderId_d], $payType);
        
       !empty($status) ? $this->success('退款成功，请查收') : $this->error('退款失败');
    }
    
    /**
     * 修改 退货状态 
     */
    public function editReturnGoods ()
    {
        $colum = ['id', 'status'];
        Tool::checkPost($_POST, ['is_numeric' => ['id', 'status']], true, $colum) ? : $this->ajaxReturnData(null, 0, '操作失败');
       
        $model = BaseModel::getInstance(OrderReturnGoodsModel::class);
        switch($_POST['status']){
            case  0:
                $status = 5;
                break;
            case  1:
                $status = 6;
                break;
            case  2:
                $status = 7;
                break;
            case  3:
                $status = 7;
                break;
            case  4:
                $status = 8;
                break;
            case  5:
                $status = 9;
                break;
            case  6:
                $status = 4;
                break;
        }
        //$order_id = $model->where(['id'=>$_POST['id']])->getField('order_id');
        $order = $model->field('order_id,user_id,type,price,goods_id,number')->where(['id'=>$_POST['id']])->select()[0];
       $res      = M('order')->where(['id'=>$order['order_id']])->setField('order_status',$status);
        $res      = M('order_goods')->where(['order_id'=>$order['order_id'],'goods_id'=>$order['goods_id']])->setField('status',$status);

        if($status == 9){
            $rebate = M('order_goods')->where(['order_id'=>$order['order_id'],'goods_id'=>$order['goods_id']])->getField('good_rebate');
            $self_rebate = M('order_goods')->where(['order_id'=>$order['order_id'],'goods_id'=>$order['goods_id']])->getField('self_rebate');

            $num = $order['number'] ?$order['number'] :1;
            $rebate = (int)$rebate*(int)$num;
            $self_rebate = (int)$self_rebate*(int)$num;

            $data=M('user')->where(['id'=>$order['user_id']])->select()[0];
            $p_id   = $data['pid'];
            $data2=M('user')->where(['id'=>$p_id])->select()[0];

           //减去返利
            $order_sn_id =M('order')->where(['id'=>$order['order_id']])->getField('order_sn_id');
            $id=M('rebate_log')->where(['pid'=>$order['user_id'],'oid'=>$order_sn_id])->getField('id');
            $id2=M('rebate_log')->where(['pid'=>$data['p_id'],'oid'=>$order_sn_id])->getField('id');
            $id3=M('rebate_log')->where(['pid'=>$data2['p_id'],'oid'=>$order_sn_id])->getField('id');
             //自购减利
            M('user')->where(['id'=>$order['user_id']])->setDec('rebate_money',$self_rebate);
            if(($order['price']-$self_rebate>0) && $id2 ){
                //上级减利
                 if($data['member_status']>1){
                    $res=M('user')->where(['id'=>$data['p_id']])->setDec('rebate_money',$rebate);
                 }
             }
             if(($order['price']-$self_rebate-$rebate>0) && $id3){
               //上上级返利
                 if($data['member_status']>2){
                   $data3=M('user')->where(['id'=>$data2['p_id']])->select()[0];
                   $res=M('user')->where(['id'=>$data2['p_id']])->setDec('rebate_money',$rebate * $data3['two_rebates']*0.01);
                    }
            }
           $res=M('rebate_log')->where(['oid'=>$order_sn_id])->setField('status',1);

            //增加库存
            $goodsModel = M('goods');
            $goods = M('order_goods')->field('id,goods_id,goods_num')->where('order_id='.$order['order_id'])->select();
            $str = '';
            foreach( $goods as $v1 ){
                $str .= $v1[ 'goods_id' ] . ',';
            }
            $p_goods = $goodsModel->where( [ 'id' => [ 'IN',\rtrim( $str,',' ) ] ] )->getField( 'id,p_id,price_member' );
            foreach($goods as $v){
                $res  = $goodsModel->where(['id'=>$v['goods_id']])->setInc('stock',$v['goods_num']);
                $res2 = $goodsModel->where(['id'=>$p_goods[ $v['goods_id'] ]['p_id']])->setInc('stock',$v['goods_num']);
                $_goods = M('spec_goods_price')->where(['goods_id'=>$v['goods_id']])->setInc('store_count',$v['goods_num']);
            }
        }
        $res2 = $model->save($_POST);
        $this->promptPjax($res, '保存错误');
        $this->promptPjax($res2, '保存错误');

        $this->ajaxReturnData(['url' => U('returnGoods')]);
    }

    /**
     * 导出订单excel
     */
    public function expOrders()
    {
        $cond = [];
        $xlsName = "orders";
        $xlsCell = array(
            array('order_sn_id', '订单号'),
            array('user_id', '购买人昵称'),
            array('realname', '收件人姓名'),
            array('mobile', '手机'),
            array('prov', '省'),
            array('city', '市'),
            array('dist', '区'),
            array('address', '地址'),
            array('zipcode', '邮编'),
            array('expname', '快递公司名'),
            array('express_id', '快递单号'),
            array('order_status', '订单状态'),
            array('title', '物品名称'),
            array('sku', '商品编码'),
            array('goodsnum', '数量'),
            array('pay_time', '付款时间'),
            array('delivery_time', '发货时间'),
            array('remarks', '买家备注'),
            array('admin_remarks', '卖家备注'),
            array('price_sum', '订单总价'),
            array('ware_id', '发货仓名'),
            array('supplier', '供应商名')

        );
        $xlsModel = M('Order');
        $userModel = M('User');
        $addressModel = M('UserAddress');
        $regionModel = M('Region');
        $ordertogoodsModel = M('OrderGoods');
        $goodsModel = M('Goods');
        $expressModel = M('Express');
        $specGoods  = M('spec_goods_price');
        $sendAddress  = M('send_address');
        $supplierModel = M('supplier');

        $get = $_GET;
        $cond = $this->getWhere($get);


        $oid = '';
        $uid = '';
        $addressid = '';
        $expid = '';
        $gid = '';
        $wareId = '';
        $orderData = $xlsModel
            ->where($cond)
            ->order('id desc')
            ->getField('id,order_sn_id,user_id,address_id,pay_time,remarks,exp_id,express_id,delivery_time,order_status,price_sum,admin_remarks,ware_id');
        foreach($orderData as $v1){
            $oid .= $v1['id'].',';
            $uid .= $v1['user_id'].',';
            $addressid .= $v1['address_id'].',';
            $expid .= $v1['exp_id'].',';
            $wareId .= $v1['ware_id'].',';
        }
        unset($v1);
        $oid = rtrim($oid,',');
        $uid = rtrim($uid,',');
        $addressid = rtrim($addressid,',');
        $expid = rtrim($expid,',');
        //商品订单
        $orderGoods = $ordertogoodsModel->where(['order_id'=>['in',$oid]])->order('id desc')->getField('id,order_id,goods_id,goods_num');
        //用户信息
        $user = $userModel->where(['id'=>['in',$uid]])->getField('id,user_name,mobile');
        //收货地
        $orderAddress = $addressModel->where(['id'=>['in',$addressid]])->getField('id,realname,mobile,user_id,prov,city,dist,address,zipcode');
        //快递
        $express = $expressModel->where(['id' => ['in',$expid]])->getField('id,name,code');
        foreach($orderGoods as $gvo){
            $gid .= $gvo['goods_id'].',';
        }
        unset($gvo);
        //商品
        $gid = rtrim($gid,',');
        $goods = $goodsModel->where(['id'=>['in',$gid]])->getField('id,title,stock,supplier_id');
        $goods_spe = $specGoods->where(['goods_id'=>['in',$gid]])->getField('goods_id,sku,key');
        //发货地
        $sendAdd = $sendAddress->where(['id' => ['in',$wareId]])->getField('id,stock_name,status');
        //供应商
        $supp_id = array_column($goods,'supplier_id');
        $supplier = $supplierModel->where(['id' => ['in',$supp_id]])->getField('id,name,status');
        //组合数据
        foreach($orderGoods as $ogk=>$ogo){
            $data[$ogo['order_id']]['id'] = $ogo['order_id'];
            $data[$ogo['order_id']]['order_sn_id'] = $orderData[$ogo['order_id']]['order_sn_id'];
            $data[$ogo['order_id']]['user_id'] = $user[ $orderData[$ogo['order_id']]['user_id'] ]['user_name'];
            $data[$ogo['order_id']]['realname'] = $orderAddress[ $orderData[$ogo['order_id']]['address_id'] ]['realname'];
            $data[$ogo['order_id']]['mobile'] = $orderAddress[ $orderData[$ogo['order_id']]['address_id'] ]['mobile'];
            $data[$ogo['order_id']]['prov'] = $regionModel->where(['id' => $orderAddress[ $orderData[$ogo['order_id']]['address_id'] ]['prov']])->getField('name');
            $data[$ogo['order_id']]['city'] = $regionModel->where(['id' => $orderAddress[ $orderData[$ogo['order_id']]['address_id'] ]['city']])->getField('name');
            $data[$ogo['order_id']]['dist'] = $regionModel->where(['id' => $orderAddress[ $orderData[$ogo['order_id']]['address_id'] ]['dist']])->getField('name');
            $data[$ogo['order_id']]['address'] = $orderAddress[ $orderData[$ogo['order_id']]['address_id'] ]['address'];
            $data[$ogo['order_id']]['zipcode'] = $orderAddress[ $orderData[$ogo['order_id']]['address_id'] ]['zipcode'];
            $data[$ogo['order_id']]['expname'] = $express[ $orderData[$ogo['order_id']]['exp_id'] ]['name'];
            $data[$ogo['order_id']]['express_id'] = $orderData[$ogo['order_id']]['express_id'];
            $data[$ogo['order_id']]['order_status'] = $orderData[$ogo['order_id']]['order_status'];
            $data[$ogo['order_id']]['title'] .= $goods[$ogo['goods_id']]['title'].',//,';
            $data[$ogo['order_id']]['sku'] .= $goods_spe[$ogo['goods_id']]['sku'].',//,';
            $data[$ogo['order_id']]['goodsnum'] .= $ogo['goods_num'].',//,';
            $data[$ogo['order_id']]['pay_time'] = $orderData[$ogo['order_id']]['pay_time'] ? date('Y-m-d H:m:s',$orderData[$ogo['order_id']]['pay_time']) : null;;
            $data[$ogo['order_id']]['delivery_time'] = $orderData[$ogo['order_id']]['delivery_time'] ? date('Y-m-d H:m:s',$orderData[$ogo['order_id']]['delivery_time']) : null;
            $data[$ogo['order_id']]['remarks'] = $orderData[$ogo['order_id']]['remarks'];
            $data[$ogo['order_id']]['admin_remarks'] = $orderData[$ogo['order_id']]['admin_remarks'];
            $data[$ogo['order_id']]['price_sum'] = $orderData[$ogo['order_id']]['price_sum'];
            $data[$ogo['order_id']]['ware_id'] = $sendAdd[ $orderData[ $ogo['order_id'] ]['ware_id'] ]['stock_name'];
            $data[$ogo['order_id']]['supplier'] = $supplier[ $goods[$ogo['goods_id']]['supplier_id'] ]['name'] .',//,';
        }
        unset($ogo);
        $data = array_values($data);

        $this->exportExcel($xlsName,$xlsCell,$data);

    }

    /**
     * @desc  生成Excel
     * @param unknown $expTitle
     * @param unknown $expCellName
     * @param unknown $expTableData
     */
    public function exportExcel($expTitle,$expCellName,$expTableData) {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);
        $fileName = $expTitle.date('_YmdHis');
        $cellNum = count($expCellName);
        $dateNum = count($expTableData);
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new \PHPExcel;
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:'.$cellName[$cellNum-1].'1');
        for($i = 0; $i<$cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
            $objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$i])->setAutoSize(true);
        }
        for($i = 0; $i<$dateNum; $i++) {
            for($j = 0; $j<$cellNum; $j++) {
                if(in_array($expCellName[$j][0], ['order_sn_id', 'mobile'])) {
                    $objPHPExcel->getActiveSheet()->setCellValue($cellName[$j].($i+3),' '.$expTableData[$i][$expCellName[$j][0]].' ');
                }else {
                    $objPHPExcel->getActiveSheet()->setCellValue($cellName[$j].($i+3),' '.$expTableData[$i][$expCellName[$j][0]]);
                }
            }
        }


        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    //获取where条件
    public function getWhere($post){
        $where = [];
        //收件人模糊
        $addressModel = BaseModel::getInstance(UserAddressModel::class);
        if (!empty($post['realname'])) {
            $userArray1 = $addressModel->getUserByRealName($post['realname']);
            $user1 = implode(",", $userArray1);
        }
        //手机号模糊
        if(!empty($post['mobile'])){
            $userArray2 = $addressModel->getUsersMobile($post['mobile']);
            $user2 = implode(",", $userArray2);
        }
        if(!empty($user1) || !empty($user2)){
            $where ['user_id'] = ['in',$user1.','.$user2];
        }
        //时间
        if( !empty( $post[ 'timegap' ]) && !empty( $post[ 'timeEnd' ]) ){
            $where[ 'create_time' ] = [ 'BETWEEN',\strtotime( $post[ 'timegap' ] ) . ',' . \strtotime( $post[ 'timeEnd' ] ) ];
        }elseif( empty($post[ 'timegap' ]) && !empty( $post[ 'timeEnd' ]) ){
            $where[ 'create_time' ] = [ 'ELT',\strtotime( $post[ 'timeEnd' ] ) ];
        }elseif( !empty($post[ 'timegap' ]) && empty( $post[ 'timeEnd' ]) ){
            $where[ 'create_time' ] = [ 'EGT',\strtotime( $post[ 'timegap' ] ) ];
        }
        //状态
        if( $post['order_status'] !== '' ){
            $where[ 'order_status' ] = $post[ 'order_status' ];
        }
        //商品名称
        if(!empty($post['goods'])){
            $rand['title'] = ['like','%'.$post['goods'].'%'];
            $userArray = M('goods')->field('id')->where($rand)->select();
            if(!empty($userArray)){
                foreach($userArray as $k=>$v){
                    $userid[$k] = $v['id'];
                }
                $user3 = implode(",", $userid);
            }

            if(!empty($user3)){
                $orwhere['goods_id'] = ['in',$user3];
                $orderArray = M('order_goods')->field('order_id')->where($orwhere)->select();
                if(!empty($orderArray)){
                    foreach($orderArray as $k2=>$v2){
                        $oid[$k2] = $v2['order_id'];
                    }
                    $oid_str = implode(",", $oid);
                }
                if(!empty($oid[0])){
                    $where ['id'] = ['in',$oid_str];
                }
            }
        }
        //发货仓
        if(!empty($post['ware_id']) ){
            $send_address = M('send_address')->where(['stock_name'=>['like','%'.$post['ware_id'].'%']])->select();
            $ware_ids = array_column($send_address,'id');

            $where[ 'ware_id' ] = ['in',$ware_ids];
        }
        //普通订单or团购订单
        if($post['order_type'] == 1){
            $where['group_id'] = 0;
        }elseif($post['order_type'] == 2){
            $where['group_id'] = ['neq',0];
        }
        if( $where === [] ) $where = '1=1';//方便缓存数据

//        $where['status'] = 0;
        return $where;
    }

    //取消订单，逻辑删除订单
    public function setOrderStatus (){
        $order_id = I('post.id');
        $status = I('post.status');
        if($status == '-1'){
            //改变订单状态
            $res = M('order')->where(['id'=>$order_id])->setField('order_status',-1);
            //退回库存,一个订单只有一个商品
            $goods = M('order_goods')->field('goods_id,goods_num')->where(['order_id'=>$order_id])->select();
            foreach($goods as $v){
                $good_pid = M('goods')->where(['id'=>$v['goods_id']])->getField('p_id');
                M('goods')->where(['id'=>$v['goods_id']])->setInc('stock',$v['goods_num']);
                M('goods')->where(['id'=>$good_pid])->setInc('stock',$v['goods_num']);
                M('spec_goods_price')->where(['goods_id'=>$v['goods_id']])->setInc('store_count',$v['goods_num']);
            }

        }else if($status == '-2'){
            $res = M('order')->where(['id'=>$order_id])->setField('status',1);
        }
        if($res){
            if($status == '-1'){
                $this->ajaxReturnData('',1,'取消成功');
            }
            $this->ajaxReturnData(U('orderList'),1,'删除成功');
        }else{
            $this->ajaxReturnData('2',0);
        }

    }

    //申请售后(退货)
    public function sendReturnGoods() {
        //
        $goods_id = I('get.goods_id');//商品id
        $order_id = I('get.order_id/d');//订单id
        //查询商品订单表
        $order_goods = $this->getOrderGoodsByGoodsId($goods_id,$order_id);
        //查询订单表数据
        $order = M('order')->where('id='.$order_id)->find();

        //查询订单商品表对应的商品信息
        if (empty($order_goods)) {
            return false;
        }
        $where['id']             = $order_goods['goods_id'];
        $name                    = M('Goods')->field('class_id,title,p_id,price_member')->where($where)->find();
        $order_goods['title']    = $name['title'];
        $order_goods['class_id'] = $name['class_id'];
        $order_goods['p_id']     = $name['p_id'];
        $order_goods['price_member'] = $name['price_member'];
        $Goods = $order_goods;
        //查询商品图片
//        new GoodsImagesModel()
        $goods = GoodsImagesModel::getGoodsImageByGoods($Goods);
        //商品总价
        $goods['goods_price_sum'] = $goods['goods_price']*$goods['goods_num'];
        //用户信息
        $user = M('user')->where(['id'=>$order['user_id']])->field('id,mobile,user_name')->select()[0];

        $this->assign('user',$user);
        $this->assign('order',$order);
        $this->assign('goods',$goods);
        $this->display();
    }
    //提交退货申请
    public function return_goods_add(){
        $m = M('order_return_goods');

        if (IS_POST){
            $where['goods_id'] = I('post.goods_id');//商品id
            $where['order_id'] = I('post.order_id');//订单id
            $result = M('order_return_goods')->where($where)->find();
            if (!empty($result)) {
                $this->error('该商品申请退货已经提交!');
            } else{
                $upload = new \Think\Upload($this->config);// 实例化上传类
                //上传文件
                $info = $upload->upload();
                $voucher = '';
                if($info) {// 上传成功
                    foreach ($info as $key => $value) {
                        $voucher .= '/'.Uploads.'/'.$value['savepath'].$value['savename'].',';
                    }
                }
                $data['voucher'] = '';
                $data['tuihuo_case'] = I('post.tuihuo_case');//退货原因
                $data['order_id'] = I('post.order_id');//订单id
                $data['goods_id'] = I('post.goods_id');//商品id
                $data['explain'] = I('post.explain');//退货说明
                $data['price'] = I('post.price');//退款金额
                $data['mumber'] = I('post.mumber');//数量
                $data['type'] = I('post.type');//退换货
                $data['is_receive'] = I('post.is_receive');//是否收到货
                $data['create_time'] = time();//添加时间
                $data['user_id'] = I('post.user_id');//用户id
                $data['status'] = 0;//状态
                $data['apply_img'] = substr($voucher,0,-1);
                $res=$m->data($data)->add();
                if ($res) {
                    M('order_goods')->where(['goods_id'=>I('post.goods_id'),'order_id'=>I('post.order_id')])->setField('status','5');
                    M('order')->where(['id'=>I('post.order_id')])->setField('order_status','5');

                    $this->success('申请成功',U('orderList'));exit;
                }
                $this->error('申请失败');

            }
        }
    }

    //查询订单商品表信息
    public function getOrderGoodsByGoodsId($goods_id,$order_id){
        if (empty($goods_id)) {
            return false;
        }
        if (empty($order_id)) {
            return false;
        }
        $where['goods_id'] = $goods_id;
        $where['order_id'] = $order_id;
        $field = 'goods_id,goods_num,goods_price,user_id';
        $res = M('Order_goods')->where($where)->find();
        return $res;
    }

    /*
     * 后台修改订单状态
     */
    public function editOrderStatus(){
        $order_status = $_POST['order_status'];
        $order_id = $_POST['order_id'];
        $admin_remarks = $_POST['admin_remarks'];

        $data['order_status'] = (string)$order_status;
        $data['admin_remarks'] = $admin_remarks;
        if($order_status>4){

            $return = M('order_return_goods')->where(['order_id'=>$order_id])->getField('id');
            if($return){

                switch($order_status){
                    case  5:
                        $status = 0;
                        break;
                    case  6:
                        $status = 1;
                        break;
                    case  7:
                        $status = 3;
                        break;
                    case  8:
                        $status = 4;
                        break;
                    case  9:
                        $status = 5;
                        break;
                    case  4:
                        $status = 6;
                        break;
                }
                $result = M('order')->where(['id'=>$order_id])->save($data);
                $result2 = M('order_goods')->where(['id'=>$order_id])->save(['order_status'=>$order_status]);
                $result3 = M('order_return_goods')->where(['id'=>$order_id])->save(['status'=>$status]);
                if($result){
                    $this->ajaxReturnData('',1,'修改成功');
                }else{
                    showData(11,1);
                    $this->ajaxReturnData('',0,'修改失败');
                }

            }else{
                $this->ajaxReturnData('',3,'未申请售后,请先申请售后');
            }
        }else{
            $result = M('order')->where(['id'=>$order_id])->save($data);
            $result2 = M('order_goods')->where(['id'=>$order_id])->save(['order_status'=>$order_status]);
           showData($result,1);
            if($result){
                $this->ajaxReturnData('',1,'修改成功');
            }else{

                $this->ajaxReturnData('',0,'修改失败');
            }
        }

    }

    /**
     * ajax 退货单导出
     * @copyright 版权所有©亿速网络
     */
    public function outReturnGoods ()
    {
        $xlsName = "ReturnGoods";
        $xlsCell = array(
            array('order_sn_id', '订单编号'),
            array('goods', '商品名称'),
            array('price', '退款金额'),
            array('type', '类型'),
            array('paytype', '支付类型'),
            array('status', '状态')
        );


        $model = BaseModel::getInstance(org::class);
        Tool::isSetDefaultValue($_GET, array(//设置默认值 版权所有©亿速网络
            'orderBy' => org::$createTime_d,
            'sort'    => BaseModel::DESC
        ));
        $where = array();
        Tool::connect('ArrayChildren');

        $model->setNoValidate([//不检测的搜索键
            org::$orderId_d
        ]);

        $where = $model->buildSearch($_GET);

        $orderModel = BaseModel::getInstance(OrderModel::class);

        Tool::connect('parseString');

        $where[org::$orderId_d] = $orderModel->getSearch($_GET);
        if (!empty($where['order_id'])) {
            $where['order_id'] = ['in', str_replace('"', null, $where['order_id'])];
        } elseif (isset($where['order_id'])) {
            unset($where['order_id']);
        }

        $field = 'order_id,goods_id,price,status,type';
        $data = M('order_return_goods')->field($field)->where($where)->order("create_time desc")->select();

        $goodsid = array_column($data,'goods_id');
        $goods = M('goods')->where(['id'=>['in',$goodsid]])->getField('id,title,price_member');

        $orderid = array_column($data,'order_id');
        $order = M('order')->where(['id'=>['in',$orderid]])->getField('id,order_sn_id,pay_type');

        $returnStatus = C('returnGoods');
        $payType = array(
            0 => '',
            1 => '微信',
            2 => '支付宝',
            3 => '银联',
            4 => '余额'
        );
        $type = array(
            0 => '换货',
            1 => '退货',
            2 => '退款',
            3 => '维修'
        );
        foreach($data as &$v){
            $v['order_sn_id'] = $order[$v['order_id']]['order_sn_id'];
            $v['goods'] = $goods[$v['goods_id']]['title'];
            $v['status'] = $returnStatus[$v['status']];
            $v['type'] = $type[$v['type'] ] ;
            $v['paytype'] = $payType[ $order[ $v['order_id'] ] ['pay_type'] ] ;

        }

        $this->exportExcel($xlsName,$xlsCell,$data);
    }



    //订单列表 - 团购订单列表     ---meng
    public function orderGroupList()
    {
        $condition = [
            '1'=>'拼团中',
            '2'=>'拼团成功',
            '3'=>'拼团失败'];
        $this->assign('condition',$condition);
        $this->display();
    }

    //订单列表 - 获取团购商品列表   ---meng
    public function getOrderGroupList()
    {
        $search=I("post.code");
        $goods=I("post.goods");
        $timegap=I("post.timegap");
        $timeEnd=I("post.timeEnd");
        $group_status=I("post.group_status");
        $is_host=I("post.is_host");
        if($search){
            if($goods){
                $where1=" p.title like '%{$goods}%' ";
            }else{
                $where1=" 1=1 ";
            }

            if($timegap){
                $where2=" and r.create_time > $timegap ";
            }else{
                $where2=" and 1=1 ";
            }

            if($timeEnd){
                $where3=" and r.over_time < $timeEnd ";
            }else{
                $where3=" and 1=1 ";
            }

            //如果有团单状态，搜索出来主订单的
            if($group_status){
                $where4=" and r.group_status = $group_status ";
            }else{
                $where4=" and  1=1 ";
            }

            //如果搜索所有团长订单
            if($is_host){
                $where5=" and r.pid = 0 and r.is_host = 1 ";
            }else{
                $where5=" and r.pid >= 0 ";
            }


            $where6=" and r.group_id > 0 ";
            $where=$where1.$where2.$where3.$where4.$where5.$where6;

        }else{
            $where=['r.group_id'=>['gt',0],'r.pid'=>['egt',0]];
        }
//        showData($where);die;
        $field="r.id,r.order_sn_id,r.price_sum,r.order_status,r.group_status,r.group_id,r.pay_time,r.remarks,r.express_id,r.is_host,p.title,p.goods_id,p.goods_num";
//        $get_p=(I("get.p",1)-1)*10;
        $get_p=I("get.p",1);
        $groupList  = M("order")
            ->alias('r')
            ->join('db_group as p ON r.group_id=p.id')
            ->where($where)
            ->field($field)
            ->order('r.create_time DESC')
            ->page($get_p,10)
            ->select();
        //拼接图片 发货仓
        foreach ($groupList as $k=>$v){
            $goodsPid = M('goods')->where(["id"=>$v['goods_id']])->getField("p_id");
            $goodsTmp['pic_url'] = M('goods_images')->where(['goods_id'=>$goodsPid ])->getField('pic_url');
            $goodsTmp['stock_name'] = M('goods')
                                    ->alias('g')
                                    ->join('db_send_address as a ON g.send_address=a.id')
                                    ->where(['g.id'=>$v['goods_id']])
                                    ->getField("a.stock_name");
            $groupListData[] = array_merge($v,$goodsTmp);
        }
        $orderStatus= [
            '-1'=>'取消订单',
            '0'=>'未支付',
            '1'=>'发货中',
            '2'=>'已收货',
            '3'=>'发货',
            '4'=>'退货审核中',
            '5'=>'审核失败',
            '6'=>'审核成功',
            '7'=>'审核成功',
            '8'=>'退款中',
            '9'=>'退款成功',
        ];

        $num=M("order")
            ->alias('r')
            ->join('db_group as p ON r.group_id=p.id')
            ->where($where)
            ->field($field)
            ->count();
        $this->assign('groupList',$groupListData);
        $this->assign('orderStatus',$orderStatus);
        $Page= new \Think\Page($num,10);
        $show= $Page->show();
        $this->assign('page',$show);
        $this->display();

    }

    //订单列表 - 团购订单列表详情  ---显示团成员    ---meng
    public function groupCouponInformation()
    {
        $orderId=I("get.order_id");
        $orderModel = new OrderModel();
        $groupOrderUserData = $orderModel->getGroupOrderUser($orderId);  //全团成员简要
        $orderStatus= [
            '-1'=>'取消订单',
            '0'=>'未支付',
            '1'=>'已支付',
            '2'=>'发货中',
            '3'=>'已收货',
            '4'=>'发货',
            '5'=>'退货审核中',
            '6'=>'审核失败',
            '7'=>'审核成功',
            '8'=>'退款中',
            '9'=>'退款成功',
        ];
//        showData($groupOrderUserData);
        //订单数据
        $orderData = $orderModel->where(['id'=>$orderId])->find();
        $expName  = M('express')->where(['id'=>$orderData['exp_id']])->getField('name');
        $user_address  = M('user_address')->where(['id'=>$orderData['address_id']])->find();
        $goods  = M('group')->field('title,price,goods_id')->where(['id'=>$orderData['group_id']])->find();
        $user  = M('user')->where(['id'=>$orderData['user_id']])->find();
        $user_address ['prov'] = M('region')->where('id='.$user_address['prov'])->getField('name');
        $user_address ['city'] = M('region')->where('id='.$user_address['city'])->getField('name');
        $user_address ['dist'] = M('region')->where('id='.$user_address['dist'])->getField('name');

        $this->assign('user',$user);
        $this->assign('goods',$goods);
        $this->assign('user_address',$user_address);
        $this->assign('expName',$expName);
        $this->assign('orderData',$orderData);
        $this->assign('UserData',$groupOrderUserData);
        $this->assign('orderStatus',$orderStatus);
        $this->display();
    }
    //团购订单 修改团购订单的状态 确认发货填写快递单号时，进行团长返利   ---meng
    public function groupHostRebate($orderId){
        $res1= M('order')->where(['id'=>$orderId])->find();
        if($res1['is_host'] == 1 && $res1['order_status'] == "3" && $res1['rebate_status'] == 0 ){
            $rebate = M('group')->field("rebate_host,price,group_person_num")->where(['id'=>$res1['group_id']])->find();
            $rebateMonery   = $rebate['price'] * $rebate['group_person_num'] * $rebate['rebate_host'];
            $goodsAllMonery = $rebate['price'] * $rebate['group_person_num'];
            $RebateLog            = new RebateLogController($orderId);
            $RebateLog->insertHost($rebateMonery,$goodsAllMonery);
        }
    }
    /**
     * 根据订单id增加用户积分     ---meng
     *
     */
    public function  addUserIntegral($orderId)
    {
        $exist = M('integral_use')->where(['order_id'=>$orderId])->find();
        if($exist){
            return true;
        }
        $orderData = M('order_goods')->field("goods_id,goods_num,user_id")->where(['order_id'=>$orderId])->select();
        //获得商品p_id
        $goodsPidArr= M('goods')->field("p_id")->where(['id'=>['in',array_column($orderData,'goods_id')]])->select();
        $userLevel = M('user')->where(['id'=>$orderData[0]['user_id']])->getField("level_id");
        $goods_integral = M('goods_integral')->where(['goods_pid'=>['in',array_column($goodsPidArr,'p_id')]])->select();
        $userintegral = 0;
        foreach ($orderData as $k => &$v){
            switch ($userLevel)
            {
                case 1:
                    $integral = $goods_integral[$k]['user_level1'];
                    break;
                case 2:
                    $integral = $goods_integral[$k]['user_level2'];
                    break;
                case 3:
                    $integral = $goods_integral[$k]['user_level3'];
                    break;
                case 4:
                    $integral = $goods_integral[$k]['user_level4'];
                    break;
            }

            $v['integral'] = $v['goods_num'] * $integral;
            $userintegral += $v['integral'];
            $v['type']     = 1;
            $v['order_id'] = $orderId;
            $v['trading_time'] = time();
        }
        $trans = M();
        $trans->startTrans();
        $result = M('integral_use')->addAll($orderData); //返回最后插入的id

//        $res = M('user')->where(['id'=>$orderData[0]['user_id']])->setInc('integral',$userintegral);   //添加到用户表 (暂时不添加)
//        if($result && $res){
        if($result){
            $trans->commit();
        }else{
            file_put_contents('./Uploads/group/integralLog.txt',date('Y-m-d H:i:s',time())."积分返还失败-------订单id".$orderId."\r\n".\print_r($orderId,true)."\r\n"."\r\n",8);
            $trans->rollback();
        }
        return true;
    }


}