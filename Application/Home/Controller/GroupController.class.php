<?php

namespace Home\Controller;

use Common\Model\BaseModel;
use Home\Model\GroupModel;
use Home\Model\OrderModel;
use Home\Model\GoodsImagesModel;
use Think\Controller;
use Common\TraitClass\FrontGoodsTrait;
use Common\Model\ExpressModel;


/**
 * 团购控制器
 */
class GroupController extends BaseController
{
    use FrontGoodsTrait;

    public function __construct()
    {
        parent::__construct();
        $this->getNavTitle();
    }
    /**
     * 团购商品列表
     */
    public function index()
    {
        $this->display();
    }
    /**
     * 获取团购商品分页      待解决问题  商品图片
     */
    public function getList(){
        $search=I("post.search",'');
        if($search){
            $where=" title like '%{$search}%'";
        }else{
            $where="1=1";
        }

        //1团购活动时间已结束的不显示   2商品库存不足的不显示
        $timeWhere= " and end_time>unix_timestamp(now()) ";
        $goodsNumWhere= " and goods_num>=0 ";
        $filed="p.id,p.title,p.end_time,p.price,s.pic_url";
        $get_p=(I("get.p",1)-1)*10;
        $groupList=M()->query("SELECT ".$filed." FROM `db_group` p LEFT JOIN `db_goods_images` s ON p.goods_id=s.goods_id WHERE {$where}".$timeWhere.$goodsNumWhere." LIMIT {$get_p},10");
        $num=M()->query("SELECT count(*) as num FROM `db_group` WHERE {$where}".$timeWhere.$goodsNumWhere);
        $count=$num[0]['num'];
        $this->assign('count',$count);
        $this->assign('groupList',$groupList);
        $Page= new \Think\Page($count,10);
        $show= $Page->show();
        $this->assign('page',$show);
        $this->display();
    }

