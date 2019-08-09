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

use Home\Model\UserGradeModel;
use Home\Model\UserModel;
use Think\Controller;
use Common\TraitClass\InternetTopTrait;
use Common\Tool\Tool;
use Common\Model\BaseModel;
use Common\Model\UserAddressModel;
use Common\Model\RegionModel;
use Common\TraitClass\AddressTrait;
use Common\TraitClass\NoticeTrait;
use Common\Model\ExpressModel;
use Home\Model\GoodsModel;
use Home\Model\GoodsImagesModel;
use Home\Model\SpecGoodsPriceModel;
use Home\Model\OrderModel;
use Home\Model\OrderGoodsModel;
use Home\Model\CouponListModel;
use Home\Model\CouponModel;
use Home\Model\GoodsCartModel;
use Common\TraitClass\FrontGoodsTrait;
use Home\Model\PromotionGoodsModel;
use Common\Model\PromotionTypeModel;
use Common\Tool\Extend\ArrayChildren;
use Common\Tool\Event;
use Home\Model\PromGoodsModel;
use Home\Model\InvoiceCompanyModel;
use Home\Model\InvoiceTypeModel;
use Common\TraitClass\SmsVerification;
use Common\Content\Content;
use Think\Hook;
use Common\Behavior\WangJinTing;
use Home\Logical\AmountLogic;
use Home\Model\BalanceModel;
use Common\TraitClass\FreightTrait;
use Home\Model\CommodityGiftModel;
use Home\Model\IntegralUseModel;
use Home\Model\GroupModel;


/**
 * 结算
 */
class SettlementController extends Controller
{
    use InternetTopTrait;
    use AddressTrait;
    use NoticeTrait;
    use FrontGoodsTrait;
    use SmsVerification;
    use FreightTrait;

    private $arrayObj;
    private $discount;
    private $specData = array();

    private $validate = [
        'pay_type',
        'address_id',
        'price_sum',
        'freight_id',
    ];
    // 0没有活动，1尾货清仓，2，最新促销，3积分商城,4打印耗材
    const NO_ACTIVITY = 0;

    // 无活动
    const DISPLAY_HTML = 'buyNow';

    const POOP_GOODS_HTML = 'poopClearByGoodsInformation';

    // 尾货清仓结算商品页
    const NORMAL_GOODS_HTML = 'poopClearByGoodsInformation';

    /**
     * 结算
     */
    public function __construct()
    {
        parent::__construct();

        $this->isLogin();
        // 初始化数据
        $this->hot_words = self::keyWord();

        $name = self::userDataExits();

        $information = $this->getIntnetInformation();

        Hook::add('reade', WangJinTing::class);
        $str = $this->getFamily();

        $this->assign("article_lists", $this->arctile());

        $this->assign($information);

        $this->assign('intnetTitle', $this->getConfig('intnet_title') . C('internetTitle.settlement'));

        $this->assign('str', $str);

        $this->assign('userId', $name);
    }

    // +------------------------------------------------
    // | [逻辑重构]
    // +------------------------------------------------
    public function shopping()
    {
        // 验证立即购买商品信息
        $this->validateBuyNow();

        // 获取商品信息
        $goodsModel = BaseModel::getInstance(GoodsModel::class);

        $goodsData = $goodsModel->getGoodsContentById($_POST['goods_id']);

        $this->prompt($goodsData);

        // 活动处理
        $activityType = Content::parseCall($goodsData[GoodsModel::$status_d]);

        $contentObj = new Content($activityType, $goodsData);

        $detailAlgMoneryObj = $contentObj->newInstance();

        $algMoneyType = $detailAlgMoneryObj->getResult(); // 获取计算类型

        $this->promptParse($algMoneyType !== false, '商品活动错误');

        $goods = $detailAlgMoneryObj->getGoods();

        $goods['goods_num'] = $_POST['goods_num'];

        // 活动价格处理
        Content::setActivityType(C('promotion_type')); // 设置 收费处里对象

        $promotionType = Content::parseCall($algMoneyType);

        $contentObj->setActivityObj($promotionType);

        $contentObj->setConstructParam($goods);

        $goodsData = $contentObj->newInstance()->acceptCash();

        // +----------------------------
        $this->payAndAddress(); // 地址及其支付信息
        // |-----------------------------

        $this->setKeyByOpreator(GoodsModel::$id_d);
        $this->parseGoodsData(array(
            $goodsData
        )); // 商品信息

        $this->invoiceAllow(); // 发票信息

        //$this->gift(); // 赠品信息

        $this->assign('goods_html', self::POOP_GOODS_HTML);

        $this->assign('totalMonery', $goodsData['totalMoney']);

        $this->assign('activityType', C('activity_type'));

        $this->assign('activityModel', PromotionTypeModel::class);

        $this->display(self::DISPLAY_HTML);
    }

    /**
     * 获取余额
     */
    public function getBalaceMoney()
    {
        $this->ajaxReturnData([
            'money' => BaseModel::getInstance(BalanceModel::class)->getBalanceMoney(),
        ]);
    }

