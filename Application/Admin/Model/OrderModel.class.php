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
use Common\Tool\Tool;
use Common\TraitClass\callBackClass;
use Common\Model\BaseModel;
use Think\AjaxPage;

/**
 * 订单模型 
 * @author 王强
 * @version 1.0.1
 */
class OrderModel extends BaseModel
{
    use callBackClass;
    // -1:取消订单,0 未支付，1已支付，2，发货中，3已发货，4已收货，5退货审核中，6审核失败，7审核成功，8退款中，9退款成功, 10：代发货，11待收货
    const CancellationOfOrder = -1;
    
    const NotPaid = 0;
    
    const YesPaid = 1;
    
    const InDelivery = 2;
    
    const AlreadyShipped = 3;
    
    const ReceivedGoods = 4;
    
    const ReturnAudit = 5;
    
    const AuditFalse  = 6;
    
    const AuditSuccess = 7;
    
    const Refund = 8;
    
    const ReturnMonerySucess = 9;
    
    const ToBeShipped = 10;
    
    const ReceiptOfGoods = 11;
    
    
    private static $obj ;


	public $isSelectColum;
    
    private $sColums = [];
    
    private $orderIds = []; //订单编号数组

	public static $id_d;	//id

	public static $orderSn_id_d;	//订单标识

	public static $priceSum_d;	//总价

	public static $expressId_d;	//快递单编号

	public static $addressId_d;	//收货地址编号

	public static $userId_d;	//用户编号

	public static $createTime_d;	//创建时间

	public static $deliveryTime_d;	//发货时间

	public static $payTime_d;	//支付时间

	public static $overTime_d;	//完结时间

	public static $orderStatus_d;	//-1：取消订单；0 未支付，1已支付，2，发货中，3已发货，4已收货，5退货审核中，6审核失败，7审核成功，8退款中，9退款成功, 

	public static $commentStatus_d;	//评价状态 0未评价 1已评价

	public static $distributionStatus_d;	//分销状态,0-未结算订单,1-已结算订单

	public static $freightId_d;	//运费id

	public static $wareId_d;	//仓库编号

	public static $payType_d;	//支付类型编号

	public static $remarks_d;	//订单备注

	public static $status_d;	//0正常1删除

	public static $translate_d;	//1需要发票，0不需要

	public static $shippingMonery_d;	//运费【这样 就不用 重复计算两遍】

	public static $expId_d;	//快递表编号

	public static $platform_d;	//平台：0代表pc，1代表app

	public static $orderType_d;	//订单类型0普通订单1货到付款

	public static $shipping_d;	//配送方式

	public static $integral_d;	//积分金额，不为0则为积分订单 create by li

	public static $rebateStatus_d;	//该订单是否已返利0-未,1-已


	public static $goodsRebate_d;	//订单总返利金


	public static $adminRemarks_d;	//后台修改订单添加备注


	public static $selfRebate_d;	//订单总自购返利


	public static $couponAmount_d;	//优惠金额


	public static $tradeNo_d;	//支付流水单号

    public static $groupPerson_num_d;	//已开团人数
    public static $groupId_d;	//团购活动ID
	public static $pid_d;	//团单父id
	public static $groupStatus_d;	//团购状态：1拼团中；2拼团成功；3拼团失败
	public static $overdueTime_d;	//团单支付过期时间
	public static $groupCause_d;	//团订单备注失败原因

	public static $isHost_d;	//0：不是团长 1:团长

   
    public static function getInitnation()
    {
        $class = __CLASS__;
        return static::$obj = !(static::$obj instanceof $class) ? new static() : static::$obj;
    }
    
    
    /**
     * @return the $orderIds
     */
    public function getOrderIds()
    {
        return $this->orderIds;
    }
    
    
    /**
     * 添加前操作
     * {@inheritDoc}
     * @see \Think\Model::_before_insert()
     */
    protected function _before_insert(&$data, $options)
    {
        
        $data[static::$createTime_d] = time();
       
        $data[static::$orderSn_id_d] = Tool::toGUID();
        
        $data[static::$orderStatus_d] = 0;
        
        $_SESSION[static::$orderSn_id_d] = $data[static::$orderSn_id_d];
        return $data;
    }
    
    
    