    /**
     *  团购商品详情页   ---meng 前端页面应显示里活动结束还有多少时间
     */
    public function detail()
    {
        $groupId = I("post.id",'');
        if(empty($groupId)){
            $this->error('系统防御开启', U('Index/index'));
        }
        //点击量
        $obj  = new GroupModel();
        $res = $obj->where(['id'=>$groupId])->setInc ('look_num',1);
        //团活动详情
        $groupGoods = $obj->getDetail($groupId);
        //团活动图片
        $goodsImg = M('goods_images')->where(['goods_id'=>$groupGoods['goods_id']])->select();
        //已成团的团单
        $obj1  = new OrderModel();
        $groupOrderData = $obj1->where(['group_id'=>$groupId,'pid'=>0,'group_status'=>2])->select();  //团单pid=0,团状态在2拼团中的
        //算出团单还差几人
        foreach ($groupOrderData as $k => $v){
            $groupOrderData[$k]['lack_person']= $groupGoods['group_person_num'] - $v['group_person_num'];
        }

        //添加formid     ---衔接buyNow
        unset($_SESSION['formId']);
        $_SESSION['formId'] = sha1(md5('Groupgoods') . time());



        $this->assign('goodsImg',$goodsImg);
        $this->assign('groupOrderData',$groupOrderData);
        $this->assign('groupGoods',$groupGoods);
        $this->display();
    }
    /**
     *  开团
     */
    public function startGroup()
    {
        if(empty($_SESSION['user_id'])){
//            if (IS_AJAX) {
//                $url = U('Public/login');
//                $this->ajaxReturnData(['url' =>$url], 0, '请先登录!');
//            }
            $this->redirect('Public/login');
        }

        //判断该用户是否下过此团购订单
        $uid = $_SESSION['user_id'];
        $groupId = I("get.id",'');
        $obj  = new OrderModel();
        $res = $obj->where(['group_id'=>$groupId,'user_id'=>$uid])->find();
        if(!empty($res) && $res['group_status'] != 4){
            echo "<script> alert('您已参加了该活动');location.href='/index.php/Home/Group/index';</script>";
            exit;
//                $this->redirect("Group/index");
        }else{
            //未参加过此团购的，生成订单，显示订单详情
            $obj1  = new GroupModel();
            $groupDetail = $obj1->getDetail($groupId);

            $obj2  = new OrderModel();
            $orderData1 = $obj2->createOrderSn();    //生成订单唯一标识
            $orderData2  = [
                'price_sum'      => $groupDetail['price'], // 总价
                'create_time'    => time(),
                'status'         => 0,  // 订单正常
                'translate'      => 0,  // 0,不需要发票   1,需要发票
                'group_id'      => $groupDetail['id'],  // 团活动id
                'group_status'      => 1  //    1,待成团  支付后修改状态为2
//                    'is_host'      => 1,  // 0,不是团长   1,团长
//                    'address_id'     => $address_id, // 收货地址    --点立即支付时 修改地址时再添加备注
//                    'order_status'   => 0,  // 未支付
//                    'comment_status' => 0,  // 未评论
//                    'pay_type'       => 0, // 支付类型
//                    'remarks'        => $message, // 订单备注      --点立即支付时 修改地址时再添加备注
//                    'group_person_num'        => , // 已拼单人数      --点立即支付时 修改地址时再添加备注

            ];
            //减库存 下订单        开团： 团购活动表中先减库存，支付成功：订单表中团购人数+1， 支付失败：团购库存+1  该团失败
            if($groupDetail['goods_num'] >= $groupDetail['group_person_num']){        //库存大等于参团数 才能开团
                $trans = M();
                $trans->startTrans();
                $groupRes = M('group')->lock(true)->where(['id'=>$groupDetail['id']])->find();//加锁查询
                if($groupRes){
                    $groupSql = "UPDATE `db_group` SET goods_num=goods_num-1 WHERE id=".$groupDetail['id'];
                    $res = M()->execute($groupSql);
                    if($res){
                        $trans->commit();
                    }else{
                        $trans->rollback();
                        echo "<script> alert('库存不足，请选购其他商品');location.href='/index.php/Home/Group/index';</script>";
                        exit;
                    }
                }
                //下订单
                $orderId = M('order')->add(array_merge($orderData1,$orderData2));
                if($orderId){
                    //跳转到支付结算页   订单信息存session
                    $orderData2['title'] = $groupDetail['title'];
                    $orderData2['goods_id'] = $groupDetail['goods_id'];
                    $orderData2['id'] = $orderId;
                    $_SESSION['orderData'] = array_merge($orderData1,$orderData2);
                    $this->redirect('Group/confirmGroup');     //或者ajax返回前台数据，前台请求结算页面   ---meng
                }
            }else{
                echo "<script> alert('库存不足，请选购其他商品');location.href='/index.php/Home/Group/index';</script>";
                exit;
            }
        }


    }
    /**
     * 参团   减库存下订单
     * 团购活动表中先库存-1，主订单团购人数+1(先占名额,再支付)
     * 支付成功：订单表中 1修改支付状态，
     *                    2修改主团单拼团成功状态 支付失败：团购库存+1
     */
    public function joinGroup()
    {
        if(empty($_SESSION['user_id'])){
//            if (IS_AJAX) {
//                $url = U('Public/login');
//                $this->ajaxReturnData(['url' =>$url], 0, '请先登录!');
//            }
            $this->redirect('Public/login');
        }
        $groupId = I("get.id",'')?I("get.id",''):I("post.id",'');
        $orderId = I("get.r",'')?I("get.r",''):I("post.r",'');
        //由订单id 团活动id 查询出主团单
        $obj  = new OrderModel();
        //团单状态2:拼团中  pid为0主团单
        $hostOrderData = $obj->where(['group_id'=>$groupId,'id'=>$orderId,'group_status'=>2,'pid'=>0])->find();
        if(empty($hostOrderData)){
            echo "<script> alert('信息错误');location.href='/index.php/Home/Group/index';</script>";
            exit;
        }
        //判断库存 判断团单名额
        $groupObj  = new GroupModel();
        $groupGoods = $groupObj->where(['id'=>$groupId])->find();
        if($groupGoods['goods_num'] <= 0){
            echo "<script> alert('库存不足，请选购其他商品');location.href='/index.php/Home/Group/index';</script>";
            exit;
        }
        $personNum = (int)$groupGoods['group_person_num'] - (int)$hostOrderData['group_person_num'];
        if($personNum <= 0){
            echo "<script> alert('此团人数已满');location.href='/index.php/Home/Group/index';</script>";
            exit;
        }

        //下订单     ---meng   加锁执行事务
        $trans = M();
        $trans->startTrans();

        $groupdata = $groupObj->lock(true)->where(['id'=>$groupId])->find();//加锁查询

        if($groupdata){
            //修改订单表 主团单 拼团人数
            $orderSql = "UPDATE `db_order` SET group_person_num=group_person_num+1 WHERE id=".$orderId;
            $res = $trans->execute($orderSql);
            //减少团活动表中库存
            $groupSql = "UPDATE `db_group` SET goods_num=goods_num-1 WHERE id=".$groupId;
            $res1 = $trans->execute($groupSql);
            //生成订单
            $newOrderId = $obj->addGroupOrder($orderId, $groupId, $groupGoods['price'],$hostOrderData['group_person_num']+1);

            if($res['group_person_num'] > $groupGoods['group_person_num']  &&  empty($res1)  &&  empty($newOrderId)){
                $trans->rollback();
                echo "<script> alert('此团人数已满');location.href='/index.php/Home/Group/index';</script>";
                exit;
            }else{
                $trans->commit();
            }
        }
        //跳转支付结算页
        $this->redirect('Group/confirmGroup');    //或者ajax返回前台数据，前台请求结算页面    ---meng

    }


