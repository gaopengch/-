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

namespace Home\Model;
use Common\Model\BaseModel;
/**
 * 优惠券列表 model 
 */
class CouponListModel extends BaseModel
{
    private static $obj;
    
    private $couponByUserId = 0;
    
	public static $id_d;

	public static $cId_d;

	public static $type_d;

	public static $userId_d;

	public static $orderId_d;

	public static $useTime_d;

	public static $code_d;

	public static $sendTime_d;
    
	public static $status_d;

	const ALREADY = 1; //已使用
	
	const NOTUSE  = 0; // 未使用

	public static $amount_d;	//优惠金额

    public static function getInitnation()
    {
        $name = __CLASS__;
        return self::$obj = !(self::$obj instanceof $name) ? new self() : self::$obj;
    }
    
    /**
     * 获取 个人代金券 
     */
    public function getUserCouponByUserId () 
    {
       if ($this->CouponByUserId === 0) {
           return array();
       }
       
       $data = S('couponData_Home');
       if (empty($data)) {
           $data = $this
                ->where(self::$userId_d.'="%s" and '.self::$useTime_d.' = 0 and '.self::$status_d.' = '.self::NOTUSE, $this->couponByUserId)
                ->order()
                ->limit(16)
                ->select();
       } else {
           return $data;
       }
       
       //区分 使用 和 未使用
       if (empty($data)) {
           return array();
       }
       S('couponData_Home', $data, 6);
       return $data;
    }
    
    /**
     * 使用数据 赋值
     */
    public function updateData($id, $insertId)
    {
        if (($id = intval($id)) === 0 || ($monery = intval($insertId)) === 0) {
            !$this->isOpenTranstion ? :  $this->rollback();
            return false;
        }
        
        $saveData = [
            self::$orderId_d => $insertId,
            self::$useTime_d => time(),
            self::$status_d  => self::ALREADY
        ];
        
        $status = $this->where(self::$id_d.'=%d', $id)->save($saveData);
        
        if ($status === false) {
            $this->rollback();
            return false;
        }
        
        $this->commit();
        
        return true;
    }
    /**
     * @return the $CouponByUserId
     */
    public function getCouponByUserId()
    {
        return $this->couponByUserId;
    }
    
    /**
     * @param number $CouponByUserId
     */
    public function setCouponByUserId($couponByUserId)
    {
        if (!is_int($couponByUserId)) {
            throw new \Exception('数据类型不正确');
        }
        $this->couponByUserId = $couponByUserId;
    }
    //查询用户的可用优惠券
    public function getUsableCouponByUserId($user_id,$limit){
        $user_id = $_SESSION['user_id'];
        if(empty($user_id)) {   
            return false; 
        }
        $where['user_id'] = $user_id;
        $where['use_time'] = '';
        $where['use_end_time'] = array('GT',time());
        $res = M('coupon_list as a')->field('a.id,a.c_id,a.code,b.name,b.money,b.condition,b.use_start_time,b.use_end_time')->join('left join db_coupon as b on a.c_id=b.id')->where($where)->limit($limit)->select();
        $count =  M('coupon_list as a')->join('left join db_coupon as b on a.c_id=b.id')->where($where)->count();
        return array('res'=>$res,'count'=>$count);
    }
    // // //查询已用优惠券
    public function getUsedCouponByUserId($user_id){
        $user_id = $_SESSION['user_id'];
        if(empty($user_id)) {   
            return false;
        }
        $where['user_id'] = $user_id;
        $where['use_time'] = array('neq',0);
        $_GET['p'] = empty($_GET['p'])?0:$_GET['p'];
        $res = M('coupon_list as a')->field('a.id,a.c_id,a.code,b.name,b.money,b.condition,b.use_start_time,b.use_end_time')->join('left join db_coupon as b on a.c_id=b.id')->where($where)->select();
        $count =  M('coupon_list as a')->join('left join db_coupon as b on a.c_id=b.id')->where($where)->count();
        return array('res'=>$res,'count'=>$count);
    }
    // //查询过期优惠券
    public function getOverdueCouponByUserId($user_id){
        $user_id = $_SESSION['user_id'];
        if(empty($user_id)) {   
            return false;
        }
        $where['user_id'] = $user_id;
        $where['use_time'] = '';
        $where['use_end_time'] = array('LT',time());
        $res = M('coupon_list as a')->field('a.id,a.c_id,a.code,b.name,b.money,b.condition,b.use_start_time,b.use_end_time')->join('left join db_coupon as b on a.c_id=b.id')->where($where)->select();
        $count =  M('coupon_list as a')->join('left join db_coupon as b on a.c_id=b.id')->where($where)->count();
        return array('res'=>$res,'count'=>$count);
    }
    //根据订单产看用户用过的优惠券
    public function getCouponByOrderId($order_id){
        $user_id = $_SESSION['user_id'];
        if(empty($user_id)) {   
            return false;
        }
        if (empty($order_id)) {
            return false;
        }
        $where['user_id'] = $user_id;
        $where['order_id'] = $order_id;
        $field = 'c_id,use_time';
        $res  = $this->field($field)->where($where)->find();
        if (!$res) {
            return '';
        }
        return $res;
    }

