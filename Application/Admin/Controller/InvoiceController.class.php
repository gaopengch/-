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

namespace Admin\Controller;

use Common\Controller\AuthController;
use Common\Model\BaseModel;
use Admin\Model\OrderModel;
use Common\Tool\Tool;
use Common\Model\ExpressModel;
use Common\Model\UserAddressModel;
use Common\TraitClass\SmsVerification;
use Admin\Model\PayTypeModel;
use Common\Model\RegionModel;
use Common\Model\OrderGoodsModel;
use Admin\Model\GoodsModel;
use Home\Model\SpecGoodsPriceModel;

/**
 * 发货单 
 */
class InvoiceController extends AuthController
{
    use SmsVerification;
    public function index ()
    {
        $this->condition = $this->getAppointData();
        $this->display();
    }
    public function index1 ()
    {
//        $this->condition = $this->getAppointData();
        $this->display();
    }

    /**
     * 获取配货单 
     */
    public function ajaxGetData ()
    {
        $addressModel = BaseModel::getInstance(UserAddressModel::class);
        
        $orderModel = BaseModel::getInstance(OrderModel::class);
        
        Tool::connect('ArrayChildren');
        
        $where = array();
        
        $where[OrderModel::$orderStatus_d] = ['between', OrderModel::InDelivery.','.OrderModel::ReceivedGoods];
        
        $addressIdArray = $addressModel->getSearchByData($_POST, OrderModel::$addressId_d);
        
        $orderIdArray  = $orderModel->getSearchByData($_POST, OrderModel::$id_d);
        
        $where = array_merge($where, $addressIdArray, $orderIdArray);
        
        Tool::isSetDefaultValue($_POST, array('orderBy' => 'id', 'sort' => 'DESC'));
        
        $orderData  = $orderModel->getOrderData($_POST, $where, 'INVOICE_CACHE_KEY_SEDFX');
        $this->promptPjax($orderData['data'], '暂无数据');
        
        $this->cackeKey = 'INVOICE_WHAT_KEY';//设置缓存key
        
        $this->addressModel = clone $addressModel;
        //获取运送方式
        $orderData['data'] = $this->getExpress($orderData['data'], $orderModel, OrderModel::$addressId_d);
        $this->expressModel = ExpressModel::class;
        $this->model        = OrderModel::class;
        $this->order        = $orderData;
        
        $this->display();
        
    }

    public function ajaxGetData1 ()
    {

        $addressModel = BaseModel::getInstance(UserAddressModel::class);

        $orderModel = BaseModel::getInstance(OrderModel::class);

        Tool::connect('ArrayChildren');

        $where = array();
        $where[OrderModel::$orderStatus_d] = ['between', OrderModel::InDelivery.','.OrderModel::ReceivedGoods];

        if(I('delivery_time_start') && I('delivery_time_end')) {
            $delivery_time_start = strtotime(I('delivery_time_start'));
            $delivery_time_end = strtotime(I('delivery_time_end')) + 60*60*24;
            $where[OrderModel::$deliveryTime_d] = ['between', $delivery_time_start.','.$delivery_time_end];
        }


        $addressIdArray = $addressModel->getSearchByData($_POST, OrderModel::$addressId_d);

        $orderIdArray  = $orderModel->getSearchByData($_POST, OrderModel::$id_d);
        $where = array_merge($where, $addressIdArray, $orderIdArray);

        Tool::isSetDefaultValue($_POST, array('orderBy' => 'id', 'sort' => 'DESC'));

        if(!empty($_POST['goods'])){
            $rand['title'] = ['like','%'.$_POST['goods'].'%'];
            $userArray = M('goods')->field('id')->where($rand)->select();
            if(!empty($userArray)){
                foreach($userArray as $k=>$v){
                    $userid[$k] = $v['id'];
                }
                $user1 = implode(",", $userid);
            }
            if(!empty($userid[0])){
                $orwhere['goods_id'] = ['in',$user1];
                $orderArray = M('order_goods')->field('order_id')->where($orwhere)->select();
                if(!empty($orderArray)){
                    foreach($orderArray as $k2=>$v2){
                        $oid[$k2] = $v2['order_id'];
                    }
                    $oid_str = implode(",", $oid);
                }
                if(!empty($oid[0])){
                    $where ['id'] = ['in',$oid_str];
                }
            }
        }


        $orderData  = $orderModel->getOrderData($_POST, $where, 'INVOICE_CACHE_KEY_SEDFX');

        $this->promptPjax($orderData['data'], '暂无数据');

        $this->cackeKey = 'INVOICE_WHAT_KEY';//设置缓存key

        $this->addressModel = clone $addressModel;
        //获取运送方式

        $orderData['data'] = $this->getExpress($orderData['data'], $orderModel, OrderModel::$addressId_d);
        $this->expressModel = ExpressModel::class;
        $this->model        = OrderModel::class;
        $this->order        = $orderData;

        $this->display();

    }
    
