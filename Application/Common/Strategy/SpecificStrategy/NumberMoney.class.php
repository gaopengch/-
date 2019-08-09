<?php
// +----------------------------------------------------------------------
// | OnlineRetailers [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2003-2023 www.yisu.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 亿速网络（http://www.yisu.cn）
// +----------------------------------------------------------------------
// | Author: 王强 <13052079525>
// +----------------------------------------------------------------------
namespace Common\Strategy\SpecificStrategy;

use Common\Strategy\AbstractStrategy;
use Think\Exception;
use Home\Model\FreightModeModel;
use Common\TraitClass\NoticeTrait;

/**
 * 减价优惠 类
 * @author 王强
 * @version 1.0.0
 */
class NumberMoney extends AbstractStrategy
{
    use NoticeTrait;   
    public function __construct( array $receive)
    {
        $this->receive = $receive;
    }
    
    /**
     * {@inheritDoc}
     * @see \Home\Strategy\AbstractStrategy::acceptCash()
     */
    public function acceptCash()
    {
        // TODO Auto-generated method stub
        
        $goodsMoney = $_SESSION['alone_goods_money'];
        
        $data = $this->receive;
        
        if (empty($data)) {
            throw new \Exception('运费错误');
        }

        //判断运费条件
        if($data['is_free_shipping'] == 2 && $data['is_select_condition'] == 0 ){
            //不指定条件包邮
            return 0;
        }
        if($data['mail_area_monery']){
            //指定条件并在包邮地区包邮
            $totalNumber =$_SESSION['user_goods_number'];
            if($goodsMoney <$data['mail_area_monery']){
                $money = $this->algMoney($totalNumber);
                return $money;
            }else{
                if($totalNumber >= $data['mail_area_num']){
                    return 0;
                }
                $money = $this->algMoney($totalNumber);
                return $money;
            }

        }

        //不包邮，指定运费计算
        $totalNumber = (int)$_SESSION['user_goods_number'];
        $money = $this->algMoney($totalNumber);
        return $money;
    }

    /**
     * @param array $data
     */
//    private function algMoney (array $data)
    private function algMoney ($totalNumber)
    {

        $this->promptPjax($_SESSION['user_goods_number'], '商品重量错误');
         
        $data = $this->receive;
        
        $this->promptPjax($data, '运费数据错误');
        // 总件数
//        $totalNumber = $_SESSION['user_goods_number'];

        //首件
        $fristThing = (int)$data[FreightModeModel::$firstThing_d];
    
        //续件
        $continuedThing = (int)$data[FreightModeModel::$continuedThing_d];
    
        //首费
        $fristMoney = (int)$data[FreightModeModel::$fristMoney_d];
    
        //续费
        $continuedMonery = (float)$data[FreightModeModel::$continuedMoney_d];
    
        $unitThing = ($totalNumber-$fristThing) ;
    
        $unitThing = $unitThing <= 0 ? 0 : $unitThing;

    
        $money =  sprintf("%.2f", (($fristMoney + (ceil($unitThing/$continuedThing))*$continuedMonery) * $this->discount)/100 );
    
        $money = ceil($money);
    
        return $money;
    }
}