    /**
     * 获取优惠卷信息2（通过用户优惠卷列表id获取）
     * @param $id  优惠卷列表id/db_coupon_list
     * @return mixed  数据结果
     */
    public function getCouponsInfo2($id){
        $data = M('coupon_list a')
            ->join('db_coupon b on a.c_id=b.id')
            ->field('a.id,a.c_id,a.user_id,a.send_time,b.name,b.money,b.condition,b.type,b.use_start_time,b.use_end_time,b.reduced_type,b.discount,b.time_type,b.limited_time,b.goods_class_type,b.goods_class,b.instructons')
            ->where(['a.id'=>$id,'a.status'=>0])
            ->find();
        return $data;
    }
    /**
     * 订单判断优惠卷是否可用
     * @param $id        优惠卷列表id/db_coupon_list
     * @param $goods_id  商品id/字符串、数组
     * @param $money     订单商品总价
     * @return int
     */
    public function getCouponAmount($id,$goods_id,$money){
        $data = $this->getCouponsInfo2($id);
        $now = time();
        if(empty($data)){
            return false;
        }
        //价格
        if($money<$data['condition']){
            return false ;
        }
        //判断时间
        if($data['time_type'] == 1){
            if($data['use_start_time']>$now || $data['use_end_time']<$now){
                return false;
            }
        }elseif($data['time_type'] == 2){
            $time = $data['limited_time']*86400 + $data['send_time'];
            if($now>$time){
                return false;
            }
        }
        //当前商品是否可用,=0通用不用判断
        if($data['goods_class_type'] == 1){
            //指定商品
            $gid = M('goods')->field('p_id')->where( ['id'=>['in',$goods_id]] )->select();
            $gid = array_column($gid,'p_id');
            if(!in_array($data['goods_class'],$gid)){
                return false ;
            }
        }elseif($data['goods_class_type'] == 2){
            //指定分类
            $cid = M('goods')->field('class_id')->where( ['id'=>['in',$goods_id]] )->select();
            $cid = array_column($cid,'p_id');
            $topcid = M('goods')->field('class_id')->where( ['id'=>['in',$cid]] )->select();
            $topcid = array_column($topcid,'p_id');
            if(!in_array($data['goods_class'],$topcid)){
                return false ;
            }
        }
        $res = [
            'id'  => $id,
            'reduced_type'  => $data['reduced_type'],
            'money'  => $data['money'],
            'discount'  => $data['discount'],
        ];

        return $res;
    }
    /**
     * 获取用户优惠卷列表
     * @param $user_id
     * @return mixed
     */
    public function UserCoupons($limit){
        $user_id = $_SESSION['user_id'];
        $data = $data = M('coupon_list a')
            ->join('db_coupon b on a.c_id=b.id')
            ->field('a.id,a.c_id,a.user_id,a.send_time,a.status,b.name,b.money,b.condition,b.type,b.use_start_time,b.use_end_time,b.reduced_type,b.discount,b.time_type,b.limited_time,b.goods_class_type,b.goods_class,b.instructons')
            ->where(['a.user_id'=>$user_id,'status'=>0])
            ->select();
        $now = time();
        foreach($data as &$v){
            if($v['time_type'] == 1){
                if($v['use_start_time']>$now || $v['use_end_time']<$now){
                    unset($v);
                }
            }elseif($v['time_type'] == 2){
                $time = $v['limited_time']*86400 + $v['send_time'];
                $v['use_end_time'] = $v['limited_time']*86400 + $v['send_time'];
                $v['use_start_time'] = $v['send_time'];
                if($now>$time){
                    unset($v);
                }
            }
            if($v['goods_class_type'] == 1){
                $goods_id = M('goods')->field('id')->where(['p_id'=>$v['goods_class']])->find();
                $v['goods_id'] = $goods_id['id'];
            }
            $v['discount'] = $v['discount']*0.1;
            $v['money'] = floatval($v['money']);
        }
        $data = array_slice($data,0,5);

        return $data;
    }
}