<?php

namespace Home\Controller;

use Common\Controller\RebateLogController;
use Common\Controller\WithdrawalController;
use Common\Tool\Tool;
use Think\AjaxPage;
use Think\Page;
use Common\Tool\QRcode;

class DistributionController extends BaseController
{
    private $user_id;
    private $listRows;
    private $where = [];

    public function __construct()
    {
        parent::__construct();
        $this->setUserId( $_SESSION[ 'user_id' ] );
        $this->setListRows( 10 );//设置每页显示数目
        $this->setWhere();
    }

    /**
     * @description 我的返利
     */
    public function index()
    {
        $active = I( 'active' );
        $this->assign( 'active',$active );
        $uid = $this->getUserId();
        //会员我的返利
        $member_status=M('user')->where(['id'=>$uid])->getField('member_status');
        if($member_status<=1) {
           $isStandard='none';
       }
        //$data=M('rebate_log')->where("uid='$uid' and type=2")->select();
        $data=  M('rebate_log')->find();
       //总返利
        $user  = M('user')->Field( 'rebate_money,bestir_pre')->where( [ 'id' => $this->getUserId() ])->select()[0];
        //正在提现
        $now = M('withdrawal')->where(['uid'=>$this->getUserId(),'status'=>['in',('0,1')]])->getField('sum(money)');
        //可提现
        $balance = $user['rebate_money'] - M('withdrawal')->where(['uid'=>$this->getUserId(),'status'=>['in',('0,1,2')]])->getField('sum(money)');

        //本月返利
        $start=strtotime(date('Y-m-01 00:00:00'));
        $end = strtotime(date('Y-m-d H:i:s'));
        $where['time'] = array('between',array($start,$end));
        $where['pid'] = $this->getUserId();
        $where['status'] = '0';
// $month = M('rebate_log')->Field('sum(rebate_money) as sum')->where($where)->select();
        //上月返利
        $lastmonth_start=mktime(0,0,0,date('m')-1,1,date('Y'));
        $lastmonth_end=mktime(0,0,0,date('m'),1,date('Y'))-24*3600;
        $cond['time'] = array('between',array($lastmonth_start,$lastmonth_end));
        $cond['pid'] = $this->getUserId();
        $cond['status'] = '0';
        $lastmonth = M('rebate_log')->where($cond)->getField('sum(rebate_money)');
        $month = M('rebate_log')->where($where)->getField('sum(rebate_money)');
        //会员返利总额
        $vip_rebate=M('rebate_log')->where(['pid' =>$uid,'type'=>1,'status'=>0] )->getField('sum(rebate_money)');
        //自购返利总额
        $self_rebate=M('rebate_log')->where(['pid' =>$uid,'type'=>2,'status'=>0] )->getField('sum(rebate_money)');
        //经销商返利
       $forward_rebate=M('rebate_log')->where(['pid' =>$uid,'type'=>3,'status'=>0] )->getField('sum(rebate_money)');
        $user['balance'] = $balance;
        S('user_rebate'.$uid,$user);
        $data['total'] = $user['rebate_money'];
        $data['now'] = $now;
        $data['balance'] = $balance;
        $data['bestir_pre'] = $user['bestir_pre'];
        $data['month'] = $month;
        $data['lastmonth'] = $lastmonth;
        $data['vip_rebate']=$vip_rebate;
        $data['self_rebate']=$self_rebate;
        $data['forward_rebate']=$forward_rebate;
        $data['member_status']=$member_status;
       foreach($data as &$v){
            if($v == ''){
                $v = 0;
            }
        }
        $this->assign('data',$data);
        $this->display();
    }

    public function index_ajax()
    {
        //添加搜索条件
        $post          = I('post.data/a');
       
        $post[ 'pid' ] = $_SESSION[ 'user_id'] ? $_SESSION[ 'user_id' ] : E( '未登录' );
        $Obj           = new RebateLogController( $post );
        $data          = $Obj->select();
       //$data=$Obj->where("uid='$uid' and type=2")->select();
        if( $data === [] ) $this->ajaxReturnData([],0,'暂无数据');
        foreach($data['data'] as &$v){
            $v['time'] = date('Y-m-d h:i:s',$v['time']);
        }
        $data = $this->addUserInfo($data);
        $this->ajaxReturnData( $data );
    }
    
