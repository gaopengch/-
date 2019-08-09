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
use Common\Tool\Tool;
use Common\TraitClass\callBackClass;
use Common\Model\IsExitsModel;
use Common\TraitClass\SkuCheckTrait;

/**
 * 商品规格模型 
 * @author 王强
 * @version 1.0.1
 */
class SpecGoodsPriceModel extends BaseModel implements IsExitsModel
{
    use callBackClass;  
    use SkuCheckTrait;
    private static $obj;
    
	public $splitKey; //分隔符
    

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

    public static $priceLevel1_d;	//普通会员一口价
    public static $priceLevel2_d;	//铜牌会员一口价
    public static $priceLevel3_d;	//银牌会员一口价
    public static $priceLevel4_d;	//黄金会员一口价




    public static function getInitnation()
    {
        $name = __CLASS__;
        return static::$obj = !(static::$obj instanceof $name) ? new static() : static::$obj;
    }
    
    /**
     * @desc 组合规格项
     * @param array $attribute 规格表数组
     * @param array $request   笛卡尔数组
     * @param array $attr      规格项数组
     * @param BaseModel $specItemModel  规格项model
     * @return string;
     */
    public function getAttributeBuildGoodsInfo(array $attribute, array $request, array $attr, BaseModel $specItemModel)
    {
        if (!($specItemModel instanceof BaseModel)) {
            return null;
        }
        // 检测参数数据
        $array = func_get_args();
        array_pop($array);
        
        foreach ($array as $value) {
            if (empty($value) || !is_array($value)) {
                return null;
            }
        }
        
        $keySpecGoodsPrice = array();
       
        // 商品编辑时 数据处理
        if (!empty($_SESSION['goodsIdArr'])) { 
            
            $id = Tool::characterJoin($_SESSION['goodsIdArr'], static::$id_d);
            
            $keySpecGoodsPrice = $this
                 ->field(static::$barCode_d, true)
                ->where(static::$goodsId_d .' in ('.$id.')')
                ->select();
             $keyString = $array =  array();
             foreach ($keySpecGoodsPrice as $name => $value) {
                
                 $keyString[$value[static::$key_d]] = $value;
             }

             //添加会员一口价到$keyString数组中（编辑商品属性时的显示）   ---meng
            foreach ($keyString as $k => $vo) {
                  $goodsD = M('goods')
                          ->where(['id'=>$vo['goods_id']])
                          ->field('price_level1,price_level2,price_level3,price_level4')
                          ->find();
                $keyString[$k] = array_merge($vo,$goodsD);
            }


            //规格项
            $_SESSION['goodsIdArr'] = null;
        }
        
        $cloName = $request['arrayKeys'];
        $str = "<table class='table table-bordered' id='spec_input_tab'>";
        $str .="<tr>";
        // 显示第一行的数据
        foreach ($cloName as $k => $v)
        {
            $str .=" <td><b>{$attribute[$v]}</b></td>";
        }
        $str .="<td><b>市场价（划线价）</b></td>
               <td><b>会员价格</b></td>
               <td><b>普通会员一口价</b></td>
               <td><b>铜牌会员一口价</b></td>
               <td><b>银牌会员一口价</b></td>
               <td><b>黄金会员一口价</b></td>
               <td><b>超市价</b></td>
               <td><b>清仓活动价</b></td>
               <td><b>库存</b></td>
                <td><b>商品编码</b></td>
                <td><b>操作</b></td>
             </tr>";
        
        foreach ($request['cartesianProduct'] as $key => $value)
        {
            $str .="<tr>";
            $itemKeyName = array();
            $flag = 0;
            foreach($value as $k2 => $v2)
            {
                $str .="<td>{$attr[$v2][$specItemModel::$item_d]}</td>";
                $itemKeyName[$v2] = $attribute[$attr[$v2][$specItemModel::$specId_d]].':'.$attr[$v2][$specItemModel::$item_d];
            }//
            ksort($itemKeyName);
            $itemKey = implode('_', array_keys($itemKeyName));
            if(!empty($keyString) ) {
                $str .="<td><input type='text' name='item[$itemKey][".static::$price_d."]' value='{$keyString[$itemKey][static::$price_d]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
                
                $str .="<td><input type='text' name='item[$itemKey][".static::$preferential_d."]' value='{$keyString[$itemKey][static::$preferential_d]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";

                //追加会员价格   ---meng
                $str .="<td><input type='text' name='item[$itemKey][".static::$priceLevel1_d."]' value='{$keyString[$itemKey][static::$priceLevel1_d]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";

                $str .="<td><input type='text' name='item[$itemKey][".static::$priceLevel2_d."]' value='{$keyString[$itemKey][static::$priceLevel2_d]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";

                $str .="<td><input type='text' name='item[$itemKey][".static::$priceLevel3_d."]' value='{$keyString[$itemKey][static::$priceLevel3_d]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";

                $str .="<td><input type='text' name='item[$itemKey][".static::$priceLevel4_d."]' value='{$keyString[$itemKey][static::$priceLevel4_d]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";

                //追加会员价格   ---meng


                
                $str .="<td><input type='text' name='item[$itemKey][".static::$priceSuper_d."]' value='{$keyString[$itemKey][static::$priceSuper_d]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
                $str .="<td><input type='text' name='item[$itemKey][".static::$priceClear_d."]' value='{$keyString[$itemKey][static::$priceClear_d]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";

                $str .="<td><input type='text' name='item[$itemKey][".static::$storeCount_d."]' value='{$keyString[$itemKey][static::$storeCount_d]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
                
                $str .="<td><input type='hidden' name='item[$itemKey][".static::$goodsId_d."]' 
                            value='{$keyString[$itemKey][static::$goodsId_d]}' />
                            <input type='hidden' name='item[$itemKey][".static::$id_d."]' value='{$keyString[$itemKey][static::$id_d]}' />
                            <input name='item[{$itemKey}][".static::$sku_d."]' value='{$keyString[$itemKey][static::$sku_d]}' />
                        </td>";
                 $str .="<td>
                            <input type='button' onclick='del_item({$keyString[$itemKey][static::$goodsId_d]})' value='删除'/>
                        </td>";
            } else {
                $str .="<td><input type='text' name='item[$itemKey][".static::$price_d."]'  onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
              
                $str .="<td><input type='text' name='item[$itemKey][".static::$preferential_d."]'  onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";

                //追加会员价格   ---meng
                $str .="<td><input type='text' name='item[$itemKey][".static::$priceLevel1_d."]'  onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";

                $str .="<td><input type='text' name='item[$itemKey][".static::$priceLevel2_d."]'  onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";

                $str .="<td><input type='text' name='item[$itemKey][".static::$priceLevel3_d."]'  onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";

                $str .="<td><input type='text' name='item[$itemKey][".static::$priceLevel4_d."]'  onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";

                //追加会员价格   ---meng


                $str .="<td><input type='text' name='item[$itemKey][".static::$priceSuper_d."]'  onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
                $str .="<td><input type='text' name='item[$itemKey][".static::$priceClear_d."]'  onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";

                $str .="<td><input type='text' name='item[$itemKey][".static::$storeCount_d."]'  onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
               
                $str .="<td><input type='text' name='item[$itemKey][".static::$sku_d."]' /></td>";

                $str .="<td>
                            <input type='button' onclick='del_item({$keyString[$itemKey][static::$goodsId_d]})' value='删除'/>
                        </td>";
            }
            $str .='</tr>';
        }
        $str .='</table>';
        return $str;
    }
    
