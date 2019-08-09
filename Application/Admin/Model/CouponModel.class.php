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

namespace Admin\Model;

use Common\Model\BaseModel;

/**
 * 代金卷模型 
 */
class CouponModel extends BaseModel
{
    private $poopKey; // 代金券相关操作的键

    private static $obj;

	public static $id_d;	//表id

	public static $name_d;	//优惠券名字

	public static $type_d;	//发放类型 0面额模板1 按用户发放 2 注册 3 邀请 4 线下发放

	public static $money_d;	//优惠券金额

	public static $condition_d;	//使用条件

	public static $createnum_d;	//发放数量

	public static $sendNum_d;	//已领取数量

	public static $useNum_d;	//已使用数量

	public static $sendStart_time_d;	//发放开始时间

	public static $sendEnd_time_d;	//发放结束时间

	public static $useStart_time_d;	//使用开始时间

	public static $useEnd_time_d;	//使用结束时间

	public static $addTime_d;	//添加时间

	public static $updateTime_d;	//更新时间

	public static $reducedType_d;	//优惠形式：1按具体金额money，2按折扣discount

	public static $discount_d;	//折扣比例：0-100

	public static $timeType_d;	//有效期类型：1、按具体日期区间，2、N天内有效

	public static $limitedTime_d;	//有效天数

	public static $limitedNumber_d;	//每人限领数目：0：不做限制，其他按数字


	public static $goodsClass_type_d;	//是否指定商品或分类：0通用不指定，1指定商品，2指定分类

	public static $goodsClass_d;	//指定的商品或分类id

	public static $instructons_d;	//优惠卷说明

    public static function getInitnation()
    {
        $name = __CLASS__;
        return static::$obj = !(static::$obj instanceof $name) ? new static() : static::$obj;
    }
    
    protected function _before_insert(& $data, $options)
    {
        $data[static::$updateTime_d] = time();
        $data[static::$addTime_d]    = time();
        $data[static::$sendNum_d]    = 0;
        $data[static::$useNum_d]     = 0;
        return $data;
    }
    
    protected function _before_update(& $data, $options)
    {
        $isExits = $this->editIsOtherExit(static::$name_d, $data[static::$name_d]);
        
        if ($isExits) {
            $this->rollback();
            $this->error = '已存在该名称：【'.$data[static::$name_d].'】';
            return false;
        }
        $data[static::$updateTime_d] = time();
    
        return $data;
    }
    
    /**
     * 是否可以发放代金券 
     * @param int $sendNum 发放数量
     * @param int $id      代金卷编号
     * @return bool
     */
    public function isSendCoupon($sendNum, $id)
    {
        if (!is_numeric($sendNum) || !is_numeric($id)) {
            $this->error = '参数错误';
            return false;
        }
        
        $obj = $this->where(static::$id_d.'= "%s"', $id);
        
        //获取数量
        $data = $obj->find(array(
                'field' => array(
                    static::$createnum_d,
                    static::$sendStart_time_d,
                    static::$sendEnd_time_d,
                    static::$sendNum_d
                )
        ));
        
        if (empty($data)) {
            $this->error = '代金卷不存在';
            return false;
        }
        
        if ($data[static::$createnum_d] <= $data[static::$sendNum_d] || $data[static::$createnum_d] <= 0) {
            $this->error = '发放数量不足';
            return false;
        }
        
        // 是否在发放时间内
        $time = time();
        
        if ($time < $data[static::$sendStart_time_d] || $time > $data[static::$sendEnd_time_d]) {
            $this->error = '发放时间不允许';
            return false;
        }
        //记录发放数量
        
        $status = $this->where(static::$id_d.'= "%s"', $id)->setInc(static::$sendNum_d, $sendNum);
        
        return $status;
        
    }
    
    
    /**
     * 获取错误 
     */
    public function getCouponError() {
        return $this->error;
    }
    
    /**
     * 添加数据 
     */
    public function addData($data, $flag = false)
    {
        if (empty($data)) {
            return $data;
        }
        
        $data[static::$sendStart_time_d] = strtotime($data[static::$sendStart_time_d]);
        
        $data[static::$sendEnd_time_d]   = strtotime($data[static::$sendEnd_time_d]);
       
        $data[static::$useEnd_time_d]    = strtotime($data[static::$useEnd_time_d]);
        
        $data[static::$useStart_time_d]  = strtotime($data[static::$useStart_time_d]);
        
        if ($data[static::$useStart_time_d]> $data[static::$useEnd_time_d]) {
            return false;
        }

        if ($data[static::$sendStart_time_d]> $data[static::$sendEnd_time_d]) {
            return false;
        }

        return !$flag ? $this->add($data) : $this->save($data);
    }
    
    /**
     * 获取优惠额度 
     */
    public function getCouponById($id)
    {
        if (($id = intval($id)) === 0) {
            return 0;
        }
        
        $data = $this->where(static::$id_d.'='.$id)->getField(static::$money_d);
        return $data;
    }
    
    /**
     * 获取代金券优惠数据 
     */
    public function getCouponData (array $data, $splitKey)
    {
        if (!$this->isEmpty($data) || empty($this->poopKey)) {
            return $data;
        }
       
        $temp = $flag = array();
       
        $temp = $data;
        foreach ($data as $key => &$value)
        {
            if ( $value[$splitKey] == -1) {//-1 表示代金券
                $value[$this->poopKey] = (int)$value[$this->poopKey];//代金券编号取整
                continue;
            } else {
                $flag[$key] = $value;
            }
            unset($data[$key]);
        }
      
        if (empty($data)) {
            return $temp;
        }
        
        $receiveData = $this->getDataByOtherModel($data, $this->poopKey, [
            static::$id_d,
            static::$name_d,
        ], static::$id_d);
        return array_merge($receiveData, $flag);
    }
    
    /**
     * @return the $poopKey
     */
    public function getPoopKey()
    {
        return $this->poopKey;
    }
    
    /**
     * @param field_type $poopKey
     */
    public function setPoopKey($poopKey)
    {
        $this->poopKey = $poopKey;
    }
}