    /**
     * 立即购买
     * $this->setUseModel($goodsModel);
     * // // 是否是 促销产品
     * // $dataPromo = $this->getPromotionDataByGoods($_POST);
     * // $goodsModel->setSplit(PromotionGoodsModel::$goodsId_d); // 设置分割键
     */
    public function buyNow()
    {
        // 验证立即购买商品信息
        $this->validateBuyNow();
        unset($_SESSION['formId']);    //---meng
        // +----------------------------
        $this->payAndAddress(); // 地址及其支付信息

        Tool::connect('parseString');

        // 获取商品信息
        $goodsModel = BaseModel::getInstance(GoodsModel::class);
        $soldtime = $this->getSoldTime($_POST['goods_id']);
        $goodsData = $goodsModel->getGoodsById($_POST['goods_id'], []);

        $this->promptParse($goodsData, '商品数据错误');

        $this->setKeyByOpreator(GoodsModel::$id_d);

        $this->parseGoodsData($goodsData);
        //重定等级折扣  -------------meng
        //把折扣最终价价存session
        if($_SESSION['user_id']){
            $gid=$_POST['goods_id'];
            $goods_price=get_level_price($_SESSION['user_id'],$gid);
            $_SESSION['buy_goods'][$gid]=$goods_price;
        }
        // 判断用户是否添加过发票信息
        $this->invoiceAllow(); // 发票信息
        foreach($goodsData as $vo){
            //增加购买商品缓存
            $goods[$vo['id']]['id'] = $vo['id'];
//            $goods[$vo['id']]['price'] = $vo['price_member'];      //注释掉   ---meng
            $goods[$vo['id']]['price'] = $goods_price;
            $goods[$vo['id']]['num'] = $_POST['goods_num'];
            $goods[$vo['id']]['freights_id'] = $vo['freights_id'];
            $goods[$vo['id']]['good_rebate'] = $vo['good_rebate'];
            $goods[$vo['id']]['self_rebate'] = $vo['self_rebate'];
            $goods[$vo['id']]['send_address'] = $vo['send_address'];
            $goods[$vo['id']]['weight'] = $vo['weight'];

            $rebate += $_POST['goods_num'] * $vo['self_rebate'];
        }


        $_SESSION['buyByCartGoods'] = $goods;

        //获取发货仓
        $send_address = array_column($goods,'send_address');
        $sendAddrData = M('send_address')->field('id,stock_name')->where(['id'=>['IN',$send_address]])->select();


        //判断用户等级,折扣
        $IntegralUseModel = new IntegralUseModel();
        $this->discount = $IntegralUseModel->getDiscount($_SESSION['user_id']);
        $totalMonery = $goodsModel->getTotalMonery();

        //推翻上面等级折扣  -------------meng
        $_SESSION['orderTotal'] = $totalMonery;
//        //把折扣最终价价存session
//        if($_SESSION['user_id']){
//            $gid=$_POST['goods_id'];
//            $totalMonery=get_level_price($_SESSION['user_id'],$gid);
//            $_SESSION['buy_goods'][$gid]=$totalMonery;
//            $_SESSION['orderTotal'] = $totalMonery;
//        }

        //判断商品类型
        if($goodsData[0]['status'] != 0 ){

            $this->assign('activityType', C('activity_type'));
            $this->assign('activityModel', PromotionTypeModel::class);
            $this->assign('goodsModel', GoodsModel::class);
        }

        //多次提交验证
        $uid = $_SESSION['user_id'];
        $check = mt_rand(0,1000000);
        $_SESSION['check'.$uid] =$check;

        //是否为跨境购商品
        $customs = $goodsData[0]['is_customs'];
        $this->assign('customs',$customs);

//         $this->gift(); // 赠品信息
        $this->assign('check',$check); //防止表单多次提交
        $this->assign('rebate',$rebate); //商品返利金
        $this->assign('sendAddrData',$sendAddrData); //发货仓
        $this->assign('goods_html', self::NORMAL_GOODS_HTML);
        $this->assign('totalMonery', $totalMonery);
//        $this->assign('totalMoneryDiscount', round(max($totalMonery * $this->discount,0.01),2));   // 注释掉,修改如下  ---meng
        $this->assign('totalMoneryDiscount', $totalMonery);
        $this->display();
    }
    //防止表单多次提交
    public function getCheck(){
        $uid = $_SESSION['user_id'];
        $check  = I('get.check');
        $scheck = $_SESSION['check'.$uid];
        if($check == $scheck){
            S('check'.$uid,null);
            $this->ajaxReturnData('',1,'');
        }else{
            $this->ajaxReturnData('',0,'');
        }
    }
    //判断是否都抢购时间
    public function getSoldTime($gid){
        $goods=M('goods')->where(array('id'=>$gid))->field('price_market,sold_date,sold_time')->find();
        //添加倒计时时间戳
        $soldname = M("GoodsSoldtime")->field('name,id,starttime')->where('status=1')->select();
        $day = date('Y-m-d',$goods['sold_date']);
        foreach($soldname as $v){
            if($v['id'] == $goods['sold_time']){
                $h = $v['starttime'];
                $h = strtr($h,'-',':');
            }
        }
        $starttime = strtotime($day.$h);
        $itme =time();
        if($starttime<$itme){
            return true;
        }else{
            $this->error('未到开抢时间',U('Home/Goods/goodsDetails',array('id'=>$gid)));exit;
        }
    }

    /**
     * 优惠消息 立即购买
     */
    public function getPromotionDataByGoods(array $post)
    {
        if (empty($post) || !is_array($post)) {
            return array();
        }

        $model = BaseModel::getInstance(PromotionGoodsModel::class);

        $data = $model->getPromotionData($post, 'goods_id');
        if (empty($data)) {
            return array();
        }

        Event::insetListen('goods', function (array &$param) use ($post) { // 监听事件
            foreach ($param as &$value) {
                $value['goods_num'] = $post['goods_num'];
                $value['price_new'] = $post['price_new'];
            }
        });

        $data = self::sumPromotion($data, $model);

        return $data;
    }