    /**
     * 添加 商品-规格对应 
     * @param array $data 规格数据
     * @param array $goodsId 商品编号
     * @return 
     */
    public function addSpecByGoods(array $data, array $goodsId)
    {
        
        if (empty($data) || empty($goodsId)) {
            return array();
        }
        
        
        $specId = array_keys($data);
        
        $build  = array();
       
        $sku    = null;
        
        $skuFirstCheck = [];
        
        foreach ($data as $key => $value) {
            $build[] = $this->create($value);
            $sku .= ',"'.$value[static::$sku_d].'"';
            $skuFirstCheck [] = $value[static::$sku_d];
        }
       
        if ($this->isSameValueByArray($skuFirstCheck)) {
            $this->rollback();
            
            $this->error = '不允许重复编码';
            
            return false;
        }
        
        
        //检查sku 是否重复
        
        $sku     = substr($sku, 1);
        
        $skuByCount = $this->IsExits($sku);
        
        $this->skuCountBySku = $skuByCount;
        
        $isExtis = $this->checkAdd();
        
        if ($isExtis === true) {// 存在
            $this->rollback();
            return false;
        }
        
        
        foreach ($goodsId as $key => $value) {
            $build[$key][static::$goodsId_d] = $value;
        }
        foreach ($specId as $key => $value) {
            $build[$key][static::$key_d]     = $value;
            
            if (isset($value[static::$id_d])) {
                unset(  $build[$key][static::$id_d] );
            }
        }

        $status = $this->addAll($build);
        if (!$this->traceStation($status)) {
            return false;
        }
        
        $this->commit();
        //添加
        return $status;
    }
    