    /**
     * 根据订单号获取商品数量
     * @param string $orderSn 订单号
     * @return int
     */
    public function getGoodsByOrderSn($orderSn)
    {
        if (empty($orderSn) || !is_numeric($orderSn))
        {
            return false;
        }
        
        return $this->where('id = "%s"', $orderSn)->getField('price_sum');
    }
   
    /**
     * 获取 该用户下的全部订单 【可以归隐了】
     */
    public function getOrder($userAddress, $field = null, $default = 'select')
    {
        $field = $field === null ?  $this->getDbFields() : $field;
        if (is_array($field) && isset($field[0]))
        {
            $field[0] = 'id as order_id';
        }
        //处理查询条件
        $orderBy = Tool::buildActive($_POST);
        
        $where = $this->create($orderBy);
        
        //处理收货人
        if ( !empty($userAddress) ) {
            
            $id = Tool::connect('parseString')->characterJoin($userAddress, 'id');
            $id = str_replace('"', null, $id);
            $where['address_id'] = array('in', $id);
            
        }
        
        empty($_POST['timegap']) ?: $where['create_time'] = array('elt',strtotime($_POST['timegap'])); 
        
        $pageSize = C('PAGE_SIZE');
        $offset = ($_GET['page'] - 1) * $pageSize;
        
        $data =  $this
            ->field($field)
            ->where($where)
            ->order($_POST['orderBy'].' '.$_POST['sort'])
            ->limit($offset, $pageSize)
            ->$default();
        return $data;
    }
    
    
    /**
     * 根据订单号查询数据 
     */
    public function getOrderById($id)
    {
        if (($id = intval($id)) === 0)
        {
            return array();
        }
        $sColums = $this->sColums;
        $field =  empty($sColums) ?  implode(',', $this->getDbFields()) : $sColums;
        
        $data = $this->find(array(
            'field' => $field,
            'where' => array(static::$id_d => $id)
        ));
        
        return $data;
    }
    
    public function getDataByColum ($id)
    {
        
        $this->sColums = [
            static::$id_d,
            static::$orderSn_id_d,
            static::$expressId_d,
            static::$expId_d,
            static::$priceSum_d,
            static::$addressId_d,
            static::$payType_d,
            static::$createTime_d
        ];
        
        return $this->getOrderById($id);
        
    }
    
    
    /**
     * 编号获取单个数据 
     */
    public function getOneData ($id)
    {
       
        $data = $this->getOrderById($id);
       
        if (empty($data)) {
            return null;
        }
        return empty($data[$this->isSelectColum]) ? null : $data[$this->isSelectColum];
    }
    
    /**
     * 获取订单状态 
     */
    public function getOrderStatusByUser($id, $filed='id')
    {
        if (!is_numeric($id) || empty($filed))
        {
            return false;
        }
        return $this->where($filed.' = "%s"', $id)->getField('order_status');
    }
    
    /**
     * 处理订单搜索条件 
     * @param array $data 要处理的条件
     * @return array
     */
    public function parseOrderCondition(array $userAddress)
    {
        //处理查询条件
        $orderBy = Tool::buildActive($_POST);
      
        $where = $this->create($orderBy);
        //处理收货人
        if ( !empty($userAddress) && is_array($userAddress)) {
        
            $id = Tool::connect('parseString')->characterJoin($userAddress, static::$id_d);
            $id = str_replace('"', null, $id);
            $where[static::$addressId_d] = array('in', $id);
        }
        $timegap = strtotime(I('post.timegap'));
        $timeEnd = strtotime(I('post.timeEnd'));
        if (!empty($timegap)&&!empty($timeEnd)) {
            $where[static::$createTime_d] = array('between',array($timegap,$timeEnd));
        }else if(!empty($timegap)&&empty($timeEnd)){
            $where[static::$createTime_d] = array(array('egt',$timegap));
        }else if(empty($timegap)&&!empty($timeEnd)){
            $where[static::$createTime_d] = array('between',array('1362798943',$timeEnd));
        }

        return $where;
    }
    
