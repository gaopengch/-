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
use Common\Tool\Extend\Time;
use Admin\Model\PayTypeModel;
use Common\TraitClass\StatisticsTrait;
use Common\Model\ExpressModel;
use Common\Model\UserAddressModel;
use Common\Model\RegionModel;

/**
 * 统计
 * @author 王强
 * @version 1.0
 * 2017 -4 -6 
 */
class AnalysisController extends AuthController
{
    use StatisticsTrait;
    
    /**
     * 订单相关图表 
     */
    public function Order2 ()
    {
        //获取订单数 支付订单数

        $order = BaseModel::getInstance(OrderModel::class);

        $totalNumber = $order->getNumberByWhere();

        $where = OrderModel::$orderStatus_d.' = "1"';

        $dataStr = (new Time())->getTime(C('ORDER_NUMBER'));//x轴日期

        //付款数
        $moneryNumber = $order->getNumberByWhere($where);

        //获取指定日期的订单数量
        $dataOrderNumber = $order->getDataOrderNumberByDate($dataStr);

        //付款数
        $payNumber       = $order->getDataOrderNumberByDate($dataStr, ' and '.OrderModel::$orderStatus_d.' BETWEEN "'.OrderModel::YesPaid.'" and "'.OrderModel::ReturnMonerySucess.'"');

        $this->assign('totalNumber', $totalNumber);

        $this->assign('moneryTotal', $moneryNumber);

        $this->assign('dataStr', json_encode($dataStr));
        $this->assign('dataNumber', json_encode(array_values($dataOrderNumber)));
        $this->assign('payNumber', json_encode(array_values($payNumber)));
        $this->display();
    }

    public function Order ()
    {
        //获取订单数 支付订单数
        $order = BaseModel::getInstance(OrderModel::class);
        $totalNumber = $order->getNumberByWhere();
        //付款数
        $where = OrderModel::$orderStatus_d.' > "0"';
        $moneryNumber = $order->getNumberByWhere($where);
        //获取指定日期的订单数量
        if(isset($_GET['date'])) {
            if (is_numeric($_GET['date'])) {
                $date=$_GET['date'];
                $dataStr = $this->searchOrder($date);
            }
        }elseif(isset($_GET['start_time'])  ){
            $dataStr = $this->gettime();
        }else{
            $dataStr=(new Time())->getTime(C('ORDER_NUMBER'));
        }

        $dataOrderNumber = $order->getDataOrderNumberByDate($dataStr);
        //付款数
        $payNumber       = $order->getDataOrderNumberByDate($dataStr, ' and '.OrderModel::$orderStatus_d.' BETWEEN "'.OrderModel::YesPaid.'" and "'.OrderModel::ReturnMonerySucess.'"');
        $this->assign('totalNumber', $totalNumber);
        $this->assign('moneryTotal', $moneryNumber);
        $this->assign('dataStr', json_encode($dataStr));
        $this->assign('dataNumber', json_encode(array_values($dataOrderNumber)));
        $this->assign('payNumber', json_encode(array_values($payNumber)));
        $this->display();
    }
    /**条件搜索
     *
     */
    public function searchOrder($date){
        if($date==7) {
            $dataStr=(new Time())->getTime(C('ORDER_NUMBER'));
            return $dataStr;
        }
        if($date==23){
            $dataStr=(new Time())->getTime(C('ORDER_NUMBER')+23);
            return $dataStr;
        }
    }