    /**
     * 物流信息
     */
    public function shipping()
    {
        $expressModel = BaseModel::getInstance(ExpressModel::class);

        $expressData = $expressModel->getDefaultOpen(false);

        $this->assign('expressData', $expressData);

        $this->assign('expressModel', ExpressModel::class);

        $this->display();
    }


    /**
     * 获取地区
     */
    public function getAreaListByUserId()
    {
        $model = BaseModel::getInstance(UserAddressModel::class);

        $model->setSelectColums([
            UserAddressModel::$createTime_d,
            UserAddressModel::$updateTime_d,
            UserAddressModel::$zipcode_d
        ]);
        $userAreaList = $model->getAreaListByUserId($_SESSION['user_id'], true);

        $this->promptPjax($userAreaList);

        $regionModel = BaseModel::getInstance(RegionModel::class);

        $userAreaList = $regionModel->getRegionByUserAddress($userAreaList, $model);

        $this->assign('userAddress', $userAreaList);

        $this->assign('model', UserAddressModel::class);

        $this->display();
    }

    /**
     * 生成订单
     * @return boolean
     */
    public function BuliderOrder()
    {
        // 验证数据
        $this->validate();

        // 比较价格
        $goodsModel = BaseModel::getInstance(GoodsModel::class);

        // 分发

        Tool::connect('parseString');

        $useCouponMonery = 0; // 优惠券

        $conpouModel = null;

        if (!empty($_POST['couponListId']) && !empty($_SESSION['couponId'])) { // 验证优惠券

            $conpouModel = BaseModel::getInstance(CouponModel::class);
            $conpouListModel = BaseModel::getInstance(CouponListModel::class);
            $useCouponMonery = $conpouListModel->getCouponAmount($_POST['couponListId']);
//            $useCouponMonery = $_SESSION['own_my_coupon'];
        }
        //验证地址
        $address = M('user_address')
            ->field(
                'id,realname,mobile,prov,city,dist,address'
            )
            ->where(
                ['id'=>$_POST['address_id'],'user_id'=>$_SESSION['user_id']]
            )
            ->find();
        $this->promptParse($address, '库存不足或其他原因，抢购没有成功，下次加油！');//收货地址有误



//        $price = $_SESSION['total_money_sum']- $useCouponMonery - $_POST['price_sum'];
        $price = $_SESSION['total_money_sum']- $useCouponMonery ;
        $_POST['coupon_amount'] = $useCouponMonery;

        $validateExpress = $price < 0 ? false : true;

        $this->promptParse($validateExpress, '库存不足或其他原因，抢购没有成功，下次加油！');//数据错误

        $orderModel = BaseModel::getInstance(OrderModel::class);

        Tool::connect('Token');
        $IntegralUseModel = new IntegralUseModel();
        $this->discount = $IntegralUseModel->getDiscount($_SESSION['user_id']);
        $price_sum = $_SESSION['total_money_sum'];
//        $_POST['price_sum'] = round(max($price_sum * $this->discount,0.01),2);
        $_POST['price_sum'] = round(max($price * $this->discount,0.01),2);
        $_POST['goods_rebate'] = $_SESSION['user_goods_rebate'];
        //检测库存，更改库存
        $goodsModel = M('goods');
        $goodsModel->startTrans();
        foreach($_POST['goods_id'] as $v){
            $res1 = M('goods')->Field('stock,p_id')->where(['id'=>$v['goods_id']])->select();
            if($res1[0]['stock']>=$v['goods_num']){
                $res = M('goods')->where(['id'=>$v['goods_id']])->setDec('stock',$v['goods_num']);
                $p_goods = $goodsModel->where(['id'=>$res1[0]['p_id']])->setDec('stock',$v['goods_num']);
                $_goods = M('spec_goods_price')->where(['goods_id'=>$v['goods_id']])->setDec('store_count',$v['goods_num']);
            }else{
                $this->promptParse('', '库存不足或其他原因，抢购没有成功，下次加油！');//库存不足
            }
        }
        $insertId = $orderModel->addOrder($_POST);
        if(empty($insertId)){
            $goodsModel->rollback();
        }else{
            $goodsModel->commit();
        }

        $this->promptParse($insertId, '库存不足或其他原因，抢购没有成功，下次加油！');//数据错误

        $orderGoodsModel = BaseModel::getInstance(OrderGoodsModel::class);
        //拼接赠品
        $this->insertGiftGoods($_POST);

        $status = $orderGoodsModel->addOrderGoods($_POST['goods_id'], $insertId);

        $this->promptParse($status, '库存不足或其他原因，抢购没有成功，下次加油！');//数据错误

        // 处理库存
        $amountModel = new AmountLogic($_POST['goods_id'], $orderModel);


        $status = $amountModel->checkAmountDel();

        $this->promptParse($status, $amountModel->getError());

        if ($conpouModel instanceof CouponModel) { // 更新优惠券使用信息
            $status = BaseModel::getInstance(CouponListModel::class)->updateData($_POST['couponListId'], $insertId);
        } else
            if (!empty($_POST['cart_id'])) {

                $cartModel = BaseModel::getInstance(GoodsCartModel::class);
                $status = $cartModel->delCart($_POST['cart_id']);
            } else {
                $status = $goodsModel->commit();
            }
        $this->promptParse($status, '库存不足或其他原因，抢购没有成功，下次加油！');//创建失败
        $_SESSION['order_id'] = $insertId;
        $_SESSION['total'] = $_POST['price_sum'];

        if (!empty($_POST['prom_id'])) {
            $_SESSION['prom_id'] = $_POST['prom_id']['prom_id'];
            $_SESSION['type'] = $_POST['prom_id']['type'];
        }
        // 未选择新的发票类型插入最后一次的发票类型
        if ($_POST['invoice_id'] == null) {
            $this->redirect('PayOrder/InertnetWxpay');
        } else {
            $end_invoice_data = M('invoice')->where('id=' . $_POST['invoice_id'])->find();
            $end_order_id = M('order')->field('id,price_sum')
                ->where('user_id=' . $_SESSION['user_id'])
                ->order('id DESC')
                ->limit(1)
                ->find();
            $end_invoice_data['order_id'] = $end_order_id['id'];
            // 该价格有问题，确定之后再做修改
            $end_invoice_data['price'] = $end_order_id['price_sum'];
            $end_invoice_data['billing_date'] = time();
            array_shift($end_invoice_data);
            ($end_invoice_data);
            $addStatus = M('invoice')->add($end_invoice_data);
            if ($addStatus) {
                $this->redirect('PayOrder/InertnetWxpay');
            } else {
                return false;
            }
        }
    }
    /**
     * 分单生成订单
     * @return boolean
     */
    public function BuliderOrder2()
    {
        // 验证数据
        $this->validate();

        // 比较价格
        $goodsModel = BaseModel::getInstance(GoodsModel::class);

        // 分发
        $post = I('post.');

        //修改提交来的数据最终价格  ------meng
        foreach ($_SESSION['buyByCartGoods']  as  $k => $v){
            $post['goods_id'][$k]['goods_price'] = $v['price'];
        }

        Tool::connect('parseString');

        $useCouponMonery = 0; // 优惠券

        $conpouModel = null;
        $this->promptParse($post['freight_id'], '库存不足或其他原因，抢购没有成功，下次加油！');//收货地址有误

        if (!empty($post['couponListId']) ) { // 验证优惠券

            $conpouModel = BaseModel::getInstance(CouponModel::class);

            $conpouListModel = BaseModel::getInstance(CouponListModel::class);

            $c_gid = array_column($post['goods_id'],'goods_id');
            $c_num = array_sum(array_map(function($val){return $val['goods_num']*$val['goods_price'];}, $post['goods_id']));
            $useCoupon = $conpouListModel->getCouponAmount($post['couponListId'],$c_gid,$c_num);
            //优惠金额
            if($useCoupon){
                if($useCoupon['reduced_type'] == 1){
                    $useCouponMonery = $useCoupon['money'];
                }elseif($useCoupon['reduced_type'] == 2){
                    $useCouponMonery = $c_num*(100-$useCoupon['discount'])*0.01;
                }
            }else{
                $useCouponMonery = 0;
            }

        }
        //验证地址
        $address = M('user_address')
            ->field(
                'id,realname,mobile,prov,city,dist,address'
            )
            ->where(
                ['id'=>$post['address_id'],'user_id'=>$_SESSION['user_id']]
            )
            ->find();
        $this->promptParse($address, '库存不足或其他原因，抢购没有成功，下次加油！');//收货地址有误

//        $price = $_SESSION['total_money_sum']- $useCouponMonery - $_POST['price_sum'];
        $price = $_SESSION['total_money_sum'] - $useCouponMonery ;

        $post['coupon_amount'] = $useCouponMonery;

        $validateExpress = $price < 0 ? false : true;

        $this->promptParse($validateExpress, '库存不足或其他原因，抢购没有成功，下次加油！');//数据错误

        $orderModel = BaseModel::getInstance(OrderModel::class);

        Tool::connect('Token');
        $IntegralUseModel = new IntegralUseModel();
        $this->discount = $IntegralUseModel->getDiscount($_SESSION['user_id']);
        $price_sum = $_SESSION['total_money_sum'];
//        $_POST['price_sum'] = round(max($price_sum * $this->discount,0.01),2);
        $post['price_sum'] = round(max($price * $this->discount,0.01),2);
        $post['goods_rebate'] = $_SESSION['user_goods_rebate'];
        $post['self_rebate'] = $_SESSION['user_self_rebate'];

        //检测库存，更改库存
        $model = M();
        $model->startTrans();
        $goodsModel = M('goods');
//        $goodsModel->startTrans();
        try{
            foreach($post['goods_id'] as $v){

                $res1 = M('goods')->Field('stock,p_id')->where(['id'=>$v['goods_id']])->select();
                if($res1[0]['stock']>=$v['goods_num']){
                    $res = M('goods')->where(['id'=>$v['goods_id']])->setDec('stock',$v['goods_num']);
                    $p_goods = $goodsModel->where(['id'=>$res1[0]['p_id']])->setDec('stock',$v['goods_num']);
                    $_goods = M('spec_goods_price')->where(['goods_id'=>$v['goods_id']])->setDec('store_count',$v['goods_num']);
                }else{
                    $model->rollback();
                    $this->promptParse('', '库存不足或其他原因，抢购没有成功，下次加油！');//库存不足
                }
            }
        }catch (\Exception $e){
            $model->rollback();
            $this->promptParse('',$e->getMessage().'库存不足或其他原因，抢购没有成功，下次加油！');//库存不足
        }
        //判断是否要分单
        $data = $this->isFendan($post);

        if($data == 0 ){
            $insertId = $orderModel->addOrder($post);
            if(empty($insertId)){
//                $goodsModel->rollback();
                $model->rollback();
            }else{
//                $goodsModel->commit();
                $model->commit();
            }

                $this->promptParse($insertId, '库存不足或其他原因，抢购没有成功，下次加油！');//数据错误

                $orderGoodsModel = BaseModel::getInstance(OrderGoodsModel::class);
                //拼接赠品
                $this->insertGiftGoods($post);

                $status = $orderGoodsModel->addOrderGoods($post['goods_id'], $insertId);

                $this->promptParse($status, '库存不足或其他原因，抢购没有成功，下次加油！');//数据错误

                $str = $insertId;
                $sum_price = $post['price_sum'];
            }else{
                foreach($data as $v){
                    $insertId = $orderModel->addOrder($v);
                    if(empty($insertId)){
//                        $goodsModel->rollback();
                        $model->rollback();
                    }else{
//                        $goodsModel->commit();
                        $model->commit();
                    }

                    $this->promptParse($insertId, '库存不足或其他原因，抢购没有成功，下次加油！');//数据错误

                    $orderGoodsModel = BaseModel::getInstance(OrderGoodsModel::class);
                    //拼接赠品
                    $status =$this->insertGiftGoods($post);
                    if($status == false){
//                        $goodsModel->rollback();
                        $model->rollback();
                    }

                    $status = $orderGoodsModel->addOrderGoods($v['goods_id'], $insertId);

                    $this->promptParse($status, '库存不足或其他原因，抢购没有成功，下次加油！');//数据错误

                    $str .= $insertId.',';

                    $sum_price += $v['price_sum'];
                }
                $str = rtrim($str,',');
            }

        //如果使用了优惠劵
        if($post['couponListId']){
            $coup['order_id']=$str;
            $coup['use_time']=time();
            $coup['status']=1;
            M('coupon_list')->where([ 'id'=>$post['couponListId'] ])->save($coup);
            $couId  = M('coupon_list')->where([ 'id'=>$post['couponListId'] ])->getField('c_id');
            M('coupon')->where(['id'=>$couId])->setInc('use_num');
        }


        $payOrder['pay_order_id'] = Tool::toGUID();
        $payOrder['order_id_str'] = $str;
        $payOrder['create_time'] = time();
        $payOrder['price_sum'] = $sum_price;
        $payId = M('pay_order')->add($payOrder);


        $amountModel = new AmountLogic($post['goods_id'], $orderModel);


        $status = $amountModel->checkAmountDel();

        $this->promptParse($status, $amountModel->getError());



//        if ($conpouModel instanceof CouponModel) { // 更新优惠券使用信息
//            $status = BaseModel::getInstance(CouponListModel::class)->updateData($post['couponListId'], $insertId);
//        } else
            if (!empty($post['cart_id'])) {

                $cartModel = BaseModel::getInstance(GoodsCartModel::class);
                $status = $cartModel->delCart($post['cart_id']);
            } else {
//                $status = $goodsModel->commit();
                $status = $model->commit();;
            }
        $this->promptParse($status, '库存不足或其他原因，抢购没有成功，下次加油！');//创建失败

        $_SESSION['order_id'] = $insertId;
        $_SESSION['pay_order_id'] = $payId;
        $_SESSION['pay_order_id_sn'] = $payOrder['pay_order_id'];
        $_SESSION['order_id_str'] = $payOrder['order_id_str'];
        $_SESSION['total'] = $sum_price;

        if (!empty($post['prom_id'])) {
            $_SESSION['prom_id'] = $post['prom_id']['prom_id'];
            $_SESSION['type'] = $post['prom_id']['type'];
        }
        // 未选择新的发票类型插入最后一次的发票类型
        if ($post['invoice_id'] == null) {
            $this->redirect('PayOrder/InertnetWxpay');
        } else {
            $end_invoice_data = M('invoice')->where('id=' . $post['invoice_id'])->find();
            $end_order_id = M('order')->field('id,price_sum')
                ->where('user_id=' . $_SESSION['user_id'])
                ->order('id DESC')
                ->limit(1)
                ->find();
            $end_invoice_data['order_id'] = $end_order_id['id'];
            // 该价格有问题，确定之后再做修改
            $end_invoice_data['price'] = $end_order_id['price_sum'];
            $end_invoice_data['billing_date'] = time();
            array_shift($end_invoice_data);
            ($end_invoice_data);
            $addStatus = M('invoice')->add($end_invoice_data);
            if ($addStatus) {
                $this->redirect('PayOrder/InertnetWxpay');
            } else {
                return false;
            }
        }
    }

