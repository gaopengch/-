<?php

namespace Common\Controller;

use Think\AjaxPage;

/**
 * @description 返利控制器
 * Class RebateLogController
 * @package Common\Controller
 */
class RebateLogController
{
    private $data;

    /**
     * RebateLogController constructor.
     * @param $data
     */
    public function __construct( $data )
    {
        $this->setData( $data );
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData( $data )
    {
        $this->data = $data;

    }

    /** 实例化时候传入订单id
     * @description 添加返利记录方法
     */
    public function insert()
    {
        //获取订单号
        $orderId = $this->getData();
        $time    = time();
        //获取订单表信息
        $orderData = M( 'order' )->field( 'price_sum,user_id,rebate_status,goods_rebate,self_rebate,order_sn_id' )->where( [ 'id' => $orderId ])->find();
        if( $orderData[ 'rebate_status' ]){
            return true;
        }

        //判断支付成功返利
        $userModel=M('user');
        $rebataLogModel=M('rebate_log');
        $rebataLogModel->startTrans();
        $user_data = $userModel->where(['id'=>$orderData[ 'user_id' ]])->select()[0];
        $pid_data = $userModel->where(['id'=>$user_data['p_id']])->select()[0];
        //增加父级
        $data[ 'uid' ]          = $user_data['id'];
        $data[ 'pid' ]          = $user_data['p_id'];
        $data[ 'oid' ]          = $orderData[ 'order_sn_id'];
        $data[ 'total_price' ]  = $orderData[ 'price_sum' ];
        $data[ 'rebate_money' ] = $orderData[ 'goods_rebate'];
        $data[ 'time']          = $time;
        $data[ 'type']          = 1;
        file_put_contents('./Uploads/rebateLog.txt',"pc70saveData-------"."\r\n".\print_r($data,true)."\r\n",8);
        $res = $rebataLogModel->add( $data ) ;
        $status = M( 'user' )->where( [ 'id' => $data[ 'pid' ] ] )->setInc( 'rebate_money',$data[ 'rebate_money']);


        //增加本身

            $data[ 'type' ]          = 2;
            $data[ 'uid' ]           = $user_data['id'];
            $data[ 'pid' ]           = $user_data['id'];
            $data[ 'rebate_money' ]  = $orderData[ 'self_rebate'];
            $res2 = $rebataLogModel->add( $data ) ;
            $status2 = M( 'user' )->where( [ 'id' => $data[ 'pid' ] ] )->setInc( 'rebate_money',$data[ 'rebate_money']);

        
        //增加父级的父级
        if($pid_data['member_status']>2){
            $ppid_data = $userModel->where(['id'=>$pid_data['p_id']])->select()[0];
           $data[ 'type' ]          = 3;
           $data[ 'uid' ]           = $pid_data['id'];
           $data[ 'pid' ]           = $pid_data['p_id'];
           $data[ 'rebate_money' ]  = $orderData[ 'goods_rebate'] * $ppid_data['two_rebates']*0.01;
           $res3 = $rebataLogModel->add( $data ) ;
           $status3 = M( 'user' )->where( [ 'id' => $data[ 'pid' ] ] )->setInc( 'rebate_money',$data[ 'rebate_money']);
           
        }
        file_put_contents('./Uploads/rebateLog.txt',"pc94show-------".$res."\r\n".\print_r($orderId,true)."\r\n"."\r\n",8);
       if( $res ){
            //将user表的总金额增加
            M( 'order' )->where( [ 'id' => $orderId ] )->save( [ 'rebate_status' => 1 ] );
            $rebataLogModel->commit();
            return true;
        }
        $rebataLogModel->rollback();
        return false;

    }

    /**
     * @description 根据父级用户等级,获取分销比例
     * @param $uid 会员的id
     * @return array 用户的父级id与提成比例
     */
    private function getRebatePercent( $uid )
    {
        if( !( $array = S( 'rebate_ratio' ) ) ){
            $array = M( 'userGrade' )->getField( 'id,rebate_ratio' );
            S( 'rebate_ratio',$array,10 );
        }
        $pid            = M( 'user' )->where( [ 'id' => $uid ] )->getField( 'p_id' );
        $plv            = M( 'user' )->where( [ 'id' => $pid ] )->getField( 'member_status' );
        $rebate_percent = $array[ '$plv' ];
        //当返利比例为0时,返回空数组
        if( !( \boolval( $rebate_percent ) ) ){
            return [];
        }
        return [ 'pid' => $pid,'rebate_percent' => $array[ $plv ] ];
    }

    /**
     * @description 分页查询3
     * @return array
     */
    public function select()

    {
//        if(empty($post))? return false:$post;
        $limit = 10;//每页显示数目
        $where = $this->getWhere(); 
        $where = $where ? $where : '1=1';
        $count = M( 'rebate_log' )->where($where)->getField( 'COUNT(*)' );
        $post=$post['type'];
        if( $count == 0 ) return [];//无数据返回空数组
        $page = new AjaxPage( $count,$limit );
        $data = M( 'rebateLog' )->where( $where )->limit( $page->firstRow,$limit )->select();
        $page = $page->show();
        return [ 'data' => $data,'page' => $page ];

    }

    /**
     * @description 删除方法,传入(int)id 或者是数组
     * @return bool|int 成功返回删除的行数,失败返回false
     */
    public function del()
    {
        $id     = $this->getData();
        $status = M( 'rebate_log' )->setField('status',1);
        if( $status > 0 ){
            return (int)$status;
        }
        return false;

    }

    private function getWhere()
    {
        $where     = [];
        $whereArr  = [];
        $arr       = [];
        $startTime = false;
        $endTime   = false;
        $post      = $this->getData();
        if( $post[ 'startTime' ] ) $startTime = \strtotime( \trim( $post[ 'startTime' ] ) );
        if( $post[ 'endTime' ] ) $endTime = \strtotime( \trim( $post[ 'endTime' ] ) );

        //时间条件的几种情况
        //1.当开始时间跟结束时间都存在时
        if( $startTime && $endTime){
            //当开始时间大于结束时间,错误
            if( $startTime === $endTime ) $where[ 'time' ] = [ 'EQ',$startTime . ',' . $endTime ];
            if( $startTime < $endTime ) $where[ 'time' ] = [ 'BETWEEN',$startTime . ',' . $endTime ];
            //2.当时间只存在一个或都不存在的时候
        }else{
            if( $startTime && !$endTime ) $where[ 'time' ] = [ 'EGT',$startTime ];
            if( !$startTime && $endTime ) $where[ 'time' ] = [ 'ELT',$endTime ];
        }

        if( $post[ 'pid' ] ) $where[ 'pid' ] = $post[ 'pid' ];
         
        //根据手机号 昵称模糊查询
        //1.处理昵称
        $nickName = $post[ 'nickName' ] ? \trim( $post[ 'nickName' ] ) : '';
        if( $nickName !== '' ){
            $nickNameArr             = array_filter( \explode( ' ',$nickName ) );
            $nickNameStr             = \join( '%',$nickNameArr );
            $whereArr[ 'nick_name' ] = [ 'LIKE',':nick_name' ];
            $arr[ ':nick_name' ]     = '%' . $nickNameStr . '%';
        }
        //2.处理手机号
        $mobile = $post[ 'mobile' ] ? \trim( $post[ 'mobile' ] ) : '';
        if( $mobile !== '' ){
            $mobile               = array_filter( \explode( ' ',$mobile ) );
            $mobileStr            = \join( '%',$mobile );
            $whereArr[ 'mobile' ] = [ 'LIKE',':mobile' ];
            $arr[ ':mobile' ]     = '%' . $mobileStr . '%';

        }
        if( $whereArr !== [] ){
            $uid = M( 'user' )->where( $whereArr )->bind( $arr )->getField( 'id',true );
            if( \is_array( $uid ) && count( $uid ) > 1 ){
                $where[ 'uid' ] = [ 'IN',join( ',',$uid ) ];
            }elseif( \is_array( $uid ) && count( $uid ) === 1 ){
                $where[ 'uid' ] = [ 'EQ',$uid[ 0 ] ];
            }
        }
        if ($post['type']==1){
           $where['type']=1;
        }
         if ($post['type']==2){
           $where['type']=2;
        }
         if ($post['type']==3){
           $where['type']=3;
        }
        return $where;
 }




    /** 实例化时候传入订单id
     * @description 添加拼购订单团长返利记录方法     ---meng
     */
    public function insertHost($rebateMonery,$goodsAllMonery)
    {
        //获取订单号
        $orderId = $this->getData();

        //获取订单表信息
        $orderData = M( 'order' )->field( 'price_sum,user_id,rebate_status,goods_rebate,self_rebate,order_sn_id' )->where( [ 'id' => $orderId ] )->find();
        if($orderData['rebate_status']){
            return true;
        }
        $rebataLogModel=M('rebate_log');
        $rebataLogModel->startTrans();

        //增加本身
        $data[ 'type' ]          = 2;
        $data[ 'uid' ]           = $orderData['user_id'];
        $data[ 'pid' ]           = $orderData['user_id'];
        $data[ 'oid' ]           = $orderData['order_sn_id'];
        $data[ 'time' ]          = time();
        $data[ 'rebate_money' ]  = sprintf("%.2f",$rebateMonery / 100);
        $data[ 'total_price' ]   = $goodsAllMonery;
        $res = $rebataLogModel->add( $data ) ;
        $result = M( 'user' )->where( [ 'id' => $data[ 'pid' ] ] )->setInc( 'rebate_money',$data[ 'rebate_money']);

        file_put_contents('./Uploads/group/rebateLog.txt',"mobile91show-------".$res."\r\n".\print_r($orderId,true)."\r\n"."\r\n",8);
        if( $res && $result ){
            M( 'order' )->where( [ 'id' => $orderId ] )->save( [ 'rebate_status' => 1 ] );
            $rebataLogModel->commit();
            return true;
        }else{
            $rebataLogModel->rollback();
            return false;
        }

    }

}