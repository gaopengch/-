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

class GoodsImagesModel extends BaseModel
{
    private static $obj;
    

	public static $id_d;

	public static $goodsId_d;

	public static $picUrl_d;

	public static $status_d;


	public static $isThumb_d;	//缩略图【1是 0否】

    
    public static function getInitnation()
    {
        $class = __CLASS__;
        return !(self::$obj instanceof $class) ? self::$obj = new self() : self::$obj;
    }

    /**
     * 商品相册
     */
    public  function getGoodsPictureAlbum($id)
    {
        if (!is_numeric($id) || $id == 0) {
            return array();
        }
    
        $data  = $this->getAttribute(array(
            'field' => array(self::$goodsId_d, self::$picUrl_d, self::$id_d),
            'where' => array(self::$goodsId_d => $id, self::$status_d => 1, self::$isThumb_d => 0)
        ));
        $data = $this->add_water($data);
        return $data;
    }
    
  
    /** 
     * @desc 热卖推荐
     * @param array $data
     * @param string $splitKey
     * @param array|string $field
     * @param string $where
     * @return array;
     */
    public function hotRecommendation (array $data, $splitKey) 
    {
        if (empty($data) || !is_string($splitKey)) {
            return array();
        }
        
        
        $length   = count($data);
        
        $noImages = array();
        if ($length > 3 ) {
            
            $noImages = array_splice($data, 2);
        } 
        
        $data = $this->getImageById($data, $splitKey);
        
        return array_merge($data, $noImages);
    }
     //查询商品图片
    public function getGoodsImageByData($data){
        if(empty($data)) {   
            return false;
        }
        foreach ($data as $key => $value) {
            if ($value['p_id'] == 0) {
                $where['goods_id'] = $value['goods_id'];
            }else{
                $where['goods_id'] = $value['p_id'];
            }            
            $img = M('goods_images')->field('id,pic_url')->where($where)->find();
            $data[$key]['images'] = $img['pic_url']; 
        }
        return $data; 
    }
     //查询商品图片
    public function getGoodsImageByOrder(array $order){
        if(empty($order)) {   
            return false;
        }
        foreach ($order as $key => $value) {
            foreach ($value['goods'] as $k => $v) {
                $where['goods_id'] = $v['p_id'];
                $img = M('goods_images')->field('id,pic_url')->where($where)->find();
                $order[$key]['goods'][$k]['images'] = $img['pic_url'];
            } 
        }
        return $order; 
    }
     //查询商品图片
    public function getGoodsImageByGoods(array $goods){
        if(empty($goods)) {   
            return false;
        }            
        $where['goods_id'] = $goods['p_id'];
        $img = M('goods_images')->field('id,pic_url')->where($where)->find();
        $goods['images'] = $img['pic_url'];       
        return $goods; 
    }
    
    /**
     * 获取图片 
     */
    public function getImageById (array $goods, $split)
    {
        if (empty($goods)) {
            return array();
        }
        
        $ids = Tool::characterJoin($goods, $split);
        
        $ids = str_replace('"', null, $ids);
        if (empty($ids)) {
            return $goods;
        }
        
        //分组依据列
        $data = $this
                ->field(self::$goodsId_d.self::DBAS.$split.', MAX('.self::$picUrl_d.') as '.self::$picUrl_d)
                ->where(self::$isThumb_d.' = 0 and '.self::$goodsId_d.' in (%s)', $ids)
                ->group(self::$goodsId_d)
                ->select();
        
        if (empty($data)) {
            return $goods;
        }
        
        $data = $this->covertKeyById($data, $split);
        
        $temp = [];
        
        foreach ($goods as $key => & $value)
        {
            $temp = $data[$value[$split]][self::$picUrl_d];
            $value[self::$picUrl_d] = empty($temp) ? '' : $temp;
        }
        
        return $goods;
    }

    /**
     * @desc 售罄商品添加水印
     * @param $data array
     * @return array;
     */
    public function add_water($data) {

        foreach($data as $index=>$images) {
            $stock = M('goods')->where(['id'=>$images['goods_id']])->getField('stock');
            if($stock <= 0) {
                $imageUrl = __SERVER__.$images["pic_url"];
                $ext = pathinfo($imageUrl)["extension"];
                $image_sellout = explode('.'.$ext, strstr($imageUrl,"Uploads"))[0];
                $pic_url =  '/'.$image_sellout."_sellout".'.'.$ext;
                $image_sellout = $_SERVER["DOCUMENT_ROOT"].'/'.$image_sellout."_sellout".'.'.$ext;
                if(file_exists($image_sellout)) {
                    goto edit;
                }
                $imginfo = getimagesize($imageUrl);
                $type = image_type_to_extension($imginfo[2], false);
                $x = $imginfo[0] * 0.2;
                $y = $imginfo[1] * 0.2;
                $imgcreate = "imagecreatefrom{$type}";
                $image = $imgcreate($imageUrl);
                $water_url = __SERVER__."/Public/Home/img/sellout.jpg";
                $imginfo_water = getimagesize($water_url);
                $type_water = image_type_to_extension($imginfo_water[2], false);
                $createWater = "imagecreatefrom{$type_water}";
                $water = $createWater($water_url);
                imagecopymerge($image, $water, $x, $y, 0, 0, $imginfo_water[0], $imginfo_water[1], 35);
                $saving = "image{$type}";
                $saving($image, $image_sellout);
                edit:
                $data[$index]["pic_url"] = $pic_url;
            }
        }
        return $data;
    }

//    //查询一张商品图片       ---meng
//    public function getGoodsImageByGroup(array $goods){
//        if(empty($goods)) {
//            return false;
//        }
//        $where['goods_id'] = $goods['goods_id'];
//        $img = M('goods_images')->field('pic_url')->where($where)->find();
//        $goods['images'] = $img['pic_url'];
//        return $goods;
//    }
}