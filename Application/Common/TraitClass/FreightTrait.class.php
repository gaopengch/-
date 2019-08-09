<?php
namespace Common\TraitClass;

use Common\Tool\Tool;
use Common\Model\BaseModel;
use Common\Model\UserAddressModel;
use Home\Model\FreightModeModel;
use Home\Model\FreightsModel;
use Home\Model\FreightConditionModel;
use Home\Logical\Model\FreightSendAreaLogic;
use Home\Logical\Model\FreightAreaLogic;
use Common\Strategy\FreightContent;
use Common\Tool\Extend\ArrayParse;

//运费Trait
/**
 * Class FreightTrait
 * @package Common\TraitClass
 *          $_POST = [
 *          id -> 快递id
 *          addressid  ->  收货地址id
 *          discount  ->   折扣
 *           ]
 */
trait FreightTrait
{
    /**
     * 计算运费
     */
    public function sumFreight()
    {
        $validate = ['is_numeric' => ['id', 'addressId', 'discount']];

        Tool::checkPost($_POST, $validate, true, array(
            'id'
        )) ? true : $this->ajaxReturnData(null, 0, '快递方式错误');

        $this->promptPjax( $_SESSION['user_goods_monery'], '商品金额有误');

        //获取收货地址
        $userAddressMode   = BaseModel::getInstance(UserAddressModel::class);

        $areaId            = $userAddressMode->getOne($_POST['addressId'], UserAddressModel::$city_d . ',' . UserAddressModel::$provId_d);

        $this->promptPjax($areaId, '请选择收货地址');

        //首先获取 此运送方式的所有运费设置
        $freightModel = BaseModel::getInstance(FreightModeModel::class);

        $freightModeList = $freightModel->getShipModeConfig($_POST['id']);

        $this->promptPjax($freightModeList, '运费方式没有对应的收费标准~~~');

        Tool::connect('parseString');

        //筛选是否在运送地区内
        $freightSendAreaModel  = new FreightSendAreaLogic($freightModeList);

        $freightSendAreaModel->setAreaId($areaId);

        $freightModeInAreaList = $freightSendAreaModel->isInclude($freightModel);

        $this->promptPjax($freightModeInAreaList, '该快递不包含该运送地区');

        //筛选是否包邮
        $freightConditionModel = BaseModel::getInstance(FreightConditionModel::class);

        $freightConditionList  = $freightConditionModel->IsInFreeShipingArea($freightModeInAreaList, $freightModel);


        //获取运费模板数据
        $freightTemplateModel = BaseModel::getInstance(FreightsModel::class);

        $freightConditionList = $freightTemplateModel->isFreeShipping($freightConditionList, $freightModel);


        //是否在包邮地区内
        $freightAreaModel = new FreightAreaLogic($freightConditionList);

        $freightAreaModel->setAreaId($areaId);

        $freightMonery  = $freightAreaModel->isInclude(); //在包邮地区内

        if (empty($freightMonery)) {

            $freightMonery = $freightTemplateModel->isFreeShipping($freightModeInAreaList, $freightModel);

        }
        $receive = array();

        $freightMonery = (new ArrayParse([]))->oneArray($receive, $freightMonery);
        // 计算运费
        $type = FreightContent::parseCall($receive[FreightsModel::$valuationMethod_d]);
        $obj = FreightContent::getInstance($type, $receive)->newInstance();

        $obj->setDiscount($_POST['discount']);

        $money = $obj->acceptCash();
        $_SESSION['FreightMoney'] = $money;//商品的运费

        $_SESSION['total_money_sum'] = $money + $_SESSION['user_goods_monery'];
        
        $this->ajaxReturnData(['money' => $money]);

    }