    private function addUserInfo( array $arr )
    {
        $idArr = [];
        foreach( $arr[ 'data' ] as $v ){
            if( \in_array( $v[ 'uid' ],$idArr ) ) continue;
            $idArr[] = $v[ 'uid' ];
        }
        if( $ids = \join( ',',$idArr ) ){

            $userInfo = M( 'user' )->where( [ 'id' => [ 'IN',$ids ] ] )->getField( 'id,nick_name,mobile' );

            foreach( $arr[ 'data' ] as $k => $value ){
                $arr[ 'data' ][ $k ][ 'nick_name' ] = $userInfo[ $value[ 'uid' ] ][ 'nick_name' ];
                $arr[ 'data' ][ $k ][ 'mobile' ]    = $userInfo[ $value[ 'uid' ] ][ 'mobile' ];
            }
        }
        return $arr;
    }

    /**
     * 我的团队
     */
    public function Myteam()
    {
        $active = I( 'active' );
        $this->assign('active',$active);
        $count = M( 'user' )->where( [ 'p_id' => $this->getUserId()] )->getField( 'COUNT(*)' );

        if( (int)$count !== 0 ){
            $page   = new Page( $count,$this->getListRows() );
            $show = $page->show();
            $info   = M( 'user' )->field( 'id,mobile,create_time,user_name,nick_name' )->where( [ 'p_id' => $this->getUserId()])->limit($page->firstRow.','.$page->listRows )->order('create_time desc')->select();
            //查询消费总额
            $str = '';
            foreach( $info as $k1 => $v ){
                $info[ $k1 ][ 'create_time' ] = \date( 'Y-m-d H:i:s',$v[ 'create_time' ] );
                $str                          .= $v[ 'id' ] . ',';
            }
            $moneySum = M( 'order' )->where( [ 'user_id' => [ 'IN',\rtrim( $str,',' ) ] ,'order_status' => [ 'GT', '0' ] ] )->group( 'user_id' )->getField( "user_id,SUM(price_sum)" );
            foreach( $info as $k => $v2 ){
                if( isset( $moneySum[ $v2[ 'id' ] ] ) ){
                    $info[ $k ][ 'money' ] = $moneySum[ $v2[ 'id' ] ];
                    continue;
                }
                $info[ $k ][ 'money' ] = 0;
            }
            $this->assign( 'data',$info );
            $this->assign( 'count',$count );
            $this->assign( 'page',$show );
        }
       $this->display();
    }
    //经销商团队
    public function Myteam2(){
        $active = (int)I('active');
        $this->assign( 'active',$active );
        //经销商人数
        $count2 = M( 'user' )->where( [ 'p_id' =>$this->getUserId(),'member_status'=>['GT',1]] )->getField( 'COUNT(*)' );
        $data['count2'] = $count2;
        if($count2 != 0){
                //获取下级经销商
                $info   = M( 'user' )->field( 'id,mobile,create_time,nick_name,user_name,member_status' )->where( [ 'p_id' =>$this->getUserId(),'member_status'=>['GT',1] ] )->select();
                $uids = array_column($info,'id');
//                $moneySum = M( 'order' )->where( [ 'user_id' => [ 'IN',$uids ] ,'order_status' => [ 'GT', '0' ]] )->group( 'user_id' )->getField( "user_id,SUM(price_sum)");
                //获取下级经销商的团队

                foreach($uids as $k=>$v){
                    $teamMoneySum = 0;
                    $team = M( 'user' )->field( 'id,mobile,create_time,nick_name,user_name' )->where( [ 'p_id' => $v] )->select();
                    $teamCount = M( 'user' )->where( [ 'p_id' => $v] )->getField( 'COUNT(*)' );
                    $teamUids = array_column($team,'id');
                    if(!empty($teamUids)){
                        $teamMoneySum = M( 'order' )->where( [ 'user_id' => [ 'IN',$teamUids ] ,'order_status' => [ 'GT', '0' ]] )->getField( 'SUM(price_sum)' );
                    }



//                    $teamMoneySum = M( 'order' )->where( [ 'user_id' => [ 'IN',$teamUids ] ,'order_status' => [ 'GT', '0' ]] )->getField( "SUM(price_sum)" );
                    // $teamMoneySum = $teamMoneySum?$teamMoneySum:0;
//                    $moneySum[$v] = $moneySum[$v]?$moneySum[$v]:0;
                    //拼接
                    $uids[$v]['money'] = $teamMoneySum ;
                    $uids[$v]['count'] = $teamCount;    
                }
                //拼接
                foreach($info as &$invo){
                    $invo['count'] = $uids[$invo['id']]['count'];
                    $invo['money'] = $uids[$invo['id']]['money'];
                }
                $data['data']  = $info;
            }
            $this->assign('info',$info);
            
            $this->assign('count2',$count2);
            $this->display();
    }
    public function getInfo()
    {

        if( !isset( $_POST[ 'id' ] ) || empty( $_POST[ 'id' ] ) ){
            die;
        }
        $id   = I( 'post.id','','intval' );
        $data = M( 'user' )->where( [ 'p_id' => $id ] )->field( 'id,nick_name' )->select();       
        if( empty( $data ) ){
            die;
        }
//        $json = json_encode($data);
        $this->ajaxReturn( $data );
    }


    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId( $user_id )
    {
        if( empty( $user_id ) ){
            $this->error( '请先登录',U( 'Public/login' ) );
        }
        $this->user_id = $user_id;
    }

