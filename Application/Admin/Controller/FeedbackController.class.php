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
use Common\Model\GoodsConsultationModel;
use Common\Tool\Tool;
use Admin\Model\GoodsModel;
use Admin\Model\UserModel;
use Think\AjaxPage;
use Think\Auth;
use Think\Page;

//后台管理员
class FeedbackController extends AuthController {

    public function lists ()
    {
        $model = BaseModel::getInstance(GoodsConsultationModel::class);

        Tool::isSetDefaultValue($_GET, array('p' => 1));

        $this->model = GoodsConsultationModel::class;
        $this->display();
    }

    /**
     * ajax 获取商品咨询
     */
    public function ajaxGetFeedback ()
    {
        $where = ['status' => 0 ];
        $count = M('app_feedback')->where($where)->getField( 'COUNT(*)' );
        if( (int)$count !== 0 ){
            $page = new Page($count,20);
            $show = $page->show();
            $data = M('app_feedback')->where($where)->order('feedback_id desc')->limit($page->firstRow.','.$page->listRows )->select();
            $uid_str = '';
            foreach($data as $v){
                $uid_str .= $v['user_id'].',';
            }
            $uid_str = rtrim($uid_str,',');
            $users = M('user')->where(['id'=> [ 'IN',$uid_str ]])->getField('id,user_name,mobile');

            foreach($data as $k=>$vo){
                $data[$k]['user_name'] =$users[$vo['user_id']]['user_name'] ;
            }
            $FeedbackType = C('FeedbackType');


            $this->assign('data',$data);
            $this->assign('page',$show);
            $this->assign('FeedbackType',$FeedbackType);

        }


        $this->display();

    }

    public function deleteFeedback(){
        Tool::checkPost($_POST, array('is_numeric' => array('id')), true, array('id')) ? true : $this->ajaxReturnData(null, 0, '删除失败');
        $where = ['feedback_id' =>$_POST['id']];

        $status =M('app_feedback')->where($where)->setField('status',1);
        if($status){
            $this->updateClient($status, '删除成功');
        }{
            $this->updateClient(0, '删除失败');
        }

    }
	




}