    /**
     * 获取订单数据 
     */
    public function getOrderData (array $post,  $where, $cacheKey = 'ORDEEDATA_CACHE')
    {
        if (empty($post['orderBy']) || empty($post['sort']) ) {
            return array();
        }

        $orderData = S($cacheKey);
        if (empty($orderData)) {
            $orderData = $this->getDataByPage(array(
                'field' => array(static::$orderType_d),
                'where' => $where,
                'order' => $post['orderBy'].' '.$post['sort'],
            ), 20, true, AjaxPage::class);
            if (empty($orderData)) {
                return array();
            }
            S($cacheKey, $orderData, 1);
        }
        return (array)$orderData;
    }
    
    /**
     * 根据退货信息查找 订单数据 
     */
    public function getOrderByOrderReturn(array $data, $split)
    {
        if (!$this->isEmpty($data) || empty($split)) {
            return array();
        }
        
        foreach ($data as $key => $value) {
            $where['order_id'] = $value['order_id'];
            $where['status'] = 1;
            $data[$key]['wx_order_sn'] = M('OrderWxpay')->where($where)->getField('wx_pay_id');
        }

        $cache = S('OREDER_RETURN_ORDER_SN_CACHE');
        if (empty($cache)) {
            $cache = $this->getDataByOtherModel($data, $split, [
                static::$orderSn_id_d,
                static::$id_d,
                static::$orderStatus_d,
            ], static::$id_d);
            
            if (empty($cache)) {
                return array();
            }
            
            S('OREDER_RETURN_ORDER_SN_CACHE', $cache, 2);

        }
        return $cache;
    }
    
    /**
     * 获取【like】数据 
     */
    public function getLikeData ($data)
    {
        if (empty($data)) {
            return array();
        }
        $data = addslashes(strip_tags($data));
        
        $data = $this->field(static::$id_d)->where(static::$orderSn_id_d .' like "'.$data.'%"')->select();
        
        return $data;
    }
    
    /**
     * 获取数量根据条件 
     */
    public function getNumberByWhere($where = null)
    {
        
        $count = $this->where($where)->count();
        
        return $count;
        
    }
    /**
     * 组织数据 
     */
    public function getLikeDataByOrderSn ($data)
    {
        $data = $this->getLikeData($data);
        
        return Tool::characterJoin($data, static::$id_d);
    }
    
    /**
     * 获取搜索数据
     */
    public  function getSearch(array $post)
    {
        if (empty($post['order_id'])) {
            return array();
        }
    
        $data = $this->getLikeDataByOrderSn($post['order_id']);
        
        return $data;
    }
    
    /**
     * 获取指定日期的订单数量 
     */
    public function getDataOrderNumberByDate(array $dataStr, $where = null)
    {
        if (!$this->isEmpty($dataStr)) {
            $this->error = '数据错误';
            return array();
        }
        
        sort($dataStr);
        $flag = $dataStr;
        //去第一个 和最后 一个
        $startTime = array_shift($dataStr);
        
        $endTime   = end($dataStr);
        
        if (empty($startTime) || empty($endTime)) {
            $this->error = '日期数据错误';
            return array();
        }
        $startTime = strtotime($startTime.' 00:00:00');
        $endTime   = strtotime($endTime.' 23:59:59');
        $data = $this
            ->field('count(`'.static::$id_d.'`)'.static::DBAS.' order_count, FROM_UNIXTIME('.static::$createTime_d.', "%Y-%m-%d")'.static::DBAS.static::$createTime_d)
            ->where(static::$createTime_d.' BETWEEN '.$startTime.' and '. $endTime.' '.$where)
            ->group('FROM_UNIXTIME('.static::$createTime_d.', "%Y-%m-%d")')//分组依据列 和 聚合函数 才能 查出来
            ->order(static::$createTime_d.static::ASC)
            ->select();
        if (empty($data)) {
            $this->error = '暂无数据';
            return array();
        }
        $parseArray = array();
        
        
        //showData($data);
        foreach ($data as $key => $value)
        {
            $parseArray[$value[static::$createTime_d]] = $value['order_count'];
        }

        foreach ($flag as $key => $value) {
            if (array_key_exists($value, $parseArray)) {
                continue;
            }
            $parseArray[$value] = 0;
        }
        ksort($parseArray);
        return $parseArray;
    }
    
