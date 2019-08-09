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

use Common\Model\BaseModel;
use Home\Model\IntegralUseModel;

class UserGradeModel extends BaseModel
{
    private static $obj;
	public static $id_d;	//通user表member_status字段

	public static $gradeName_d;	//返利会员等级名称

	public static $discountRate_d;	//折扣比例

	public static $rebateRatio_d;	//返利比例

	public static $status_d;	//等级状态：1启用，2不启用

	public static $description_d;	//返利等级描述


    public static function getInitnation()
    {
        $class = __CLASS__;
        return  static::$obj= !(static::$obj instanceof $class) ? new static() : static::$obj;
    }
    
    protected function _before_insert(& $data, $options)
    {
        $data[static::$status_d] = 1;
        
        return $data;
    }

    //由用户表用户状态获取等级名称
    public function getRebate($num){
        $name = $this->where(['id'=>$num])->getField('grade_name');
        return $name;
    }

    //获取折扣比例
    public function getDiscount($uid){
        $grade_id = M('user')->where(['id'=>$uid])->getField('member_status');
        $discount = $this->where(['id'=>$grade_id])->getField('discount_rate');
        return $discount;
    }

    //获取返利比例
    public function getRebateRatio($uid){
        $grade_id = M('user')->where(['id'=>$uid])->getField('member_status');
        $rebate_ratio = $this->where(['id'=>$grade_id])->getField('rebate_ratio');
        return $rebate_ratio;
    }



}