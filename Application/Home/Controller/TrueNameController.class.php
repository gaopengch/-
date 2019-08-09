<?php


namespace Home\Controller;

//个人资料
class TrueNameController extends BaseController{
    //判断是否登录
     public function __construct()
    {
        parent::__construct();

        $this->isLogin();
    }


    //实名认证
    public function true_name(){
        $active = I('active');
        $this->assign('active',$active);
        $user_id = $_SESSION['user_id'];
        $data = M('true_name')->where(['user_id'=>$user_id])->find();

        $this->assign('data',$data);

        $this->display();
    }

    public function save_true_name(){
        $data = I('post.');
        $user_id = $_SESSION['user_id'];
        $data['user_id'] = $user_id;
        $id = M('true_name')->where(['user_id'=>$user_id])->find();
        if($id){
            $res = M('true_name')->where(['user_id'=>$user_id])->save($data);
        }else{
            $res = M('true_name')->add($data);
        }
        if($res){
            M('user')->where(['id'=>$user_id])->setField('is_true',1);
            $this->success('添加成功',U('UserSet/security'));
        }else{
            $this->error('添加失败',U('true_name'));
        }

    }

    public function addImg(){
        $img = $_FILES;

        if(($img["img"]["type"]=="image/png"||$img["img"]["type"]=="image/jpeg"||$img["img"]["type"]=="image/jpg")&&$img["img"]["size"]<1024000){
            $imgname = $img['img']['name'];
            $tmp = $img['img']['tmp_name'];
            $server = $_SERVER['DOCUMENT_ROOT'];
            $path2 = "/Uploads/idCard/".date('Y-m').'/';
            $path = $server.$path2;
            if(!file_exists($path)){
                mkdir($path);
            }
            $imgpath = $path.$imgname;
            move_uploaded_file($tmp,$imgpath);
            $this->ajaxReturnData($path2.$imgname,1);
        }
        $this->ajaxReturnData('',0,'操作失败');

    }

}
