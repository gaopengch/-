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
use Org\Util\RandString;

/**
 * 代金卷模型 
 */
class CouponModel extends BaseModel
{
    private static  $obj;

	public static $id_d;

	public static $name_d;

	public static $type_d;

	public static $money_d;

	public static $condition_d;

	public static $createnum_d;

	public static $sendNum_d;

	public static $useNum_d;

	public static $sendStart_time_d;

	public static $sendEnd_time_d;

	public static $useStart_time_d;

	public static $useEnd_time_d;

	public static $addTime_d;

	public static $updateTime_d;

	public static $reducedType_d;	//优惠形式：1按具体金额money，2按折扣discount

	public static $discount_d;	//折扣比例：0-100

	public static $timeType_d;	//有效期类型：1、按具体日期区间，2、N天内有效

	public static $limitedTime_d;	//有效天数

	public static $limitedNumber_d;	//每人限领数目：0：不做限制，其他按数字

	public static $goodsClass_type_d;	//是否指定商品或分类：0通用不指定，1指定商品，2指定分类

	public static $goodsClass_d;	//指定的商品或分类id

	public static $instructons_d;	//优惠卷说明

    protected  $error;


    public static function getInitnation()
    {
        $name = __CLASS__;
        return static::$obj = !(static::$obj instanceof $name) ? new static() : static::$obj;
    }
    
    /**
     * 优惠券编号【线下发放的】 
     */
    public function getCouponByPoop ($idString, array $data, BaseModel $model)
    {
        if (!is_string($idString) || !$this->isEmpty($data) || !($model instanceof BaseModel)) {
            return $data;
        }
        
        $coupon = $this->where(self::$id_d .' in ('.$idString.')')->getField(self::$id_d.','.self::$name_d);
        
        if (empty($coupon)) {
            return $coupon;
        }
        
        foreach ($data as $key => &$value) {
            
            if (!array_key_exists($value[$model::$expression_d], $coupon)) {
                continue;
            }
            $value[$model::$expression_d] = $coupon[self::$id_d];
        }
        
        return $data;
    }


    /**
     * 根据用户ID获取优惠券关联列表
     */
    public function getCouponByUser($user_id, $field = '', $limit = '0,100')
    {
        if (empty($field)) {
            $field = 'l.`id`, c.`name`, c.`use_start_time`, c.`use_end_time`, c.`condition`, c.`money`';
        }
        $time  = time();
        $where = [
            'l.user_id'        => $user_id,
            'l.status'         => 0,
            'c.use_start_time' => ['lt', $time],
            'c.use_end_time'   => ['gt', $time]
        ];
        $list = $this->alias('c')->join('db_coupon_list AS l ON l.c_id=c.id')
            ->where($where)->field($field)->limit($limit)->select();
        return $list;
    }
    
    /**
     * 验证优惠券  
     */
    public function validateCoupon(array $data, BaseModel $model)
    {
       
        if (!$this->isEmpty($data) || !($model instanceof BaseModel)) {
            return array();
        }
        
        $parseArray = S('CouponMaster_HOME');
        
        if (empty($parseArray)) {
            
            $field = [
                self::$id_d,
                self::$condition_d,
                self::$money_d,
                self::$name_d,
                self::$useStart_time_d,
                self::$useEnd_time_d
            ];
            
            $dataArray = $this->getDataByOtherModel($data, $model::$cId_d, $field, self::$id_d);
           
            if (empty($dataArray)) {
                return array();
            }
            $curretTime =  time();
            
            $parseArray = array();
            
            foreach ($dataArray as $key => & $value) {
                if (empty($value)) {
                    continue;
                }
            
                if ($value[self::$sendStart_time_d] > $curretTime || $value[self::$useEnd_time_d] < $curretTime) {
                    $parseArray['alearldyUse'][$value[self::$id_d]] = $value;
                } else {
                    $parseArray['notUse'][$value[self::$id_d]] = $value;
                }
            }
            
            S('CouponMaster_HOME', $parseArray, 6);
        }
        
        return $parseArray;
    }
    
    /**
     * 是否可用
     * @param array $data 优惠券数组数据 
     */
    public function checkEffective (array $data)
    {
        if (!$this->isEmpty($data)) {
            return array();
        }
        
        $curretTime =  time();
        foreach ($data as $key => & $value) {
            
            if (empty($value)) {
                continue;
            }
            
            if ($value[self::$sendStart_time_d] > $curretTime || $value[self::$useEnd_time_d] < $curretTime) {
                $this->error = '未在规定的时间使用';
                $value['status'] = 0;//不可用
            }
        }
        
        return $data;
        
    }
    
