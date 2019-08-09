<?php

namespace Common\Controller;

/**
 * @description 提现控制器
 * Class WithdrawalController
 * @package Common\Controller
 */
class WithdrawalController
{
    private $data;//传参

    /**
     * WithdrawalController constructor.
     * @param array $data
     */
    public function __construct( array $data )
    {
        $this->data = $data;
    }

    public function insert()
    {
        $time                        = \time();
        $uid                         = $_SESSION[ 'user_id' ];
        $this->data[ 'drawal_id' ]   = $this->guid();
        $this->data[ 'uid' ]         = $uid;
        $this->data[ 'status' ]      = 0;
        $this->data[ 'create_time' ] = $time;
        $this->data[ 'last_time' ]   = $time;
        return M( 'withdrawal' )->add( $this->data );
    }
    public function guid()
    {
        $id = $_SESSION['user_id'];
        $uuid = rand(100,999).$id.rand(100,999);
        return $uuid;
    }
}