    /**
     * 获取各支付类型的订单数量 
     */
    public function getCountGroupByPayType ()
    {
        return $this->group(static::$payType_d)->getField(static::$payType_d.',count(`'.static::$id_d.'`)'.static::DBAS.' pay_type_count');
    }
    
    /**
     * 获取各配送类型的订单数量 
     */
    public function getCountGroupByDistributionMode ()
    {
        return $this->group(static::$expId_d)->getField(static::$expId_d.',count(`'.static::$id_d.'`)'.static::DBAS.' pay_type_count');
    }
    
    /**
     * 获取各地区的订单数量
     */
    public function getCountGroupArea ()
    {
        return $this->group(static::$addressId_d)->getField('count(`'.static::$id_d.'`)'.static::DBAS.' pay_type_count,'.static::$addressId_d);
    }
    
    /**
     * @return the $isSelectColum
     */
    public function getIsSelectColum()
    {
        return $this->isSelectColum;
    }
    
    /**
     * @param field_type $isSelectColum
     */
    public function setIsSelectColum($isSelectColum)
    {
        //版权所有©亿速网络
        $this->isSelectColum = $isSelectColum;
    }

    /**
     * @return the $sColums
     */
    public function getSColums()
    {
        return $this->sColums;
    }
    
    /**
     * 删除订单 
     */
    public function deleteOrder ($id)
    {
        if ( ($id = intval($id)) === 0) {
            return false;
        }
        $orderIds = $this->getAttribute(array(
            'field' => array(
                self::$id_d
            ),
            'where' => array(
                self::$userId_d => $id
            )
        ));
        
        if (empty($orderIds)) {
            return false;
        }
        
//        $status = $this->delete(array(
//            'where' => array(
//                $orderModel::$userId_d => $id
//            )
//        ));

        
//        if ($this->isEmpty($status)) {
//            return false;
//        }
        $this->orderIds = (array)$orderIds;
        return true;
    }
    /**
     * @param multitype: $sColums
     */
    public function setSColums($sColums)
    {
        $this->sColums = $sColums;
    }
    
    /**
     * 获取未处理订单数量
     */
    public function getUntreatedOrderNumber ()
    {
        return $this->where(self::$orderStatus_d. ' in ("'.self::YesPaid.'", "'.self::ReturnAudit.'")')->count();
    }
    public function GetOrderStatus($data){
        if(empty($data)){
            return array();
        }
        foreach ($data['data'] as $key => $value) {
            if($value['order_status']==0){
                $data['data'][$key]['OrderStatus'] = '未支付';
            }else if($value['order_status']==1){
                $data['data'][$key]['OrderStatus'] = '已支付';
            }else if($value['order_status']==2){
                $data['data'][$key]['OrderStatus'] = '发货中';
            }else if($value['order_status']==3){
                $data['data'][$key]['OrderStatus'] = '已发货';
            }else if($value['order_status']==4){
                $data['data'][$key]['OrderStatus'] = '已收货';
            }else if($value['order_status']==5){
                $data['data'][$key]['OrderStatus'] = '退货审核中';
            }else if($value['order_status']==6){
                $data['data'][$key]['OrderStatus'] = '审核失败';
            }else if($value['order_status']==7){
                $data['data'][$key]['OrderStatus'] = '审核成功';
            }else if($value['order_status']==8){
                $data['data'][$key]['OrderStatus'] = '退款中';
            }else if($value['order_status']==9){
                $data['data'][$key]['OrderStatus'] = '退款成功';
            }else{
                $data['data'][$key]['OrderStatus'] = '取消订单';
            }
            $where['id'] = $value['address_id'];
            $userAddress = M('UserAddress')->field('realname,mobile')->where($where)->find();
            $data['data'][$key]['user_name'] = $userAddress['realname'];
            $data['data'][$key]['mobile'] = $userAddress['mobile'];
        }
        return $data;
    }