    /**
     * 是否符合条件使用 
     */
    public function isUse($id, $monery) 
    {
       
        if (($id = intval($id)) === 0 || ($monery = floatval($monery)) === 0.0) {
            return false;
        }
      
        $data = $this->field(self::$condition_d.','.self::$money_d)->where(self::$id_d.'=%d', $id)->find();
        if (!empty($data[self::$condition_d]) && $data[self::$condition_d] <= $monery) {
            
            $_SESSION['own_my_coupon'] = $data[self::$money_d];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 获取某个字段值 
     */
    public function getDataByField($id, $field) 
    {
        $fieldString = implode(',', $this->getDbFields());
        if (($id = intval($id)) === 0 || false === strpos($fieldString, $field)) {
            return false;
        }
        return $condition = $this->where(self::$id_d.'=%d', $id)->getField($field);
    }
    //查询我的优惠券数量
    public function getCouponCountByUser(){
        $user_id = $_SESSION['user_id'];
        if (empty($user_id)) {
            return false;
        }
        $where['user_id'] = $user_id;
        $where['status']  = '0';
        $count = M('coupon_list')->where($where)->count();
        return $count;
    }
    //查询单张优惠券信息
    public function getCouponDetailsById($id){
        if (empty($id)) {
            return false;
        }
        $res = $this->field('id,money,condition')->where(['id'=>$id])->find();
        return $res;
    }


    /**
     * 获取一张优惠券,并判断是否有效
     * @param  integer $promo_id 优惠券id
     * @param  float  $total    现在条件
     * @return array
     */
    public function getCouponValidById($promo_id, $total = 0.00)
    {
        if (empty($promo_id)) {
            return false;
        }
        $where = [
            'l.id'     => $promo_id,
            'l.status' => 0
        ];
        $prom = M('coupon')->alias('c')->join('__COUPON_LIST__ as l on c.id=l.c_id')->where($where)
            ->field('c.condition, c.money, c.use_start_time, c.use_end_time')->find();
        if (!empty($prom) 
            && $prom['use_start_time'] < time() 
            && $prom['use_end_time'] > time() 
            && (empty($total) || $prom['conditon'] < $total))
        {
            return $prom;
        }
        return false;
    }

    
    /**
     * 买就送优惠券优惠 
     */
    public function getCouponData(array $data, BaseModel $model)
    {
        if (!$this->isEmpty($data) ||!($model instanceof  BaseModel)) {
            $this->error = '数据错误';
            return array();
        }
        
        $idString = null;
        foreach ($data as $key => $value) {
            if ($value[$model::$type_d] != -1) {
                continue;
            }
            $idString .= ','.addslashes($value[$model::$expression_d]);
        }
       
        $idString = substr($idString, 1);
        
        if (empty($idString)) {
            return $data;
        }
        
        $coupon = $this->where(self::$id_d.' in ('.$idString.')')->getField(self::$id_d.','.self::$name_d);
       
        if (empty($coupon)) {
            return $data;
        }
        
       foreach ($data as $key => & $value) {
           if ($value[$model::$type_d] != -1 || !array_key_exists($value[$model::$expression_d], $coupon)) {
               continue;
           }
           $value['promation_name'] .= '买就送代金券、' . $coupon[$value[$model::$expression_d]];
       }
       return $data;
    }
    
    /**
     * 使用优惠券
     * @param  integer $promo_id 优惠券id
     * @param  integer $order_id 订单id
     * @return boolean
     */
    public function used($promo_id, $order_id)
    {
        $data = [
            'id'       => $promo_id,
            'order_id' => $order_id,
            'use_time' => time(),
            'status'   => 1
        ];
        $ret = M('couponList')->save($data);
        return $ret > 0;
    }

    /**
     * 获取可领取优惠卷列表
     * @param $user_id      用户ID
     * @param $goods_id     商品ID
     */
    public function CouponsData($user_id='',$goods_id=''){
        $time = time();
        $data = $this->where("type = %d and send_start_time < %s and send_end_time >%s and createnum>send_num",array(1,$time,$time)
        )->select();

        if(empty($data)) {
            return 0;
        }

        //有商品id传来时判断优惠卷是否可用于该商品，=0通用不用判断
        if($goods_id){
            foreach($data as $k=>$v){
                if($v['goods_class_type'] == 1){
                    $gid = M('goods')->where( ['id'=>$goods_id] )->getField('p_id');
                    if($v['goods_class'] != $gid){
                        unset($data[$k]);
                    }
                }
                if($v['goods_class_type'] == 2){
                    $cid = M('goods')->where( ['id'=>$goods_id] )->getField('class_id');
                    $topcid = M('goods')->where( ['id'=>$cid] )->getField('class_id');
                    if($v['goods_class'] != $topcid){
                        unset($data[$k]);
                    }
                }
            }
        }

        $id = array_column($data,'id');

        $list = [];
        if($user_id){
            $list = M('coupon_list')->where(['user_id'=>$user_id,'c_id'=>['in',$id]])->group('c_id')->getField('c_id,count(c_id) as num');

        }
        foreach($data as &$v2){
            $have_num = $list[$v2['id']]?$list[$v2['id']]:0;
            $v2['have_num'] = $have_num;
            if($v2['limited_number']){
                $v2['have_status'] = $have_num<$v2['limited_number'] ? 1 : 0;
            }else{
                $v2['have_status'] = 1;
            }
            $v2['discount'] = $v2['discount']*0.1;
        }
        return $data;

    }

    /**
     * 领取优惠卷
     * @param $id 优惠卷id/db_coupon
     * @param $user_id  用户id
     * @return int  0领取失败，1领取成功
     */
    public function getCoupon($id,$user_id){
        $couponListModel = M('coupon_list');
        $time = time();
        $where = [
            'id' => $id,
            'type' => 1,
            'send_start_time' => ['lt',$time],
            'send_end_time'   => ['gt',$time],
        ];
        $coupons = M('coupon')->field('id,type,createnum,send_num,send_start_time,send_end_time,limited_number')->where($where)->find();
        if(!$coupons){
            return 0 ;
        }
        $find = $couponListModel->where(['c_id'=>$id,'user_id'=>$user_id])->select();
        $num = count($find);

        //有限制数量进行判断，无或0即为无限制
        if($coupons['limited_number']){
            if($num >= $coupons['limited_number']){
                return 2;
            }
        }

        $data = [];
        if($coupons['createnum']>$coupons['send_num']){
            $data['c_id'] = $coupons['id'];
            $data['type'] = $coupons['type'];
            $data['user_id'] = $user_id;
            $data['code'] = RandString::randString(9,0);
            $data['send_time'] = $time;
            $res = $couponListModel->add($data);
            if($res){
                $couponListModel->commit();
                M('coupon')->where('id='.$id)->setInc('send_num',1);
                $have_num = $num +1;

                if($coupons['limited_number']){
                    if ($have_num < $coupons['limited_number']) {
                        return ['have_num' => $have_num, 'have_status' => 1];
                    } else {
                        return ['have_num' => $have_num, 'have_status' => 0];
                    }
                }else{
                    return ['have_num' => $have_num, 'have_status' => 1];
                }
            }else{
                $couponListModel->rollback();
                return 0;
            }
        }else{
            return 0;
        }
    }
    /**
     * 订单页获取可用优惠卷
     * @param $user_id      用户id
     * @param $goods_id     商品id字符串
     * @param $sum          商品总价
     * @return mixed
     */
    public function usableCoupons($user_id,$goods_id,$sum){
        $data = $data = M('coupon_list a')
            ->join('db_coupon b on a.c_id=b.id')
            ->field('a.id,a.c_id,a.user_id,a.send_time,a.status,b.name,b.money,b.condition,b.type,b.use_start_time,b.use_end_time,b.reduced_type,b.discount,b.time_type,b.limited_time,b.goods_class_type,b.goods_class,b.instructons')
            ->where(['a.user_id'=>$user_id,'status'=>0])
            ->select();

        $now = time();
        $reduced_type1 = [];
        $reduced_type2 = [];
        foreach($data as $k=>&$v){
            //价格
            if($sum<$v['condition']){
//                unset($data[$k]);
                continue;
            }
            //判断时间
            if($v['time_type'] == 1){
                if($v['use_start_time']>$now || $v['use_end_time']<$now){
//                    unset($data[$k]);
                    continue;
                }
            }elseif($v['time_type'] == 2){
                $time = $v['limited_time']*86400 + $v['send_time'];
                if($now>$time){
//                    unset($data[$k]);
                    continue;
                }
            }

            //当前商品是否可用,==0通用不用判断
            if($v['goods_class_type'] == 1){
                //指定商品
                $gid = M('goods')->field('p_id')->where( ['id'=>['in',$goods_id]] )->select();
                $gid = array_column($gid,'p_id');
                if(!in_array($v['goods_class'],$gid)){
//                    unset($data[$k]);
                    continue;
                }
            }elseif($v['goods_class_type'] == 2){
                //指定分类
                $cid = M('goods')->field('class_id')->where( ['id'=>['in',$goods_id]] )->select();
                $cid = array_column($cid,'p_id');
                if($cid){
                    $topcid = M('goods')->field('class_id')->where( ['id'=>['in',$cid]] )->select();
                    $topcid = array_column($topcid,'p_id');
                    if(!in_array($v['goods_class'],$topcid)){
                        continue;
                    }
                }else{
                    continue;
                }

            }

            if($v['reduced_type'] == 1){
                $reduced_type1[] = $v;
            }elseif($v['reduced_type'] == 2){
                $reduced_type2[] = $v;
            }
        }
        $last_names = array_column($reduced_type1,'money');
        array_multisort($last_names,SORT_DESC,$reduced_type1);

        $result = array_merge($reduced_type1,$reduced_type2);
        foreach($result as &$v){
            $v['discount'] = $v['discount']*0.1;
            if($v['time_type'] == 2){
                $v['use_end_time'] = $v['send_time'] + 86400*$v['limited_time'];
            }
        }

        return $result;

    }

}