    /**
     * 数据验证
     */
    private function validate()
    {
        unset($_POST['id'], $_POST['c_id']);
        $muster = array_merge($this->validate, array(
            'goods_id',
            'formWhat'
        ));

        Tool::checkPost($_POST, array(
            'is_numeric' => $this->validate,
            'remarks',
            'couponListId'
        ), true, $this->validate) ? true : $this->error('操作失败');

        if ($_SESSION['bulidOrder'] !== $_POST['formWhat']) {
            $this->error('恶意攻击 将负法律责任');
        }

        if ($_POST['price_sum'] <= 0) {
            $this->error('数据错误');
        }
    }

    /**
     * 获取 代金券
     */
//    public function coupon()
//    {
//        $model = BaseModel::getInstance(CouponListModel::class);
//
//        $model->setCouponByUserId((int)$_SESSION['user_id']);
//
//        // 获取用户优惠券数据
//        $data = $model->getUserCouponByUserId();
//
//        // 是否有效
//        $couponModel = BaseModel::getInstance(CouponModel::class);
//
//        Tool::connect('parseString');
//
//        $data = $couponModel->validateCoupon($data, $model);
//
//        $this->data = $data;
//
//        $this->model = CouponModel::class;
//
//        $this->mCouponList = CouponListModel::class;
//        $this->display();
//    }
    /**
     * 获取 代金券
     */
    public function coupon()
    {
        $user_id = $_SESSION['user_id'];
        $goods =  $_SESSION['buyByCartGoods'];
        $goods_id = array_column($goods,'id');
        $sum = array_sum(array_map(function($val){return $val['num']*$val['price'];}, $goods)) ;
        if(!$user_id || !$goods_id || !$sum){
            $this->ajaxReturnData('',0,'参数错误');
            $this->couponData = [];
            $this->display();
            die;
        }
        $couponModel = BaseModel::getInstance(CouponModel::class);
        $data = $couponModel->usableCoupons($user_id,$goods_id,$sum);

        $this->model = CouponModel::class;
        $this->mCouponList = CouponListModel::class;
        $this->couponData = $data;
        $this->display();
    }