    /**
     * 配货单详情 
     */
    public function picking ($id)
    {
        $this->errorNotice($id);
        
        //订单信息
        $model = BaseModel::getInstance(OrderModel::class);
        
        $data = $model->getDataByColum($id);
        
        $this->promptParse($data);
        
        $payTypeModel = BaseModel::getInstance(PayTypeModel::class);
        
        //获取支付数据
        $data[OrderModel::$payType_d] = $payTypeModel->getUserNameById($data[OrderModel::$payType_d], PayTypeModel::$typeName_d);
        
        //获取物流信息
        $data[OrderModel::$expId_d]   = BaseModel::getInstance(ExpressModel::class)->getUserNameById($data[OrderModel::$expId_d], ExpressModel::$name_d);
        
        $userAddressModel = BaseModel::getInstance(UserAddressModel::class);
        
        // 地址数据
        $addressData = $userAddressModel->getAddressById($data[OrderModel::$addressId_d]);
       
        $areaModel = BaseModel::getInstance(RegionModel::class);
        
        $addressData = $areaModel->getDefaultRegion($addressData, $userAddressModel);
        
        $data = array_merge($addressData, $data);
        
        //商品信息
        $goods = $this->getGoodsInfoByOrder($data[OrderModel::$id_d]);
//        $this->key = 'intnetConfig';
        $this->key = 'information_by_intnet';
        //获取网站配置
        $this->assign('intnetConfig', $this->getGroupConfig());
        $this->assign('model', $model);
        $this->assign('addressModel', UserAddressModel::class);
        $this->assign('orderData', $data);
        $this->assign('orderDataGoods', $goods);
        $this->display();
    }
    
    /**
     * 获取订单信息 
     */
    private function getGoodsInfoByOrder ($orderId)
    {
        if (empty($orderId)) {
            return array();
        }
        Tool::connect('parseString');
        
        $orderGoodsModel = BaseModel::getInstance(OrderGoodsModel::class);
        
        $field = OrderGoodsModel::$goodsId_d.','.OrderGoodsModel::$goodsNum_d.','.OrderGoodsModel::$goodsPrice_d;
        
        $orderGoodsData  = $orderGoodsModel->getGoodsIdByOrderId($orderId, $field);

        $goodsModel = BaseModel::getInstance(GoodsModel::class);
        
        $goodsData = $goodsModel->getGoodsData($orderGoodsData, OrderGoodsModel::$goodsId_d);

        $goodsids = array_column($goodsData,'id');
        $sku = M('spec_goods_price')->where(['goods_id'=>['in',$goodsids]])->getField('goods_id,id,sku');

        foreach($goodsData as &$v){
            $v['sku'] = $sku[ $v['id'] ]['sku'];
            $v['goods_total'] = $v['goods_num']*$v['goods_price'] ;
        }

        return $goodsData;
        
    }

    /**
     * Excel导入订单快递公司名，快递单号，发货时间
     */
    public function impOrders() {
        $upload = new \Think\Upload();

        $upload->maxSize = 3145728;
        $upload->exts = array('xls');
        $upload->rootPath = "./Uploads/Orders/";
        $upload->savePath = '';
        $info = $upload->uploadOne($_FILES['import-orders']);
        if(!$info) {
            $this->error('无文件上传', U('Invoice/index1'));
        } else {
            $file = $upload->rootPath.$info['savepath'].$info['savename'];
            if(file_exists($file)) {
                vendor("PHPExcel.PHPExcel");
                vendor("PHPExcel.PHPExcel.IOFactory");
                $objReader = \PHPExcel_IOFactory::createReader('Excel5');
                $objPHPExcel = $objReader->load($file, 'utf-8');
                $sheet = $objPHPExcel->getSheet(0);
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $pre_row = 3;
                for($i = $pre_row; $i < $highestRow+1; $i++) {
//                    $data[$i-$pre_row]['order_sn_id'] = $objPHPExcel->getActiveSheet()->getCell('A'. $i)->getValue();
//                    $data[$i-$pre_row]['express_id'] = $objPHPExcel->getActiveSheet()->getCell('K'.$i)->getValue();
                    $data[$i-$pre_row]['order_sn_id'] = ltrim( $objPHPExcel->getActiveSheet()->getCell('A'. $i)->getValue());
                    $data[$i-$pre_row]['express_id'] = ltrim($objPHPExcel->getActiveSheet()->getCell('K'.$i)->getValue());

                    $data[$i-$pre_row]['expname'] = $objPHPExcel->getActiveSheet()->getCell('J'.$i)->getValue();
                    $data[$i-$pre_row]['shipping'] = (string)$objPHPExcel->getActiveSheet()->getCell('J'.$i)->getValue();
                    $data[$i-$pre_row]['order_status'] = 6;
                    $data[$i-$pre_row]['delivery_time'] =strtotime($objPHPExcel->getActiveSheet()->getCell('Q'.$i)->getValue()) ;
                }
                $order = [];
                foreach($data as $k=>$v){
                    $order_sn_id = M('order')->where(['order_sn_id'=>$v['order_sn_id'],'order_status'=>['gt','4']])->getField('order_sn_id');
                    if($order_sn_id){
                        $order[] = $order_sn_id;
                    }else{
                        $data2[$k] = $v;
                    }
                }
                $this->Querysql($data2);
                if(isset($order[0])){
                    $name = time();
                    file_put_contents('./Uploads/invoice/'.$name.'.txt',print_r($order,true),FILE_APPEND);
                    $this->ajaxReturnData($name,2,'导入成功');
                }else{
                    $this->ajaxReturnData($order,1,'导入成功');
                }

            } else {
                $this->ajaxReturnData('',0,'导入失败');
            }
        }
    }

    //
    public function Querysql($data){
        $exp_where = M('Express')->where(array('name'=>':name'));
        $sql = "UPDATE db_order SET exp_id = %d, express_id = '%s', delivery_time = %d , order_status = %d , shipping = '%s' where order_sn_id = '%s' ";
        foreach($data as $v) {
            $exp_where_tmp =clone $exp_where;
            $exp_id = intval($exp_where_tmp->bind(':name',$v['expname'])->getField('id'));
            $update_order = M('order')->execute($sql, array($exp_id, $v['express_id'], $v['delivery_time'], $v['order_status'], $v['shipping'], $v['order_sn_id']));
            unset($exp_where_tmp);
        }
    }

}