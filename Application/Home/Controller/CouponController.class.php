<?php
// +----------------------------------------------------------------------
// | OnlineRetailers [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2003-2023 www.yisu.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
// +----------------------------------------------------------------------
// | Author: 王强 <opjklu@126.com>
// +----------------------------------------------------------------------

namespace Home\Controller;
use Home\Model\CouponModel;
/**
 * 优惠券 控制器
 */

class CouponController extends BaseController{

    /**
     * 领取优惠卷
     */
    public function getCoupon(){
        $id = I('post.coupon_id');
        $user_id = $_SESSION['user_id'];
        if(!$user_id){$this->ajaxReturnData('',0,'未登录');}
        if(!$id){$this->ajaxReturnData('',0,'参数错误');}

        $CouponModel = new CouponModel();
        $data = $CouponModel->getCoupon($id,$user_id);
        if($data == 2){
            $this->ajaxReturnData($data,0,'已到领取上限');
        }
        if($data){
            $this->ajaxReturnData($data,1,'获取成功');
        }else{
            $this->ajaxReturnData($data,0,'获取失败，暂无优惠卷');
        }
    }



}