    /**
     * 编辑收货地址
     */
    public function editAddress()
    {
//        Tool::checkPost($_POST, array(
//            'is_numeric' => 'id'
//        ), true, array(
//            'id'
//        )) ?: $this->ajaxReturnData(null, 0, '操作失败');
        $id = I('post.id/d')? I('post.id/d') : $this->promptParse(null, '数据错误');

        $model = BaseModel::getInstance(UserAddressModel::class);

        $region = $model->find($id);

        $regionModel = BaseModel::getInstance(RegionModel::class);

        $region = $regionModel->getEditAddressData($region, $model);

        $this->region = UserAddressModel::class;

        $this->data = $region;

        $this->json = json_encode($region);
        $this->display();
    }

    /**
     * 购物车生成订单
     */
    public function cartSettlement()
    {
        // 获取购物车ID list
//        Tool::checkPost($_POST, array(), false, [
//            'cart_id'
//        ]) ? true : $this->promptParse(null, '数据错误');
        $id = I('post.cart_id')? I('post.cart_id') : $this->promptParse(null, '数据错误');

        $cartModel = BaseModel::getInstance(GoodsCartModel::class);

        $cartModel->setSelectColums([
            GoodsCartModel::$createTime_d,
            GoodsCartModel::$updateTime_d
        ]);

        $cartData = $cartModel->getCartDataByUserId($id, true);

        $this->promptParse($cartData, $cartModel->getError());

        foreach($cartData as &$vo){
            $g = M('goods')->where(['id'=>$vo['goods_id']])->field('id,price_member,freights_id,good_rebate,self_rebate,send_address')->find();
            //$vo['price_new'] = $g['price_member'];   //注释掉---meng
            $vo['good_rebate'] = $g['good_rebate'];
            $vo['self_rebate'] = $g['self_rebate'];
            $vo['send_address'] = $g['send_address'];

            //增加购物车购买商品缓存
            $goods[$vo['goods_id']]['id'] = $vo['goods_id'];
            //$goods[$vo['goods_id']]['price'] = $g['price_member'];   //注释掉---meng
            $goods[$vo['goods_id']]['price'] = get_level_price($_SESSION['user_id'],$vo['goods_id']);    //重新计算最新价格生成订单  ---meng
            $goods[$vo['goods_id']]['num'] = $vo['goods_num'];
            $goods[$vo['goods_id']]['freights_id'] = $g['freights_id'];
            $goods[$vo['goods_id']]['good_rebate'] = $g['good_rebate'];
            $goods[$vo['goods_id']]['self_rebate'] = $g['self_rebate'];
            $goods[$vo['goods_id']]['send_address'] = $g['send_address'];
            $goods[$vo['goods_id']]['weight'] = $g['weight'];

            $rebate += $g['self_rebate']*$vo['goods_num'];
            //下订单时生成的总价存session['buyByCartGoods']    ----meng
            $buyCartPrices += $vo['goods_num'] * get_level_price($_SESSION['user_id'],$vo['goods_id']);
        }
        $_SESSION['buyByCartGoods'] = $goods;
        $_SESSION['orderTotal'] = $buyCartPrices;  //订单总价   ----meng

        //获取发货仓
        $send_address = array_column($goods,'send_address');
        $sendAddrData = M('send_address')->field('id,stock_name')->where(['id'=>['IN',$send_address]])->select();

        $arrayObj = Tool::connect('ArrayChildren', $cartData);

        $this->arrayObj = $arrayObj;

        $cartData = $arrayObj->inTheSameState(GoodsCartModel::$buyType_d);

        $goodsModel = BaseModel::getInstance(GoodsModel::class);

        $goodsModel->setGoodsNumKey(GoodsCartModel::$goodsNum_d);
        $goodsModel->setPriceNewKey(GoodsCartModel::$priceNew_d);

        // 购物车处理
        //----------------------------------------------------------------------------

        Tool::connect('parseString');

        //  购物车处里
        $goodsData = $this->parseData(C('cart_type'), $cartData);

        //增加返利模块
        foreach($goodsData as $kg=>&$vg){
            $vg['good_rebate'] = M('goods')->where(['id'=>$vg['goods_id']])->getField('good_rebate');
        }

        //处理价格
        $goodsData = $goodsModel->parsePrice($goodsData);
        $arrayObj->setData($goodsData);
        //-----------------------------------------------------------------------------

        //分发给活动处理
//        showData($goodsData);
        $goodsData = $arrayObj->inTheSameState(GoodsModel::$status_d);
//        showData($goodsData,1);
        $goodsData = $this->parseData(C('activity_type_class'), $goodsData);

        $this->promptParse($goodsData, '商品活动错误');

        $arrayObj->setData($goodsData);

        $goodsData = $arrayObj->inTheSameState('poopStatus');
        // +--------------------------------------------------

        // 商品活动价格处理
        // 设置 收费处里对象
        $goodsData = $this->parseData(C('promotion_type'), $goodsData);

        $this->setKeyByOpreator(GoodsCartModel::$goodsId_d);

        $specItem = $this->parseGoodsData($goodsData); // 商品信息

        //支付类型及其地址
        $this->payAndAddress();

        $_SESSION['bulidOrder'] = sha1(md5(base64_encode('MyNameIsWq') . time())); // formId

        //判断用户等级,折扣
        $IntegralUseModel = new IntegralUseModel();
        $disconut = $IntegralUseModel->getDiscount($_SESSION['user_id']);

        // 统一键名
        $specItem = $this->unoipy($specItem, GoodsCartModel::$id_d);



        $uid = $_SESSION['user_id'];
        $check = mt_rand(0,1000000);
        $_SESSION['check'.$uid] =$check;
        $this->assign('check',$check); //防止表单多次提交

        $this->assign('cartModel', GoodsCartModel::class);

        $this->assign('activityType', C('activity_type'));

        $this->assign('activityModel', PromotionTypeModel::class);

        $this->assign('goods_html', self::POOP_GOODS_HTML);

        $this->assign('goodsImage', GoodsImagesModel::class);

        $this->assign('specModel', SpecGoodsPriceModel::class);

        $this->assign('goodsModel', GoodsModel::class);

        $this->assign('orderModel', OrderModel::class);

        $this->assign('goodsSpec', $specItem);

        $this->assign('rebate', $rebate);

        $this->assign('sendAddrData', $sendAddrData);

        $this->assign('numberTotal', $_SESSION['user_goods_number']);
        $this->assign('promGoods', PromGoodsModel::class);
        $this->assign('proModel', PromotionTypeModel::class);
        $this->assign('totalMonery', $_SESSION['user_goods_monery']);
        $this->assign('totalMoneryDiscount',round(max($_SESSION['user_goods_monery'] * $disconut,0.01),2));

        $this->display(self::DISPLAY_HTML);
    }