    /**
     * 保存编辑 
     */
    public function saveEdit(array $data, $id)
    {
        if (empty($data) || !is_array($data))
        {
            return $data;
        }

        $saveData = array();
        foreach ($data as $key => $value) {
            if (empty($value[static::$id_d])) {
               continue;
            }
            $saveData[$key] = $value;
            unset($data[$key]);
        }

//         showData($id);

        //标记变量 是添加还是更新
        $status = false;

        if (is_array($id) && !empty($id)) {
            $status = $this->addSpecByGoods($data, $id);
        }

        if (empty($saveData)) {

            return $status;
        }
        /**
         * 因为商品规格项每修改一次 规格项详细编号就要改变
         * 所以 这里 为了保持数据一致 删掉重加
         */
        if (empty($this->splitKey)) {
            return false;
        }

//         $idString = Tool::characterJoin($saveData, $this->splitKey);


//         $status = $this->where(static::$goodsId_d .' in ('.$idString.')')->delete();
//         if (empty($status)) {
//             $this->rollback();
//             return false;
//         }
        //批量更新
        $pasrseData = array();
        $sku = null;
        foreach ($saveData as $key => $value)
        {
            $pasrseData[$value[static::$id_d]][] = $key;
            $pasrseData[$value[static::$id_d]][] = $value[static::$price_d];
//            $pasrseData[$value[static::$id_d]][] = $value[static::$price_d];
            $pasrseData[$value[static::$id_d]][] = $value[static::$storeCount_d];
            $pasrseData[$value[static::$id_d]][] = $value[static::$sku_d];
            $pasrseData[$value[static::$id_d]][] = $value[static::$preferential_d];
            $pasrseData[$value[static::$id_d]][] = $value[static::$priceClear_d];
            $pasrseData[$value[static::$id_d]][] = $value[static::$priceSuper_d];

            //更新一口价及状态      ---meng
            $pasrseData[$value[static::$id_d]][] = $value[static::$priceLevel1_d]?$value[static::$priceLevel1_d]:null;
            $pasrseData[$value[static::$id_d]][] = $value[static::$priceLevel2_d]?$value[static::$priceLevel2_d]:null;
            $pasrseData[$value[static::$id_d]][] = $value[static::$priceLevel3_d]?$value[static::$priceLevel3_d]:null;
            $pasrseData[$value[static::$id_d]][] = $value[static::$priceLevel4_d]?$value[static::$priceLevel4_d]:null;

            //更新一口价及状态      ---meng

            $sku .= ',"'.$value[static::$sku_d].'"';

        }
        //检查sku 是否重复

    //检查sku 是否重复

        $sku     = substr($sku, 1);
        $skuByCount = $this->IsExits($sku);

        $this->skuCountBySku = $skuByCount;

        $isExtis = $this->checkUpdate();

        if ($isExtis === true) {// 存在
            $this->rollback();
            return false;
        }


        $keyArray =[
            static::$key_d,
            static::$price_d,
//            static::$price_d,
            static::$storeCount_d,
            static::$sku_d,
            static::$preferential_d,
            static::$priceClear_d,
            static::$priceSuper_d,
            //更新一口价及状态      ---meng
            static::$priceLevel1_d,
            static::$priceLevel2_d,
            static::$priceLevel3_d,
            static::$priceLevel4_d,
            //更新一口价及状态      ---meng

        ];

        $sql = $this->buildUpdateSql($pasrseData, $keyArray, $this->getTableName());
//        showData($sql);
        $status = parent::execute($sql);

        //更新Goods表等级会员一口价数据    ---meng
        foreach ($pasrseData  as $k => $v){
            $pasrseData[$k] = array_combine($keyArray,$v);
            $arrKey[] = $k;
        }
        $goodsIdArr1 = $this->where(['id'=>['in',$arrKey]])->field('goods_id')->select();
        $pasrseDataTmp = array_combine(array_column($goodsIdArr1 , "goods_id"),$pasrseData);
        foreach ($pasrseDataTmp  as $k => $v){
            $priceLevelData['price_level1'] = $v['price_level1']?$v['price_level1']:null;
            $priceLevelData['price_level2'] = $v['price_level2']?$v['price_level2']:null;
            $priceLevelData['price_level3'] = $v['price_level3']?$v['price_level3']:null;
            $priceLevelData['price_level4'] = $v['price_level4']?$v['price_level4']:null;
            $res = M('goods')->where(['id'=>$k])->save($priceLevelData);
        }
        unset($pasrseDataTmp);
        //更新Goods表一口价 状态数据    ---meng

        if (!$this->traceStation($status)) {
            return false;
        }
        $this->commit();
        return $status;
    }
    
