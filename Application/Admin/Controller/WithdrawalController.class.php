<?php

namespace Admin\Controller;

use Common\Controller\AuthController;
use Think\AjaxPage;

class WithdrawalController extends AuthController
{
    private $userInfo = [];//通过手机号码搜索的满足条件的用户信息

    public function index()
    {
        $this->display();
    }

    /**
     * @description ajax返回 所搜数据数据
     */
    public function ajaxSelect()
    {
        $limit = 10;
        $post = I('post.');
        $where = $this->getWhere($post);
        $count = M( 'withdrawal' )->where( $where )->getField( 'COUNT(*)' );
        if( !$count ){
            $this->ajaxReturnData( [],0,'暂无数据' );
        }
        $page = new AjaxPage( $count,$limit );
        $data = M( 'withdrawal' )->where( $where )->limit( $page->firstRow,$limit )->order( 'id desc' )->select();
        if( $this->userInfo && $data ){
            $data[ 'nick_name' ] = $this->userInfo[ 0 ][ 'nick_name' ];
            $data[ 'mobile' ]    = $this->userInfo[ 0 ][ 'mobile' ];
            $this->ajaxReturnData( $data );
        }
        $array = [
            '-1' => '<span style="color: grey">未通过</span>',
            '0'  => '<span style="color: blue">待审批</span>',
            '1'  => '<span style="color: red">待打款</span>',
            '2'  => '<span style="color: green">已打款</span>'
        ];

        $str = '';
        foreach( $data as $v ){
            $str .= $v[ 'uid' ] . ',';
        }
        //组装用户信息
        $userInfo = M( 'user' )->where( [ 'id' => [ 'IN',\rtrim( $str,',' ) ] ] )->getField( 'id,user_name,mobile' );

        foreach( $data as $k => $v2 ){
            $data[ $k ][ 'user_name' ]  = $userInfo[ $v2[ 'uid' ] ][ 'user_name' ];
            $data[ $k ][ 'mobile' ]     = $userInfo[ $v2[ 'uid' ] ][ 'mobile' ];
            $data[ $k ][ 'last_time' ]  = \date( 'Y-m-d H:i:s',$v2[ 'last_time' ] );
            $data[ $k ][ 'status_str' ] = $array[ $v2[ 'status' ] ];
            $data[ $k ][ 'status' ]     = (int)$v2[ 'status' ];
            $data[ $k ][ 'total' ]     = $v2['money'] + $v2['bestir'];

        }
        $this->ajaxReturnData( [ 'data' => $data,'page' => $page->show() ] );
    }

    private function getWhere($post)
    {
        $where = [];
        //时间
        if( !empty( $post[ 'timeStart' ]) && !empty( $post[ 'timeEnd' ]) ){
            $where[ 'time' ] = [ 'BETWEEN',\strtotime( $post[ 'timeStart' ] ) . ',' . \strtotime( $post[ 'timeEnd' ] ) ];
        }elseif( empty($post[ 'timeStart' ]) && !empty( $post[ 'timeEnd' ]) ){
            $where[ 'time' ] = [ 'EGT',\strtotime( $post[ 'timeStart' ] ) ];
        }elseif( !empty($post[ 'timeStart' ]) && empty( $post[ 'timeEnd' ]) ){
            $where[ 'time' ] = [ 'ELT',\strtotime( $post[ 'timeEnd' ] ) ];
        }
        //状态
        if( (int)$post[ 'status' ] !== 999 ){
            $where[ 'status' ] = (int)$post[ 'status' ];
        }
        //手机号
        if( \preg_match( '/^1\d{10}/',$post[ 'mobile' ] ) ){
            $this->userInfo = M( 'user' )->field( 'id,mobile,nick_name' )->where( [ 'mobile' => $post[ 'mobile' ] ] )->select();
            if( !$this->userInfo ){
                $this->ajaxReturnData( [],0,'请输入正确的手机号' );
            }
            $where[ 'uid' ] = $this->userInfo[ 0 ][ 'id' ];
        }
        if( $where === [] ) $where = '1=1';//方便缓存数据
        if( $data = S( \json_encode( $where ) ) ){
            $this->ajaxReturnData( $data,1 );
        }
        return $where;
    }

