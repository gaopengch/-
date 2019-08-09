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

class GoodsMarkingModel extends BaseModel
{
    private static $obj;


	public static $id_d;	//ID

	public static $name_d;	//商品打标名称

	public static $status_d;	//是否启用：1启用，0不启用

	public static $createTime_d;	//创建时间


	public static $updateTime_d;	//更新时间


    public static function getInitnation()
    {
        $name = __CLASS__;
        return static::$obj = ! (static::$obj instanceof $name) ? new static() : static::$obj;
    }


    protected $patchValidate = true;
    protected $_validate = [
        ['nav_titile','require','导航菜单名称不能为空'],
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

    //获取打标数据
    public function getGoodsMarking(){
        $data = $this->field('id,name')->where('status = 1')->select();
        return $data;
    }

}