    /**
     * @return mixed
     */
    public function getListRows()
    {
        return $this->listRows;
    }

    /**
     * @param mixed $listRows
     */
    public function setListRows( $listRows )
    {
        $this->listRows = $listRows;
    }

    /**
     * @return mixed
     */
    public function getPageRows()
    {
        return $this->pageRows;
    }

    /**
     * @param mixed $pageRows
     */
    public function setPageRows( $pageRows )
    {
        $this->pageRows = $pageRows;
    }

    /**
     * @return mixed
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * @param mixed $where
     */
    public function setWhere()
    {
        $post        = I( 'post.' );
        $this->where = [ 'd.p_id' => $this->getUserId() ];
        //开始时间与结束时间都存在
        if( $post[ 'data' ][ 'startTime' ] && $post[ 'data' ][ 'endTime' ] ){
            $this->where[ 'd.time' ] = [ 'BETWEEN',\strtotime( $post[ 'data' ][ 'startTime' ] ) . ',' . \strtotime( $post[ 'data' ][ 'endTime' ] ) ];
        }
        //只有开始时间
        if( $post[ 'data' ][ 'startTime' ] && !$post[ 'data' ][ 'endTime' ] ){
            $this->where[ 'd.time' ] = [ 'EGT',\strtotime( $post[ 'data' ][ 'startTime' ] ) ];
        }
        //只有结束时间
        if( !$post[ 'data' ][ 'startTime' ] && $post[ 'data' ][ 'endTime' ] ){
            $this->where[ 'd.time' ] = [ 'ELT',\strtotime( $post[ 'data' ][ 'endTime' ] ) ];
        }
        //用户名
        $uid = 0;
        if( $userName = \trim( $post[ 'data' ][ 'userName' ] ) ){
            $status = \preg_match_all( '/^[\x{4e00}-\x{9fa5}\w]+$/u',$userName );//昵称只能有汉字 数字 字母 下划线
            if( !$status ) E( '昵称错误' );

            $uid = M( 'user' )->where( [ 'nick_name' => $userName ] )->getField( 'id' );
        }
        if( $uid ){
            $this->where[ 'd.uid' ] = $uid;
        }
       
    }
    public function mycode()
    {
        $active = I( 'active' );
        $this->assign( 'active',$active );
        $id = $this->getUserId();

        $user = M( 'user' )->where( [ 'id' => $id ] )->field( 'id,nick_name,mobile,code_path,member_status' )->select()[ 0 ];
        //普通会员没有推荐权限
        if( $user[ 'member_status' ] != 1 ){
            $url              = M( 'system_config' )->where( 'id=12' )->getField( 'config_value' );
            $url              = unserialize( $url );
            $user[ 'pc_url' ] = $url[ 'internet_url' ] . '/index.php/Home/Public/reg.html?reco_code=' . $user[ 'id' ]. '&qrcode=1';
            $user[ 'url' ]    = $url[ 'internet_url' ] . '/mobile/#/Register?reco_code=' . $user[ 'id' ]. '&qrcode=1';
            //是否已存在二维码并判断是否修改绑定手机号
            $path = explode('/',$user['code_path'])[3];
            if( $user[ 'mobile' ] . 'qr.png' != $path ){
                $user = $this->buildQrCode( $user );
            }
        }

        $this->assign( "data",$user );
        $this->display();
    }