    /**
     * 验证是否可满足条件使用
     */
    public function validateCouponUse()
    {
        $validate = [
            'c_id',
            'totalMonery'
        ];

        Tool::checkPost($_POST, [
            'is_number' => $validate
        ], true, $validate) ?: $this->ajaxReturnData(null, 0, '操作失败');
        $model = BaseModel::getInstance(CouponModel::class);

        // 验证是否符合条件
        $isUse = $model->isUse($_POST['c_id'], $_POST['totalMonery']);

        $this->promptPjax($isUse, '未满足条件不能使用');
        $_SESSION['couponId'] = $_POST['c_id'];
        $this->updateClient($isUse, '验证');
    }

    /**
     * 获取发票类型
     */
    public function invoice()
    {
        $data = BaseModel::getInstance(InvoiceTypeModel::class)->getOpenInvoice('INVOICE_TYPE_CONTENTENT');

        $company = BaseModel::getInstance(InvoiceCompanyModel::class)->getOpenInvoice('INVOICE_COMPANY');
        // 弹出窗显示默认选择
        // 查找是否有选择发票抬头的默认值
        $check_invoice = M('invoice')->where(array(
            'user_id'        => $_SESSION['user_id'],
            'invoice_header' => array(
                'neq',
                '个人'
            ),
            'check_status'   => 1
        ))->find();
        $this->assign('check_invoice', $check_invoice);
        $this->assign('company', $company);
        $this->assign('type', InvoiceTypeModel::class);
        $this->assign('companyModel', InvoiceCompanyModel::class);
        $this->assign('invoiceType', $data);
        $this->display();
    }