    /**
     * 商品分单计算运费
     */
    public function sumFreight2()
    {
        $validate = ['is_numeric' => ['id', 'addressId', 'discount']];
        $goods = $_SESSION['buyByCartGoods'];

        Tool::checkPost($_POST, $validate, true, array(
            'id'
        )) ? true : $this->ajaxReturnData(null, 0, '快递方式错误');

//        $this->promptPjax( $_SESSION['user_goods_monery'], '商品金额有误');
        $_SESSION['total_money_sum'] = $_SESSION['user_goods_monery']; //防运费模板报错，先保留商品金额
        $sendGoods = [];
        foreach($goods as $v){
            if(!array_key_exists($v['send_address'],$sendGoods)){
                $sendGoods[$v['send_address']]['num'] = $v['num'];
                $sendGoods[$v['send_address']]['weight'] = $v['weight'] * $v['num'];
                $sendGoods[$v['send_address']]['sumprice'] = $v['price'] * $v['num'];

            }else{
                $sendGoods[$v['send_address']]['num'] += $v['num'];
                $sendGoods[$v['send_address']]['weight'] += $v['weight'] * $v['num'];
                $sendGoods[$v['send_address']]['sumprice'] += $v['price'] * $v['num'];
            }
        }
        $freiTotal = 0;
        $flag = [];
        foreach($goods as $k=>&$v){
            $_SESSION['user_goods_weight'] = $sendGoods[$v['send_address']]['weight'];
            $_SESSION['user_goods_number'] = $sendGoods[$v['send_address']]['num'];
            $_SESSION['alone_goods_money'] = $sendGoods[$v['send_address']]['sumprice'];
            //获取收货地址
            $userAddressMode   = BaseModel::getInstance(UserAddressModel::class);

            $areaId            = $userAddressMode->getOne($_POST['addressId'], UserAddressModel::$city_d . ',' . UserAddressModel::$provId_d);

            $this->promptPjax($areaId, '请选择收货地址');

            //首先获取 此运送方式的所有运费设置
            $freightModel = BaseModel::getInstance(FreightModeModel::class);

//            $freightModeList = $freightModel->getShipModeConfig($_POST['id']);
            $freightModeList = $freightModel->getShipModeConfig2($v['freights_id']);

            $this->promptPjax($freightModeList, '运费方式没有对应的收费标准~~~');

            Tool::connect('parseString');

            //筛选是否在运送地区内
            $freightSendAreaModel  = new FreightSendAreaLogic($freightModeList);

            $freightSendAreaModel->setAreaId($areaId);

            $freightModeInAreaList = $freightSendAreaModel->isInclude($freightModel);



            $this->promptPjax($freightModeInAreaList, '该快递不包含该运送地区');

            //筛选是否包邮
            $freightConditionModel = BaseModel::getInstance(FreightConditionModel::class);

            $freightConditionList  = $freightConditionModel->IsInFreeShipingArea($freightModeInAreaList, $freightModel);


            //获取运费模板数据
            $freightTemplateModel = BaseModel::getInstance(FreightsModel::class);

            $freightConditionList = $freightTemplateModel->isFreeShipping($freightConditionList, $freightModel);


            //是否在包邮地区内
            $freightAreaModel = new FreightAreaLogic($freightConditionList);

            $freightAreaModel->setAreaId($areaId);

            $freightMonery  = $freightAreaModel->isInclude(); //在包邮地区内

            if (empty($freightMonery)) {

                $freightMonery = $freightTemplateModel->isFreeShipping($freightModeInAreaList, $freightModel);

            }
            $receive = array();

            $freightMonery = (new ArrayParse([]))->oneArray($receive, $freightMonery);
            // 计算运费
            $type = FreightContent::parseCall($receive[FreightsModel::$valuationMethod_d]);

            $obj = FreightContent::getInstance($type, $receive)->newInstance();

            $obj->setDiscount($_POST['discount']);

            $money = $obj->acceptCash();

//            $v['freiMonery'] = $money;
//
//            $freiTotal += $money;

            //flag过滤同仓库商品，并取最低运费
            if(!array_key_exists($v['send_address'],$flag)){
                $flag[$v['send_address']] = ['money'=>$money,'id'=>$v['id'],'key'=>$k];
                $v['freiMonery'] = $money;
                $freiTotal += $money;
            }else{
                if($flag[$v['send_address']]['money']>$money){
                    $v['freiMonery'] = $money;
                    $goods[ $flag[ $v['send_address'] ]['key'] ]['freiMonery'] = 0;

                    $num = $flag[$v['send_address']]['money'] - $money;
                    $freiTotal = $freiTotal - $num ;
                }else{
                    $v['freiMonery'] = 0 ;
                    $freiTotal += 0;
                }
            }

        }

        $_SESSION['buyByCartGoods'] = $goods;

        $_SESSION['FreightMoney'] = $freiTotal;//商品的运费
//        $_SESSION['total_money_sum'] = $freiTotal + $_SESSION['user_goods_monery'];     注释掉  ---meng
        $_SESSION['total_money_sum'] = $freiTotal +  $_SESSION['orderTotal'];      //  ---meng
        unset($_SESSION['orderTotal']);

        $this->ajaxReturnData(['money' => $freiTotal]);

    }

}