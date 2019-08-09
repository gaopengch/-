<?php

namespace Admin\Controller;

use Common\Controller\AuthController;
use Think\AjaxPage;


/**
 * @description 我又好货控制器
 * Class IHaveGoodController
 * @package Admin\Controller
 */
class IHaveGoodController extends AuthController
{
    public function index()
    {
        $this->display();
    }

    public function ajaxGetData()
    {
        $limit = 10;
        $post = I( 'post.' );
        $where = $this->getWhere($post);
        $count = M( 'IHaveGood' )->where( $where )->getField( 'COUNT(*)' ) or $this->ajaxReturnData( [ 'data' => [],'page' => [] ],0,'暂无数据' );

        $page = new AjaxPage( $count,$limit );
        $data = M( 'IHaveGood' )->where( $where )->limit( $page->firstRow,$limit )->order( 'id desc' )->select();
        $page = $page->show();

        foreach($data as $k=>$v){
            $data[$k]['nick_name'] = M('user')->where(['id'=>$v['uid']])->getField('nick_name');
            $data[$k]['time']      = date('Y-m-d H:i:s',$v['time']);
        }

        $this->ajaxReturnData( [ 'data' => $data,'page' => $page ] );
    }

    private function getWhere($post)
    {
        $where = [];
        if( $post[ 'timeStart' ] && $post[ 'timeEnd' ] ){
            $where[ 'time' ] = [ 'BETWEEN',\strtotime( $post[ 'timeStart' ] ) . ',' . \strtotime( $post[ 'timeEnd' ] ) ];
        }elseif( $post[ 'timeStart' ] && !$post[ 'timeEnd' ] ){
            $where[ 'time' ] = [ 'EGT',\strtotime( $post[ 'timeStart' ] ) ];
        }elseif( !$post[ 'timeStart' ] && $post[ 'timeEnd' ] ){
            $where[ 'time' ] = [ 'ELT',\strtotime( $post[ 'timeEnd' ] ) ];
        }
        return $where;
    }

    public function del()
    {
        $where = [];
        $id    = I( 'get.id' );
        if( !is_array( $id ) ){
            $where = [ 'id' => (int)$id ];
        }else{
            sort( $id );
            $str = '';
            foreach( $id as $v ){
               $str .= $v . ',';
            }
            $where['id'] = ['IN',rtrim( $str ,',')] ;
        }
        $status = M( 'IHaveGood' )->where( $where )->delete();

        if( $status ){
            $this->ajaxReturnData( [],1,'成功删除 ' . $status . ' 条数据' );
        }
        $this->ajaxReturnData( [],0,'删除失败,请稍后再试' );
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

        (bool)\preg_match( '/^\d{3}-\d{8}|\d{4}-\d{7,8}$/',$post[ 'mobile' ] ) || $this->ajaxReturnData( [],0,'电话不合法,如固话请加区号-,例如0551-1236456' );

        (bool)\preg_match( '/^[A-Za-z0-9\u4e00-\u9fa5]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/',$post[ 'mobile' ] ) || $this->ajaxReturnData( [],0,'邮箱格式不正确,请输入正确邮箱' );

        $post[ 'goods_dec' ] = \addslashes( $post[ 'goods_dec' ] );
        $post[ 'uid' ]       = $_SESSION[ 'user_id' ];
        $status1             = M( 'IHaveGood' )->add( $post );
        if( (int)$status1 > 0 ) $this->ajaxReturnData( [],1,'留言成功' );


    }

    private function checkStr( $str,$num1,$num2 )
    {
        $pregStr = '/^[\x{4e00}-\x{9fa5}\da-zA-Z-_]{' . $num1 . ',' . $num2 . '}$/u';
        return (bool)\preg_match( $pregStr,$str );
    }


    /**
     * 导出订单excel
     */
    public function expGoods()
    {
        $cond = [];
        $xlsName = "iHaveGoods";
        $xlsCell = array(
            array('id', '序号'),
            array('goods_title', '商品名称'),
            array('supplier', '供货商名称'),
            array('mobile', '手机'),
            array('goods_dec', '商品描述'),
            array('time', '提交日期'),
            array('uid', '用户账号'),
            array('email', '邮箱'),
        );
        $xlsModel = M('i_have_good');

        $get = $_GET;
        $cond = $this->getWhere($get);
        $xlsData = $xlsModel
            ->field('id,goods_title,supplier,mobile,goods_dec,time,uid,email')
            ->where($cond)
            ->order('id desc')
            ->select();
        foreach($xlsData as &$v){
            $v['uid'] = M('user')->where(['id'=>$v['uid']])->getField('user_name');
        }

        $this->exportExcel($xlsName,$xlsCell,$xlsData);

    }

    /**
     * @desc  生成Excel
     * @param unknown $expTitle
     * @param unknown $expCellName
     * @param unknown $expTableData
     */
    public function exportExcel($expTitle,$expCellName,$expTableData) {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);
        $fileName = $expTitle.date('_YmdHis');
        $cellNum = count($expCellName);
        $dateNum = count($expTableData);
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new \PHPExcel;
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:'.$cellName[$cellNum-1].'1');
        for($i = 0; $i<$cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
            $objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$i])->setAutoSize(true);
        }
        for($i = 0; $i<$dateNum; $i++) {
            for($j = 0; $j<$cellNum; $j++) {
                if(in_array($expCellName[$j][0], ['order_sn_id', 'mobile'])) {
                    $objPHPExcel->getActiveSheet()->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]].' ');
                }else {
                    $objPHPExcel->getActiveSheet()->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
                }
            }
        }


        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /*
     * 详情页
     */
    public function detail(){
        $id = I('get.id');
        $data = M( 'IHaveGood' )->where('id = '.$id)->find();
        $img = explode(',',$data['img']);
        $img = array_filter($img);
        $this->assign('imgs',$img);
        $this->assign('data',$data);
        $this->display();
    }


}