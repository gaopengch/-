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
use Common\Tool\Tool;

class SpecGoodsPriceModel extends BaseModel
{
    private $splitKey;

    private static $obj;

	public static $id_d;	//id

	public static $goodsId_d;	//商品id

	public static $key_d;	//规格键名

	public static $keyName_d;	//规格键名中文

	public static $price_d;	//价格

	public static $storeCount_d;	//

	public static $barCode_d;	//商品条形码

	public static $sku_d;	//SKU

	public static $preferential_d;	//会员价

	public static $priceClear_d;	//清仓价

	public static $priceSuper_d;	//超市价格

	public static $priceLevel1_d;	//一级会员价

	public static $priceLevel2_d;	//二级会员价

	public static $priceLevel3_d;	//三级会员价

	public static $priceLevel4_d;	//四级会员价

    public static $stock_d;

	public static $rebateTwo_d;	//二级返利

	public static $rebateThree_d;	//三级返利

    public static function getInitnation()
    {
        $name = __CLASS__;
        return self::$obj = !(self::$obj instanceof $name) ? new self() : self::$obj;
    }
    /**
     * 根据商品编号 获取数据 
     */
    public function getSpecByGoodsId ($goodsId)
    {
        if ( ($goodsId = intval($goodsId)) === 0 ) {
            return array();
        }
        
        return $this->getAttribute([
            'field' => [self::$id_d,self::$goodsId_d, self::$key_d],
            'where' => [self::$goodsId_d => $goodsId],
        ]);
    }
    
    /**
     * 购物车数组 
     */
    public function getSpecGoodsByCart(array $cart)
    {
        if (!$this->isEmpty($cart)) {
            return array();
        }
        
        $idString = Tool::characterJoin($cart, $this->splitKey);
        
        if (empty($idString)) {
            $this->error = '购物车数组错误';
            return array();
        }
        
        $field = self::$goodsId_d.','.self::$key_d.',' .self::$sku_d;
        
        $data = $this->where(self::$goodsId_d.' in('.$idString.')')->getField($field);
       
        if (empty($data)) {
            $this->error = '规格数据错误';
            return array();
        }
       
        foreach ($cart as $key => & $value) {
            if (!array_key_exists($value[$this->splitKey], $data)) {
                continue;
            }
            $value[self::$key_d] = $data[$value[$this->splitKey]][self::$key_d];
            $value[self::$sku_d] = $data[$value[$this->splitKey]][self::$sku_d];
        }
        return $cart;
    }
    
    /**
     * 获取商品规格数据 
     * @param array $specData
     * @param string $split  以 ....分割
     * @return array
     */
    public function getSpecByGoods (array $specData, $split)
    {
        if (empty($specData)) {
            return array();
        }
        
        $field = array(
            static::$id_d,
            static::$goodsId_d,
            static::$key_d,
            static::$price_d,
            static::$storeCount_d,
            static::$preferential_d,
            static::$sku_d
        );
        $data = $this->getDataByOtherModel($specData, $split, $field, static::$goodsId_d);
        
        return $data;
    }
    
    /**
     * 获取sku
     * @param int $id
     * @return array
     */
    public function getSku ($id) 
    {
        if (($id = intval($id)) === 0) {
            return array();
        }
        
        return $this->field(self::$sku_d)->where(self::$goodsId_d.'=%d', $id)->find();
    }
    
    /**
     * @return the $stock
     */
    public static function getStock()
    {
        return self::$stock_d;
    }
    
    /**
     * @param field_type $stock
     */
    public static function setStock()
    {
        self::$stock_d = self::$storeCount_d;
    }
    
    /**
     * @return the $splitKey
     */
    public function getSplitKey()
    {
        return $this->splitKey;
    }
    
    /**
     * @param field_type $splitKey
     */
    public function setSplitKey($splitKey)
    {
        $this->splitKey = $splitKey;
    }
}