    /**
     * 全部导出excel
     * 当前页导出execl
     *
     * 通过当前页数（p）来进行判断是全部导出还是当前页导出
     * 1.如果有p参数，就是当前页导出、
     * 2.如果没有p参数，就是全部导出
     */
    public function expOut()
    {
        $xlsName = "Withdrawal";
        $xlsCell = array(
            array('id', 'id'),
            array('drawal_id', '申请编号'),
            array('uid', '申请人ID'),
            array('user_name', '申请人账户名'),
            array('bank_num', '银行卡号'),
            array('bank_name', '银行名称'),
            array('bank_user', '卡户名'),
            array('ali_account', '支付宝账户'),
            array('money', '申请金额'),
            array('bestir', '激励金额'),
            array('total', '总金额'),
            array('status', '状态'),
            array('result', '审核结果'),
            array('opinion', '意见'),
            array('admin', '审批人'),
            array('create_time', '申请日期'),
            array('last_time', '最后修改时间'),
            array('mobile', '手机号')
        );
        $post = I('get.');
        $where = $this->getWhere($post);

        $data  = M('Withdrawal')->where($where)->select();
        if( empty($data[0] )){
            $this->error('无数据可导出', U('Withdrawal/index'));
        }
        //组装用户信息
        $str = '';
        foreach( $data as $v ){
            $str .= $v[ 'uid' ] . ',';
        }
        $admin_name = M('admin')->where(['id'=>session('aid')])->getField('account');
        $userInfo = M( 'user' )->where( [ 'id' => [ 'IN',\rtrim( $str,',' ) ] ] )->getField( 'id,user_name,mobile' );
        foreach( $data as $k => $v2 ){
            $data[ $k ][ 'user_name' ]  = $userInfo[ $v2[ 'uid' ] ][ 'user_name' ];
            $data[ $k ][ 'mobile' ]     = $userInfo[ $v2[ 'uid' ] ][ 'mobile' ];
            $data[ $k ][ 'last_time' ]  = \date( 'Y-m-d H:i:s',$v2[ 'last_time' ] );
            $data[ $k ][ 'create_time' ]  = \date( 'Y-m-d H:i:s',$v2[ 'create_time' ] );
            $data[ $k ][ 'total' ]  = $v2['money'] + $v2['bestir'];
            $data[ $k ][ 'admin' ]  = $admin_name;
        }
        unset($v);
        unset($v2);
        $this->exportExcel($xlsName, $xlsCell, $data);
    }

    public function exportExcel($expTitle, $expCellName, $expTableData)
    {
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle); // 文件名称
        $fileName = $expTitle . date('_YmdHis'); // or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new \PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');

        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1'); //
        for ($i = 0; $i < $cellNum; $i ++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
        }
        for ($i = 0; $i < $dataNum; $i ++) {
            for ($j = 0; $j < $cellNum; $j ++) {
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }

        ob_end_clean(); // 清除缓冲区,避免乱码
        header('Content-Type: application/vnd.ms-excel');
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls"); // attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        $objWriter->save('php://output');
        exit();
    }

    /**
     * Excel导入订单快递公司名，快递单号，发货时间
     */
    public function expIn() {
        $upload = new \Think\Upload();

        $upload->maxSize = 3145728;
        $upload->exts = array('xls');
        $upload->rootPath = "./Uploads/Table/";
        $upload->savePath = '';
        $info = $upload->uploadOne($_FILES['import-orders']);
        if(!$info) {
            $this->error('无文件上传', U('Withdrawal/index'));
        } else {
            $file = $upload->rootPath.$info['savepath'].$info['savename'];
            if(file_exists($file)) {
                vendor("PHPExcel.PHPExcel");
                vendor("PHPExcel.PHPExcel.IOFactory");
                $objReader = \PHPExcel_IOFactory::createReader('Excel5');
                $objPHPExcel = $objReader->load($file, 'utf-8');
                $sheet = $objPHPExcel->getSheet(0);
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $pre_row = 3;
                for($i = $pre_row; $i < $highestRow+1; $i++) {
                    $data[$i-$pre_row]['id'] = $objPHPExcel->getActiveSheet()->getCell('A'. $i)->getValue();
                    $data[$i-$pre_row]['status'] = $objPHPExcel->getActiveSheet()->getCell('L'.$i)->getValue();
                    $data[$i-$pre_row]['result'] = $objPHPExcel->getActiveSheet()->getCell('M'.$i)->getValue();
                    $data[$i-$pre_row]['opinion'] = $objPHPExcel->getActiveSheet()->getCell('N'.$i)->getValue();
                    $data[$i-$pre_row]['last_time'] = time();
//                    $admin  = $objPHPExcel->getActiveSheet()->getCell('O'.$i)->getValue();
//                    $aid    = M('admin')->where(['account'=>$admin])->getField('id');
                    $data[$i-$pre_row]['admin'] = $objPHPExcel->getActiveSheet()->getCell('O'.$i)->getValue();
                }

                $withdrawalModel = M('Withdrawal');
                $withdrawalModel->startTrans();//开启事务

                $sql = "UPDATE db_withdrawal SET status = %d, result = '%s', opinion = '%s',admin = '%s',last_time = %d where id = %d ";
                foreach($data as $v) {
                    $update[] = $withdrawalModel->execute($sql, array($v['status'], $v['result'], $v['opinion'], $v['admin'],$v['last_time'],$v['id']));
                }
                if(isset($update)){
                    $withdrawalModel->commit();//成功则提交
                    $this->success('导入成功',U('Withdrawal/index'));
                }else{
                    $withdrawalModel->rollback();//不成功，则回滚
                    $this->error('导入失败', U('Withdrawal/index'));
                }
            } else {
                $this->error('导入失败');
            }
        }
    }

    //单条记录处理数据
    public function toExamine(){
        $post = I('post.');
        $data [ 'status'] = $post['status'] +1;
        $data ['last_time'] = time();
//        $res = M('withdrawal')->where(['id'=>$post['id']])->setField('status',$status);
        $res = M('withdrawal')->where(['id'=>$post['id']])->save($data);
        if($res){
            $this->ajaxReturnData( $data [ 'status'],1 );
        }else{
            $this->ajaxReturnData( $data [ 'status'],0 );
        }


    }




}