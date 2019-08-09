<?php
namespace Admin\Controller;


use Common\Controller\AuthController;
use Think\Page;

class GoodsExtendController extends AuthController{

    public function goodsList(){
        $fields = 'id,title,is_customs,p_id';
        $where ['is_customs'] = 1;
        $where ['p_id'] = 0;
        $num = M('goods')->field('id')->where($where)->select();

        $count = count($num);
        $page_setting = 10;
        $page = new Page($count,$page_setting);
        $page_show = $page->show();
        $data = M('goods')->field($fields)->where($where)->limit($page->firstRow.','.$page->listRows)->order("id desc")->select();
        $goods_id = array_column($data,'id');
        $extend = M('goods_extend')->where(['goods_id'=>['in',$goods_id]])->getField('goods_id,country_id,unit_id');
        if($extend){
            $country = M('country')->where(['customs_code'=>['in',array_column($extend,'country_id')]])->getField('customs_code,name');
            $unit = M('unit')->where(['customs_code'=>['in',array_column($extend,'unit_id')]])->getField('customs_code,unit_name');

            foreach($extend as &$v){
                $v['country_name'] = $country[$v['country_id']];
                $v['unit_name'] = $unit[$v['unit_id']];
            }
        }

        $this->assign('data',$data);
        $this->assign('extend',$extend);
        $this->assign('page_show',$page_show);
        $this->display();


    }

    public function moreSave(){
        $goods_id = I('post.checkbox/a');

        $data = M('goods')->field('id,title')->where(['id'=>['in',$goods_id],'p_id'=>0])->order("id desc")->select();
        $goods_id = array_column($data,'id');
        $extend = M('goods_extend')->where(['goods_id'=>['in',$goods_id]])->getField('goods_id,country_id,unit_id');
        if($extend){
            foreach($data as &$v){
                $v['country_id'] = $extend[$v['country_id']];
                $v['unit_id'] = $extend[$v['unit_id']];
            }
        }

        $country = M('country')->field('customs_code as country_id,name')->select();
        $unit = M('unit')->field('customs_code as unit_id,unit_name')->select();

        $this->assign('save_data',$data);
        $this->assign('countrys',$country);
        $this->assign('units',$unit);

        $this->display();
    }

    public function save_post(){
        $goods = I('post.goods/a');
        $goods_id = array_column($goods,'goods_id');
        $extend = M('goods_extend')->where(['goods_id'=>['in',$goods_id]])->select();
        $exist = array_column($extend,'goods_id');

        foreach($goods as $v){
            if(in_array($v['goods_id'],$exist)){
//                showData($v,1);
                $res = M('goods_extend')->where(['goods_id'=>$v['goods_id']])->save(['country_id'=>$v['country_id'],'unit_id'=>$v['unit_id']]);
            }else{
                $res = M('goods_extend')->add($v);
            }

        }
        $this->success('编辑成功',U('goodsList'));

    }


}
