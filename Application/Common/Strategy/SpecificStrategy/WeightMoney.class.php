<?php
// +----------------------------------------------------------------------
// | OnlineRetailers [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2003-2023 www.yisu.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 亿速网络（http://www.yisu.cn）
// +----------------------------------------------------------------------
// | Author: 王强 <opjklu@126.com>
// +----------------------------------------------------------------------
namespace Common\Strategy\SpecificStrategy;


use Common\Strategy\AbstractStrategy;
use Home\Model\FreightConditionModel;
use Home\Model\FreightModeModel;
use Common\TraitClass\NoticeTrait;

/**
 * 买就送代金券 类
 * @author 王强
 * @version 1.0.0
 */
class WeightMoney extends AbstractStrategy
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
            if($goodsMoney <$data['mail_area_monery']) {
                $heavy = (int)$_SESSION['user_goods_weight'];
                $money = $this->algMoney($heavy);
                return $money;
            }else{
                if($_SESSION['user_goods_weight']>$data['mail_area_wieght']){
                    $heavy = (int)$_SESSION['user_goods_weight'];
                    $money = $this->algMoney($heavy);
                    return $money;
                }else{
                    return 0;
                }
            }
        }

        //不包邮，指定运费计算
        $heavy = (int)$_SESSION['user_goods_weight'];
        return $this->algMoney($heavy);

        
    }
    
    /**
     * @param array $data
     */
//    private function algMoney (array $data)
    private function algMoney ($heavy)
    {
      
        // 总重
//        $heavy = $_SESSION['user_goods_weight'];
        $data = $this->receive;
        //首重
        $fristHeavy = (float)$data[FreightModeModel::$firstWeight_d];
        
        //续重
        $continuedHeavy = (float)$data[FreightModeModel::$continuedHeavy_d];
        
        //首费
        $fristMoney = (float)$data[FreightModeModel::$fristMoney_d];
        
        //续费
        $continuedMonery = (float)$data[FreightModeModel::$continuedMoney_d];
        
        $unitWeight = ($heavy-$fristHeavy) ;
        
        $unitWeight = $unitWeight <= 0 ? 0 : $unitWeight;

        
        $money =  sprintf("%.2f", ( ($fristMoney + (ceil($unitWeight/$continuedHeavy))*$continuedMonery) * $this->discount)/100 );
        
        $money = ceil($money);
        
        return $money;
    }
}