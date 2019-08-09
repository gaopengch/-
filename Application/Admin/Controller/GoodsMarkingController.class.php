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

Class GoodsMarkingController extends AuthController{




    public function index()
    {
        $model = D("GoodsMarking");
        $count = $model->count();
        $page_setting = 10;
        $page = new Page($count, $page_setting);
        $page_show = $page->show();
        $rows = $model->limit($page->firstRow.','.$page->listRows)->order('id asc')->select();
        $this->assign('data',$rows);
        $this->assign('page_show',$page_show);
        $this->display();
    }

    /**
     * 添加
     */
    public function add()
    {
        $data = I('post.');
        $model = D("GoodsMarking");
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
     * 修改
     * @param $id
     */
    public function edit($id){
        $model = D("GoodsMarking");
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
        $model = D("GoodsMarking");
        if($model->delete($id)){
            $this->success("删除成功",U("index"));
        }else{
            $this->error("删除失败",U("index"));
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
            if(M("GoodsMarking")->save($result)){
                $this->ajaxReturn("no");
            }
        }else{
            $result = [
                'id'=>$id,
                'status'=>1,
            ];

            if(M("GoodsMarking")->save($result)){
                $this->ajaxReturn("yes");
            }
        }
    }



}