    /**条件搜索2
     *按时间搜索
     */
    public function gettime(){

        if( $_GET['start_time'] && !$_GET['end_time'] ){
            $start = $_GET['start_time'];
            $end = date('y-m-d ', time() );
            $num = $this->diffBetweenTwoDays($start,$end);

            $dataStr = (new Time())->getTime($num);
            return $dataStr;
        }elseif($_GET['start_time'] && $_GET['end_time']){
            $start = $_GET['start_time'];
            $end   = $_GET['end_time'];
            $num   = $this->diffBetweenTwoDays($start,$end);
            for ($i = $num, $i >= 0; $i--;) {//因为是从零开始的
                $dataStr[] = date("Y-m-d", strtotime($end."-".$i." day"));
            }
            return $dataStr;
        }
    }
    /**
     * 求两个日期之间相差的天数
     * (针对1970年1月1日之后，求之前可以采用泰勒公式)
     * @param string $day1
     * @param string $day2
     * @return number
     */
    function diffBetweenTwoDays ($day1, $day2)
    {
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);

        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        return ($second1 - $second2) / 86400;
    }
    
    /**
     * 当天订单数 
     */
    public function curretDayNumber()
    {
        $order = BaseModel::getInstance(OrderModel::class);
        //当天订单数
        $dayTime = mktime(0,0,0,date('m'),date('d'),date('Y'));
        
        $where = OrderModel::$createTime_d.' > '. $dayTime;
        
        //付款数
        $moneryNumber = $order->getNumberByWhere($where);
        
        $this->ajaxReturnData(['number' => $moneryNumber]);
    }
    /**
     * 当月订单数 
     */
    public function curretMonthNumber()
    {
        $order = BaseModel::getInstance(OrderModel::class);
        //当月订单数
        $monthTime = mktime(0,0,0,date('m'),1,date('Y'));
        
        $where = OrderModel::$createTime_d.' > '. $monthTime;
        
        //付款数
        $moneryNumber = $order->getNumberByWhere($where);
        
        $this->ajaxReturnData(['number' => $moneryNumber]);
    }
    
    /**
     * 付款统计 圆形图
     */
    public function payMemtMonery ()
    {
        //获取支付方式
        
        $model = BaseModel::getInstance(PayTypeModel::class);
        
        $data  = $model->getPay();
        
        //统计 各支付类型 的订单支付数据
        
        $orderModel = BaseModel::getInstance(OrderModel::class);
        
        $orderNumberByPayType = $orderModel->getCountGroupByPayType();
        
        //处理组合数据
        $payTypeData = $this->compilerData($data, $orderNumberByPayType);
        //从订单表中获取 支付数据
        $this->ajaxReturnData(['pay_type' => array_values($data), 'order_pay_number' => $payTypeData]);
    }
    
    /**
     * 配送方式 统计
     */
    public function distributionMode ()
    {
        $express = BaseModel::getInstance(ExpressModel::class);
        
        $expressData = $express->getIdAndName();
        
        //从订单表中获取 支付数据 //统计 各配送类型 的订单支付数据
        
        $orderModel = BaseModel::getInstance(OrderModel::class);
        
        $orderNumberByDistributionMode = $orderModel->getCountGroupByDistributionMode();
       
        //处理组合数据
        $distribution_mode = $this->compilerData($expressData, $orderNumberByDistributionMode);
       
        $this->ajaxReturnData(['distribution_mode' => array_values($expressData), 'distribution_mode' => $distribution_mode]);
    }
    
    /**
     * 地区订单统计 
     */
    public function getAreaOrderNumber ()
    {
        $model = BaseModel::getInstance(OrderModel::class);
        
        $areaOrderNumber = $model->getCountGroupArea();
        
        $this->orderData = $areaOrderNumber;
        
        $addressModel = BaseModel::getInstance(UserAddressModel::class);
        //获取地区编号
        $addressData = $addressModel->getAnalysis($areaOrderNumber, UserAddressModel::$provId_d);
        //传递地区表
        $areaModel = BaseModel::getInstance(RegionModel::class);
        
        $areaData  = $areaModel->getAnalysis($addressData, RegionModel::$name_d);
        $dataArray = $this->parseDataByArea($addressData, $areaData);
        
        $this->ajaxReturnData(['areaOrder' => $dataArray]);
    }

    /**
     * 优惠劵使用统计
     */
    public function ticketCount(){
        if($name=I('post.name'))
            $where['name']=array('LIKE',"%$name%");
        I('post.money')?$where['money']=I('post.money'):'';
        $coupons =M('coupon')->field('id,money,name,use_num,type')->where($where)->select(); //所有的优惠劵信息
        $couponInfo=D('CouponList')->useCouponCount($coupons,['id','money','name','use_num','type']); //优惠劵的条数信息
        $this->assign('couponInfoes',$couponInfo);

        $this->display();
    }

    public function ticketUser(){
        I('ticketId')?$ticketId=I('ticketId'):'';
        //查询使用了该优惠劵的用户信息
        $userTicket=M('coupon_list as a')
            ->field('b.user_name,b.mobile,c.order_sn_id,c.price_sum,c.order_status,a.order_id')
            ->join('JOIN db_user as b ON a.user_id=b.id')
            ->join('JOIN db_order as c ON a.order_id=c.id')
            ->where(['a.c_id'=>$ticketId,'a.status'=>1])
            ->select();
        $this->assign('ticketInfo',$userTicket);
        $this->display();
    }
}