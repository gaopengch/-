<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/29
 * Time: 11:35
 */
namespace Admin\Controller;
use Think\Controller;
use Think\Page;
use Common\Controller\AuthController;

Class SupplierController extends AuthController{

    public function index()
    {
        $name = I('post.name');
        if($name){
            $where ['name'] = ["LIKE", '%' . $name . '%'];
        }else{
            $where = '1=1';
        }
        $model = M("supplier");
        $count = $model->where($where)->count();
        $page_setting = 10;
        $page = new Page($count, $page_setting);
        $page_show = $page->show();
        $rows = $model->where($where)->limit($page->firstRow.','.$page->listRows)->order("id desc")->select();
        $this->assign('data',$rows);
        $this->assign('page_show',$page_show);
        $this->display();
    }

    /**
     * 添加供应商
     */
    public function add()
    {
        $data = I('post.');
        $model = M("supplier");
        if(IS_POST){
            $data['create_time'] = time();
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
     * 供应商修改
     * @param $id
     */
    public function edit($id){
        $model = M("supplier");
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
    /**
     * 供应商删除
     * @param $id
     */
    public function remove($id){
        $model = M("supplier");
        if($model->where(['id'=>$id])->setField('status',0)){
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
            if(M("supplier")->save($result)){
                $this->ajaxReturn("no");
            }
        }else{
            $result = [
                'id'=>$id,
                'status'=>1,
            ];

            if(M("supplier")->save($result)){
                $this->ajaxReturn("yes");
            }
        }
    }
    /**
     * 供应商-商品退货地址列表
     * @param $id
     */
    public function reAddressList($id){
        $model = M("supplier_re_address");
        $count = $model->where('supplier_id='.$id)->count();
        $page_setting = 10;
        $page = new Page($count, $page_setting);
        $page_show = $page->show();
        $rows = $model->where('supplier_id='.$id)->limit($page->firstRow.','.$page->listRows)->order("id desc")->select();
        $supplier = M('supplier')->field('id,name')->where('id='.$id)->find();
        $this->assign('data',$rows);
        $this->assign('supplier',$supplier);
        $this->assign('page_show',$page_show);
        $this->display();
    }
    /**
     * 添加供应商-商品退货地址
     */
    public function addAddres()
    {
        $data = I('post.');
        $model = M("supplier_re_address");

        if(IS_POST){
            $data['create_time'] = time();
            if($model->create($data) === false){
                $this->error("添加失败",U("add"));
            }
            if($model->add($data) === false){
                $this->error("添加失败",U("add"));
            }else{
                $this->success("添加成功",U('reAddressList',['id'=>$data['supplier_id']]));
            }
        }else{
            $supplier_id = I('get.supplier_id/d');
            $row['supplier_id'] = $supplier_id;
            $this->assign('row',$row);
            $this->display();
        }

    }

    /**
     * 供应商-商品退货地址修改
     * @param $id
     */
    public function editAddres($id){
        $model = M("supplier_re_address");
        if(IS_POST){
            $data = I('post.');
            if($model->create($data) === false){
                $this->error("添加失败",U("editAddres",'id='.$data['id']));
            }
            if($model->save($data) === false){
                $this->error("添加失败",U("editAddres",'id='.$data['id']));
            }else{
                $this->success("修改成功",U('reAddressList',['id'=>$data['supplier_id']]));
            }
        }else{
            $row = $model->find($id);
            $this->assign('row',$row);
            $this->display("addAddres");
        }
    }
    /**
     * 供应商-商品退货地址删除
     * @param $id
     */
    public function removeAddres(){
        $id = I('get.id/d');
        $supplier_id = I('get.supplier_id/d');
        $model = M("supplier_re_address");
        if($model->where(['id'=>$id])->delete()){
            $this->success("删除成功",U("reAddressList",['id'=>$supplier_id]));
        }else{
            $this->error("删除失败",U("reAddressList"),['id'=>$supplier_id]);
        }
    }

    /**
     * ajax 改变状态
     * 手动切换改变是否显示
     */
    public function changAddressStatus(){
        $id = I("id");
        $data_flag = I("data_flag");
        if(	$data_flag == "true"){
            $result = [
                'id'=>$id,
                'status'=>0,
            ];
            if(M("supplier_re_address")->save($result)){
                $this->ajaxReturn("no");
            }
        }else{
            $result = [
                'id'=>$id,
                'status'=>1,
            ];

            if(M("supplier_re_address")->save($result)){
                $this->ajaxReturn("yes");
            }
        }
    }

}


