<?php

namespace Home\Controller;

use Common\Controller\RebateLogController;


/**
 * @description 我又好货控制器
 * Class IHaveGoodController
 * @package Home\Controller
 */
class IHaveGoodController extends BaseController
{
    public function index()
    {
        $this->display();
    }

    /**
     * @description 添加留言内容
     */
    public function add()
    {
        $post = I( 'post.' );
        foreach( $post as $k => $v ){
            if( empty( $v ) ){
                $this->ajaxReturnData( [],0,'参数不完整' );
            }
        }

        $this->checkStr( $post[ 'goods_title' ],1,50 ) || $this->ajaxReturnData( [],0,'商品名称非法字符,或长度大于50' );
        $this->checkStr( $post[ 'supplier' ],1,50 ) || $this->ajaxReturnData( [],0,'供货商非法,或长度大于50' );
        (bool)\preg_match( '/^(1\d{10})$|^(\d{4}-\d{7,8})$/',$post[ 'mobile' ] ) || $this->ajaxReturnData( [],0,'电话不合法,如固话请加区号-,例如0551-1236456' );
        (bool)\preg_match( '/^(\w)+(\.\w+)*@(\w)+((\.\w+)+)$/',$post[ 'email' ] ) || $this->ajaxReturnData( [],0,'请填写正确的邮箱' );

        $post[ 'uid' ]       = $_SESSION[ 'user_id' ] ? $_SESSION[ 'user_id' ] : $this->ajaxReturnData( [],0,'请先登录' );;



        $post[ 'goods_dec' ] = \addslashes( $post[ 'goods_dec' ] );
        $post[ 'time' ]      = \time();
        $status1             = M( 'IHaveGood' )->add( $post );

        if( (int)$status1 > 0 ) $this->ajaxReturnData( [],1,'留言成功' );


    }

    private function checkStr( $str,$num1,$num2 )
    {
        $pregStr = '/^[\x{4e00}-\x{9fa5}\da-zA-Z-_]{' . $num1 . ',' . $num2 . '}$/u';
        return (bool)\preg_match( $pregStr,$str );
    }

    public function test()
    {
        $uid = M('user')->getField('id',true);
        echo "<pre>";print_r( $uid );die;
    }
    public function mygoodshuo(){
        $this->display();
    }

    /**
     * 上传图片
     */
    public function uploadImage()
    {
        $rootPath = './'.UPLOAD_PATH;
        $savePath = '/IHaveGood/';
        $sub_name = ['date','Y-m-d'];
        $config = array(
            "rootPath" => $rootPath,
            "savePath" => $savePath,
            "saveName" => ['uniqid',''],
            "maxSize"  => 20000000, // 单位B
            "exts"     => explode(",", 'gif,png,jpg,jpeg'),
            "subName"  => $sub_name,
        );
        $ids = $this->uploadImageSave($config);
        $ids = empty($ids) ? [] : $ids;
        $this->ajaxReturn($ids);
    }

    /**
     * 处理图片
     */
    public function uploadImageSave($config)
    {
        $upload = new \Think\Upload($config);
        $info   = $upload->upload();

        if (is_array($info)) {
            $path  = '/'.trim(UPLOAD_PATH, '/');
            $ids   = [];
            foreach ($info as $vo) {
                $ids[] = $path.$vo['savepath'].$vo['savename'];
            }
        }
        return $ids;
    }

}