    /**
     * 对商品进行处理
     * @param array $class 处理对象数组
     */
    private function parseData(array $class, $cartData)
    {
        Content::setActivityType($class); // 设置 购物车处里对象

        $activityObj = new Content(null, $cartData);

        $goodsData = $activityObj->parseForeachActivity();

        $this->arrayObj->setData($goodsData);

        $goodsData = $this->arrayObj->d3ToD2();

        return $goodsData;

    }

    /**
     * 添加发票
     */
    public function invoice_add()
    {
        if (IS_POST) {
            // 先去判断该抬头是否存在
            $where['invoice_header'] = $_POST['invoice_header'];
            $where['user_id'] = $_SESSION['user_id'];
            $where['check_status'] = 1;
            if (!M('invoice')->where($where)->select()) {
                $data['pay_taxes_code'] = (I('post.pay_taxes_code') == '') ? 1 : I('post.pay_taxes_code');
                $data['invoice_type'] = trim(I('post.type'));
                $data['invoice_header'] = trim(I('post.invoice_header'));
                // $check_header = trim(I('post.invoice_header'));
                $data['invoice_title'] = trim(I('post.content'));
                $data['mobile'] = I('post.mobile');
                $data['email'] = I('post.email');
                $data['type'] = 2;
                $data['user_id'] = $_SESSION['user_id'];
                $data['create_time'] = time();
                $data['check_status'] = 1;
                // $length = count($_POST['invoice_array']);
                // 更新之前的默认选择
                M('invoice')->where(array(
                    'user_id' => $_SESSION['user_id']
                ))->save(array(
                    'check_status' => 0
                ));
                $res = M('invoice')->add($data);
                if (!$res) {
                    $this->ajaxReturn(array(
                        'code' => 0
                    ));
                } else {
                    // 返回选择的发票数据
                    $data = M('Invoice')->where(array(
                        'user_id'      => $_SESSION['user_id'],
                        'check_status' => 1
                    ))->find();
                    // dump($data);exit;
                    $this->ajaxReturn(array(
                        'code'         => 1,
                        'invoice_data' => $data
                    ));
                }
            } else {
                $this->ajaxReturn(array(
                    'code' => 2
                ));
            }
        }
    }