    /**
     * 删除规格 商品
     */
    public function deleteGoods ($goodsId)
    {
        if (!$this->isEmpty($goodsId)) {
            $this->rollback();
            return false;
        }
        
        $status = $this->where(static::$goodsId_d.' in ('.implode(',', $goodsId).')')->delete();
        return $this->traceStation($status);
    }
    
   /**
    * 删除一个商品 
    */
    public function deleteSpecById($id)
    {
        if (($id = intval($id)) === 0) {
            $this->rollback();
            return false;
        }
        
        $status = $this->where(static::$goodsId_d.' = %d ', $id)->delete();
        
        if ($status === false) {
            $this->rollback();
            return false;
        }
        $this->commit();
        return true;
    }
    
    /**
     * 根据sku 获取数据 
     * @param string $skuIdString sku字符串
     * @return array
     */
    public function getSpecDataBySku($skuIdString)
    {
        if (empty($skuIdString)) { //没有 即不存在
            return array();
        }
        return $this->where(static::$sku_d.' in ('.$skuIdString.')')->getField(static::$id_d.','.static::$sku_d);
    }
    
    /**
     * 检查是否存在相同的sku
     * {@inheritDoc}
     * @see \Common\Model\IsExitsModel::IsExits()
     */
    public function IsExits($post)
    {
        // TODO Auto-generated method stub
        if (empty($post)) { //没有 即不存在
            return false;
        }
        
        $skuData = $this->getSpecDataBySku($post);
        if (empty($skuData)) {
            return false;
        }
        
        $post = str_replace('"', '', $post);
       
        $skuIdArray = explode(',', $post);
        
        $skuData = array_merge($skuData, $skuIdArray);        
        
        $array = array_count_values($skuData); //统计出现的次数
        return $array;
        
    }
}