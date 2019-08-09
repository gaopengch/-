<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/22
 * Time: 17:48
 */
namespace Admin\Model;

use Common\Model\BaseModel;
use Think\Model;

class GoodsSoldtimeModel extends BaseModel
{
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
        $name = __CLASS__;
        return static::$obj = ! (static::$obj instanceof $name) ? new static() : static::$obj;
    }


    protected $patchValidate = true;
    protected $_validate = [
        ['nav_titile','require','导航菜单名称不能为空'],
        ['sort','number','排序只能是数字'],
    ];


    /**
     * 添加前操作
     */
    protected function _before_insert(&$data,$options)
    {
        $data['create_time'] = time();
        $data['update_time'] = time();
        return $data;
    }
    //更新前操作
    protected function _before_update(&$data, $options)
    {

        $data['update_time'] = time();
        return $data;
    }

}