    public function getGiftInfo()
    {

        $type = I('post.type');
        unset($_POST['type']);
        $gift = new CommodityGiftModel();
        $gift_info = $gift->getGiftList($type, I('post.'));
        //团单没赠送商品      ---meng
        if($_SESSION['group_id']){
            $this->ajaxReturnData( null);
        }
        //团单没赠送商品      ---meng
        $this->ajaxReturnData($gift_info);
    }

    public function insertGiftGoods($array)
    {
        $data_0 = $_SESSION['user_gift_0'];//满赠
        $data_1 = $_SESSION['user_gift_1'];
        //判断用户选的赠品是否正常
        if($data_0){

            $str = 0;
            foreach ($data_0 as $k => $v) {
                if ($_POST['gift_id'] == $v['id']) {
                    $str++;
                }
            }

            if($str != 1 ){
                return false;
            }
            //拼接满赠 赠品
            $_POST['goods_id'][$_POST['gift_id']] = [
                'goods_id' => $_POST['gift_id'],
                'goods_num'  => 1,
                'goods_price' => 0
            ];
            return true;
        }

        //拼接单品 赠品
        foreach($data_1 as $k  => $v){
            if(!empty($v)){
                $_POST['goods_id'][$v['id']] = [
                    'goods_id' => $v['id'],
                    'goods_num'  => 1,
                    'goods_price' => 0
                ];
            }
        }


    }

    //判断是否分单并进行处理
    public function isFendan($post){
        $_SESSION['pay_price_sum'] = $post['price_sum'];
        $goods_id = array_keys($post['goods_id']);
        $goods_id = implode($goods_id,',');
        $send_address = M('goods')->where(['id'=>['in',$goods_id]])->field('id,send_address')->order('send_address')->select();

        $buyByCartGoods = $_SESSION['buyByCartGoods'];

        $count = count($send_address);
        if($count == 1){
            $_POST['ware_id'] = $send_address[0]['send_address'];
            return 0;
        }else{
            //数据分组

            //优惠金额
            $conpouListModel = BaseModel::getInstance(CouponListModel::class);
            $c_gid = array_column($buyByCartGoods,'id');
            $c_num = array_sum(array_map(function($val){return $val['num']*$val['price'];}, $buyByCartGoods)) ;
            $useCoupon = $conpouListModel->getCouponAmount($post['couponListId'],$c_gid,$c_num);

            $ss = '';
            $goods_rebate = '';
            $self_rebate = '';
            $price = '';
            $freight = '';
            $couponMonery = 0;

            foreach($send_address as $k=>&$v){
                $data [ $v['send_address'] ]['goods_id'][ $v['id'] ] = $post['goods_id'][ $v['id'] ];

                $data [ $v['send_address'] ]['remarks'] = $post ['remarks'];
                $data [ $v['send_address'] ]['invoice_id'] = $post ['invoice_id'];
                $data [ $v['send_address'] ]['formWhat'] = $post ['formWhat'];
                $data [ $v['send_address'] ]['ware_id'] = $post ['ware_id'] ;
                $data [ $v['send_address'] ]['exp_id'] = $post ['exp_id'];
                $data [ $v['send_address'] ]['couponListId'] = $post['couponListId'];
                $data [ $v['send_address'] ]['pay_type'] = $post['pay_type'] ;
                $data [ $v['send_address'] ]['address_id'] = $post['address_id'];
                $data [ $v['send_address'] ]['freight_id'] = $post['freight_id'];
                $data [ $v['send_address'] ]['check'] = $post['check'];
                $data [ $v['send_address'] ]['ware_id'] = $v['send_address'];

                if($ss != $v['send_address'] && $ss !='' ){
                    unset($goods_rebate);
                    unset($self_rebate);
                    unset($price);
                    unset($freight);
                }
                $goods_rebate += $buyByCartGoods[ $v['id'] ]['good_rebate']*$buyByCartGoods[ $v['id'] ]['num'];
                $self_rebate += $buyByCartGoods[ $v['id'] ]['self_rebate']*$buyByCartGoods[ $v['id'] ]['num'];
                $price  += $buyByCartGoods[ $v['id'] ]['price']*$buyByCartGoods[ $v['id'] ]['num'];
                $freight += $buyByCartGoods[ $v['id'] ]['freiMonery'];

                //优惠金额
                if($useCoupon){
                    if($useCoupon['reduced_type'] == 1){
                        $couponMonery = $price/$c_num*$useCoupon['money'];
                    }elseif($useCoupon['reduced_type'] == 2){
                        $couponMonery = $price*(100-$useCoupon['discount'])*0.01;
                    }
                    $couponMonery = number_format($couponMonery,2);
                }else{
                    $couponMonery = 0;
                }

                $data [ $v['send_address'] ]['goods_rebate'] = $goods_rebate;
                $data [ $v['send_address'] ]['self_rebate'] = $self_rebate;
                $data [ $v['send_address'] ]['shipping_monery'] = $freight;
                if($couponMonery>$price){
                    $data [ $v['send_address'] ]['price_sum'] = $freight +0.01;
                }else{
                    $data [ $v['send_address'] ]['price_sum'] = $price + $freight - $couponMonery;
                }

                $data [ $v['send_address'] ]['coupon_amount'] = $couponMonery;

                $ss = $v['send_address'];
            }
            unset($v);

            return $data;
        }

    }

    public function idCard(){
        $user_id = $_SESSION['user_id'];
        $data = M('true_name')->where(['user_id'=>$user_id])->find();

        $this->assign('idCard',$data);

        $this->display();
    }





}