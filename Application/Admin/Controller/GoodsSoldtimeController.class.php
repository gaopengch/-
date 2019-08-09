<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/22
 * Time: 17:44
 */

namespace Admin\Controller;

use Common\Controller\AuthController;
use Think\Controller;
use Think\Page;

Class GoodsSoldtimeController extends AuthController{




    public function index()
    {
        $model = D("GoodsSoldtime");
        $count = $model->count();
        $page_setting = 10;
        $page = new Page($count, $page_setting);
        $page_show = $page->show();
        $rows = $model->limit($page->firstRow.','.$page->listRows)->order("sort asc")->select();
        $this->assign('data',$rows);
        $this->assign('page_show',$page_show);
        $this->display();
    }

    /**
     * 添加清仓时段
     */
    public function add()
    {
        $data = I('post.');
        $model = D("GoodsSoldtime");
        if(IS_POST){
            if($model->create($data) === false){
                $this->error("添加失败",U("add"));
            }
            if($model->add($data) === false){
                $this->error("添加失败",U("add"));
            }else{
                $this->success("添加成功",U("index"));
            }
        }else{
            $this->display();
        }

    }

    /**
     * 清仓时段修改
     * @param $id
     */
    public function edit($id){
        $model = D("GoodsSoldtime");
        if(IS_POST){
            $data = I('post.');
            if($model->create($data) === false){
                $this->error("添加失败",U("edit",'id='.$data['id']));
            }
            if($model->save($data) === false){
//
                $this->error("添加失败",U("edit",'id='.$data['id']));
            }else{
                $this->success("修改成功",U("index"));
            }
        }else{
            $row = $model->find($id);
            $this->assign('row',$row);
            $this->display("add");
        }
    }
    public function remove($id){
        $model = D("GoodsSoldtime");
        if($model->delete($id)){
            $this->success("删除成功",U("index"));
        }else{
            $this->error("删除失败",U("index"));
        }
    }

    /**
     * ajax改变排序的值
     * 输入框输入值来改变排序的值
     */
    public function changeSort(){
        $id = I("id");
        $sort = I("sort");
        $arr = [
            'id'=>$id,
            'sort'=>$sort
        ];
        if(M("GoodsSoldtime")->save($arr)){
            $this->ajaxReturn(['msg'=>1]);
        }else{
            $this->ajaxReturn(['msg'=>0]);
        }

    }

    /**
     * ajax 改变状态
     * 手动切换改变是否显示
     */
    public function changStatus(){
        $id = I("id");
        $data_flag = I("data_flag");
        if(	$data_flag == "true"){
            $result = [
                'id'=>$id,
                'status'=>0,
            ];
            if(M("GoodsSoldtime")->save($result)){
                $this->ajaxReturn("no");
            }
        }else{
            $result = [
                'id'=>$id,
                'status'=>1,
            ];

            if(M("GoodsSoldtime")->save($result)){
                $this->ajaxReturn("yes");
            }
        }
    }



}