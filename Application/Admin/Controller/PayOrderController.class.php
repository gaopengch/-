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
use Think\Page;


class PayOrderController extends AuthController{

    public function payOrderList(){
        $post = $_GET;

        $where = $this->getWhere($post);

        // 获取支付订单

        $count = M( 'pay_order' )->where($where)->getField( 'COUNT(*)' );
        $page   = new Page( $count,20 );


        $data = M('pay_order')->where($where)->order('id desc')->limit( $page->firstRow.','.$page->listRows )->select();

        $img = 'http://'.$_SERVER['HTTP_HOST'].'/Public/Admin/img/payConstrust.png';
        //初始化页数排序
        $show = $page->show();
        $this->assign('data',$data);
        $this->assign('img',$img);
        $this->assign('page',$show);


        $this->display();
    }

    //获取where条件
    public function getWhere($post){
        $where = [];
        if($post['id']){
            $where['id'] = ['like','%'.$post['id'].'%'];
        }
        if($post['pay_order_id']){
            $where['pay_order_id'] = ['like','%'.$post['pay_order_id'].'%'];
        }
        //状态
        if( $post['status'] != '' ){
            $where[ 'status' ] = $post[ 'status' ];
        }
        //商品订单ID
        if( $post['order_id_str'] != '' ){
            $where[ 'order_id_str' ] = ['like','%'.$post['order_id_str'].'%'];
        }
        if( $where === [] ) {
            $where = '1=1';
        }else{
            $_GET[ 'p' ] = 1;
        };

        return $where;
    }



}