    //获取自动取消时间
    public function getCancellTime(){
        $config = M('system_config')->where(['parent_key'=>'shopConfig'])->getField('config_value');
        $time = unserialize($config)['order_cancelled'];
        return $time;
    }


    //判断订单状态
    public function order_cancelled($data){
        $id = array();
        $time = $this->getCancellTime();
        if(!$time){
            $time = 24;
        }
        $now = time() - $time*3600;
        foreach($data['data'] as &$v){
            if($v['create_time']<$now && $v['order_status'] == '0'){
                $v['order_status'] = '-1';
                $id[] = $v['id'];
            }
        }
//        showData($id);
//        showData($data,1);
        if($id){
            $res  = $this->where(['id'=>['in',$id]])->setField('order_status','-1');
            $res2 = M('order_goods')->where(['order_id'=>['in',$id]])->setField('order','-1');
            $goods = M('order_goods')->field('goods_id,goods_num')->where(['order_id'=>['in',$id]])->select();
            foreach($goods as $v){
                $good_pid = M('goods')->where(['id'=>$v['goods_id']])->getField('p_id');
                M('goods')->where(['id'=>$v['goods_id']])->setInc('stock',$v['goods_num']);
                M('goods')->where(['id'=>$good_pid])->setInc('stock',$v['goods_num']);
                M('spec_goods_price')->where(['goods_id'=>$v['goods_id']])->setInc('store_count',$v['goods_num']);
            }
        }
        return $data;
    }
    /**
     * 根据订单id获取已参团用户信息     ---meng
     * $data 二维数组  0为团长信息
     */
    public function getGroupOrderUser($OrderId)
    {
        $data = [];
        //主团单数据
        $OrderData = $this->where(['id' => $OrderId])->find();
        $OrderData['user_name'] = M('user')->where(['id'=>$OrderData['user_id']])->getField("user_name");

        if ($OrderData['is_host'] == 1) {
            //团成员数据
            $childOrderData = $this
                ->where(['pid' => $OrderData['id']])
                ->order('pay_time')
                ->field("id,order_sn_id,user_id,pay_time,order_status,pay_time,group_status")
                ->select();
            $data[0] = $OrderData;
            foreach ($childOrderData as $k => &$v) {
                $v['user_name']=M('user')->where(['id'=>$v['user_id']])->getField("user_name");
                $data[$k + 1] = $v;
            }

        }else{   //不是团长，查询团长及其他成员
            $host = $this
                ->where(['id' => $OrderData['pid']])
                ->field("id,order_sn_id,user_id,pay_time,order_status,group_status,is_host")
                ->find();
            $host['user_name'] = M('user')->where(['id'=>$host['user_id']])->getField("user_name");

            //团成员数据
            $childOrderData = M('order')
                ->where(['pid' => $host['id']])
                ->order('pay_time')
                ->field("id,order_sn_id,user_id,pay_time,order_status,group_status,is_host")
                ->select();
            $data[0] = $host;
            foreach ($childOrderData as $k => &$v) {
                $v['user_name']=M('user')->where(['id'=>$v['user_id']])->getField("user_name");
                $data[$k + 1] = $v;
            }
        }
        return $data;
    }





}