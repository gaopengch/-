<?php
// +----------------------------------------------------------------------
// | OnlineRetailers [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2003-2023 www.yisu.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 亿速网络（http://www.yisu.cn）
// +----------------------------------------------------------------------
// | Author: 王强 <opjklu@126.com>\n
// +----------------------------------------------------------------------

namespace Home\Model;

use Think\Page;
use Common\TraitClass\callBackClass;
use Common\Tool\Tool;
use Common\Model\BaseModel;
use Common\TraitClass\ParsePromotionTrait;

/**
 * @desc 团购模型 
 * @author 王强
 * @version 1.0.1
 */
class GroupModel extends BaseModel
{
    private static $obj;

	public static $id_d;	//团购ID

	public static $title_d;	//活动名称

	public static $startTime_d;	//开始时间

	public static $endTime_d;	//结束时间

	public static $goodsId_d;	//商品ID

	public static $price_d;	//团购价格

	public static $goodsNum_d;	//商品参团数

	public static $buyNum_d;	//商品已购买数

	public static $orderNum_d;	//已下单人数

	public static $virtualNum_d;	//虚拟购买数

	public static $description_d;	//本团介绍

	public static $recommended_d;	//是否推荐 0.未推荐 1.已推荐

	public static $lookNum_d;	//查看次数

	public static $updateTime_d;	//更新时间

	public static $createTime_d;	//添加时间



	public static $groupPerson_num_d;	//拼团人数 至少2人成团

    
    public static function getInitnation()
    {
        $name = __CLASS__;
        return static::$obj = !(static::$obj instanceof $name) ? new static() : static::$obj;
    }
    
    /**
     * 重写父类方法
     */
    protected  function _before_insert(& $data, $options)
    {
        $data[static::$createTime_d] = time();
        $data[static::$updateTime_d] = time();
    
        return $data;
    }
     
    /**
     * 重写父类方法
     */
    protected function _before_update(& $data, $options)
    {
        $isExits = $this->editIsOtherExit(static::$title_d, $data[static::$title_d]);
        
        if ($isExits) {
            $this->rollback();
            $this->error = '已存在该名称：【'.$data[static::$title_d].'】';
            return false;
        }
        $data[static::$updateTime_d] = time();
    
        return $data;
    }
    
    /**
     * 添加促销商品
     * @param array $data post 数据
     * @param string $fun 方法名
     * @return boolean
     */
    public function addProGoods(array $data, $fun = 'add')
    {
        if (empty($data) || !is_array($data) || !method_exists($this, $fun)) {
            return false;
        }
         
        if ( $data[static::$startTime_d] > $data[static::$endTime_d]) {
            $this->error = '开始时间不能大于结束时间';
            return false;
        }
        $data[static::$startTime_d] = strtotime($data[static::$startTime_d]);
        $data[static::$endTime_d]   = strtotime($data[static::$endTime_d]);
        return $this->$fun($data);
    }



    /**
     * 获取团购列表
     */
    public function getGroupLists()
    {
        $groupList = $this->select();
        return $groupList;
    }
    /**
     * 获取团购商品详情      商品的图片   商品的详情
     *
     */
    public function getDetail($id)
    {
        $groupDetail = $this->where([self::$id_d => $id])->find();
        $goodsD = M('goods_detail')->where(['goods_id'=>$groupDetail['goods_id']])->find();
        $groupDetail['detail'] = $goodsD['detail'];
        return $groupDetail;
    }
    /**
     * 根据订单ID获取团购商品数据
     *
     */
    public function getGroupGoods($orderId)
    {
        $GorupOrder = M('order')->where(['id'=>$orderId])->find();
        $field = "p.id,p.title,p.price,p.title,p.goods_id,s.pic_url";
        $data = $this
            ->alias('p')
            ->join("db_goods_images s on p.goods_id=s.goods_id")
            ->where("p.id=".$GorupOrder['group_id'])
            ->field($field)
            ->find();
        $data1['images'] = $data['pic_url'];
        $data1['price_sum'] = $data['price'];
        $data1['title'] = $data['title'];
        $data1['goods_id'] = $data['goods_id'];
        $data1['group_id'] = $data['id'];
        $data1['id'] = $orderId;
        return $data1;

    }
    /**
     * 能否具有开团 参团资格
     *  $groupId 团活动id
     *  $orderId 参团订单id
     */
    public function isGroupBuy($groupId,$orderId = null)
    {
//        if(empty($orderId)){                  //参团情况
//
//
//        }
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




}