    /**
     * 生成二维码图片
     */
    protected function buildQrCode( array $post )
    {

        if( empty( $post[ 'url' ] ) ){
            return $post;
        }

        if( $post[ 'url' ] === $this->initURL ){
            return $post;
        }

        $url = false !== strpos( $post[ 'url' ],'http://' ) ? $post[ 'url' ] : 'http://' . $post[ 'url' ];
        include_once COMMON_PATH . 'Tool/QRcode.class.php';
        $path = C( 'qr_image' ) . $post[ 'mobile' ] . '.png';

        \QRcode::png( $url,$path,QR_ECLEVEL_H,4 );

        //添加logo
        $logo = M('user_header')->where(['user_id'=>$post['id']])->getField('user_header');
        $logo = '.'.$logo;
        if ($logo !== FALSE) {
            $QR = $path;
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($logo));
            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                $logo_qr_height, $logo_width, $logo_height);
        }
        //输出图片
        imagepng($QR,'./Uploads/qrCode/'.$post['mobile'].'qr.png' );
        $img = '/Uploads/qrCode/'.$post['mobile'].'qr.png';


        Tool::partten( $post[ 'path' ],UnlinkPicture::class );

//        $post[ 'code_path' ] = substr( $img,0,1 );
        $save[ 'code_path' ] = $img;
        M( 'user' )->where( "id='%s'",$post[ 'id' ] )->save( $save );
        return $post;
    }


    /**
     * @description 前台提现页面
     */
    public function withdrawal()
    {
        $bestir_pre  = M( 'user' )->where( [ 'id' => $this->getUserId() ])->getField( 'bestir_pre');
        $this->assign('bestir_pre',$bestir_pre);
        $this->display();
    }
    /**
     * @description 前台提现列表页
     */
    public function withdrawalList()
    {
        $uid = $this->getUserId();
        $data = M('withdrawal')->field('id,money,bank_num,create_time,status,drawal_id,last_time')->where([ 'uid' => $uid ])->order('id desc')->select();

        foreach($data as $k=>$v){
            switch($v['status']){
                case -1:
                    $data[$k]['status'] = '未通过';
                    break;
                case 0:
                    $data[$k]['status'] = '待审批';
                    break;
                case 1:
                    $data[$k]['status'] = '待打款';
                    break;
                case 2:
                    $data[$k]['status'] = '已打款';
                    break;
            }

            if($v['bank_num'] == 0){
                $data[$k]['type'] = '支付宝';
            }else{
                $data[$k]['type'] = '银行卡';
            }

        }
        $this->assign('data',$data);
        $this->display();
    }

    public function addWithdrawal()
    {
        $url  = U( 'Distribution/index' );
        $post = I( 'post.' );
        //支付宝银行卡有一个即可,金额不能为空
        if( !(float)$post[ 'money' ] || ( !$post[ 'bank_num' ] && !$post[ 'ali_account' ] ) ){
            $this->error( '请将页面填写完整,支付宝与银行卡填写一个即可',$url );
        }
        //银行卡16 或19位数字
        if( $post[ 'bank_num' ] ) \preg_match( '/^[1-9](\d{15})|(\d{18})$/',$post[ 'bank_num' ] ) || $this->error( '银行卡号不正确',$url );
        //支付宝账号 邮箱或者手机号码
        if( $post[ 'ali_account' ] ) \preg_match( '/([a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+)|(1\d{10})/',$post[ 'ali_account' ] ) || $this->error( '支付宝账号不正确',$url );

        $uid = $this->getUserId();
        $user_re = S('user_rebate'.$uid);
        if($post['money']>$user_re['balance']){
            $this->error( '超出可提现金额',$url );
        }
        $bestir = $post['money'] * $user_re['bestir_pre'] * 0.01;

        if($bestir != $post['bestir']){
            $this->error( '非法操作',$url );
        }
        $post ['bestir_pre'] = $user_re['bestir_pre'];
        //提交申请记录
        $status = ( new WithdrawalController( $post ) )->insert();
        $status ? $this->success( '申请成功',$url ) : $this->error( '系统异常',$url );

    }
    //前台返利异常信息列表
    public function withdrawalerrorlist(){
        //获取当前用户信息
        $uid = $this->getUserId();
        $log = M('rebate_log')->where(['pid'=>$uid,'status'=>1])->select();
        $userId = array_column($log,'uid');
        //$orderId = array_column($log,'uid');
        $mobile = M('user')->where(['id'=>['IN',$userId]])->getField('id,mobile');
        foreach($log as &$v){
            $v['mobile'] = $mobile[ $v['uid'] ];
            $v['order_sn'] = $order[ $v['order_sn'] ];
            $v['res'] = '订单售后';
            $v['time'] = date('Y-m-d h:i',$v['time']);
        }
        
        if(!empty($log[0])){
            $this->true;
        }else{
            $this->false;
        }
        //showdata($log,1);
        $this->assign('log',$log);
        $this->display();
    }
   
}