    /**
     * 确认结算页面   显示填写地址，核对信息，  要重新建个结算页   ---meng
     */
    public function confirmGroup()
    {
        if(empty($_SESSION['user_id'])){
            $this->redirect('Public/login');
        }
        // +----------------------------  订单商品信息    两种情况 1开团 2参团      主团订单id    单价 数量 名称
        //两种session数据订单数据不一样
        if(!empty($_SESSION['orderData'])){
            $groupOrderGoods = $_SESSION['orderData'];

            //商品图片信息
            $goods['goods_id'] = $_SESSION['orderData']['goods_id'];
            $goodsImgObj = new GoodsImagesModel();
            $goodsImg = $goodsImgObj -> getGoodsImageByGroup($goods);
            $groupOrderGoods['images'] = $goodsImg['images'];

            $_SESSION['orderId'] = $_SESSION['orderData']['id'];     //支付时根据订单id判断
            unset($_SESSION['orderData']);
        }elseif(!empty($_SESSION['orderDataJoin'])){
            $GroupObj  = new GroupModel();
            $groupOrderGoods = $GroupObj->getGroupGoods($_SESSION['orderDataJoin']['id']);
            $groupOrderGoods['title'] = $_SESSION['orderDataJoin']['title'];
        }else{
            echo "<script> alert('信息错误');location.href='/index.php/Home/Group/index';</script>";
            exit;
        }
        // +---------------------------- 确认订单信息数据
        $this->assign('goodsSpec', $groupOrderGoods);

        // +----------------------------  获取运费
        showData($groupOrderGoods); die;

        // +----------------------------  收货地址地址及其支付信息
        $this->payAndAddress();
        // +----------------------------  配送物流显示
        $expressModel = BaseModel::getInstance(ExpressModel::class);

        $expressData = $expressModel->getDefaultOpen(false);


        //多次提交验证
        $uid = $_SESSION['user_id'];
        $check = mt_rand(0,1000000);
        $_SESSION['check'.$uid] =$check;

        //获取发货仓
//        $send_address = array_column($goods,'send_address');
//        $sendAddrData = M('send_address')->field('id,stock_name')->where(['id'=>['IN',$send_address]])->select();
//        $this->assign('sendAddrData',$sendAddrData); //发货仓


        $this->assign('expressData', $expressData);

        $this->assign('expressModel', ExpressModel::class);


        $this->display();
    }

    /**
     * 防止表单多次提交   ajax提交认证
     */
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


}
