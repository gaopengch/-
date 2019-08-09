<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/23
 * Time: 17:32
 */
namespace Home\Model;

use Common\Model\BaseModel;

Class GoodsSoldtimeModel extends BaseModel{
    private static $obj;


	public static $id_d;	//

	public static $name_d;	//清仓时段名称

	public static $sort_d;	//排序

	public static $status_d;	//是否显示：1显示 0不显示

	public static $creatTime_d;	//创建时间

	public static $updateTime_d;	//更新时间

	public static $starttime_d;	//开始时间

	public static $endtime_d;	//结束时间


    public static function getInitnation()
    {
        $class = __CLASS__;
        return ! (self::$obj instanceof $class) ? self::$obj = new self() : self::$obj;
    }

    public function getsoldtime(){
        $data = $this->where('status = 1')->select();
        return $data;
    }

    public function menusoldtime(){
        $field = self::$id_d. ',' .self::$name_d. ',' .self::$sort_d. ',' .self::$starttime_d. ',' .self::$endtime_d;
        $data = $this->where("status = 1")->getField($field);
        return $data;
    }

    //获取时段,转成时间戳
    public function newGetSoldTime()
    {
        $soldname = M("GoodsSoldtime")->field('name,id,starttime')->where('status=1')->select();
        $time = date("Y-m-d");
        foreach($soldname as $k=>$v){
            $soldname[$k]['start'] = $soldname[$k]['starttime'];
            $starttime = str_replace('-',':',$v['starttime']);
            $str_time  = $time.$starttime ;
            $soldname[$k]['starttime'] = strtotime($str_time);
        }
        return $soldname;
    }

}