<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/27
 * Time: 9:23
 */

namespace Home\Controller;

use Common\Model\BaseModel;
use Home\Model\GoodsModel;
use Think\Page;
use Home\Model\GoodsSoldtimeModel;

Class ClearanceController extends BaseController{

    public function tomorrow(){
        $user_id = $_SESSION['user_id'];
        $goodsModel = BaseModel::getInstance(GoodsModel::class);

        //获取时段
        $soldTimeModel = BaseModel::getInstance(GoodsSoldtimeModel::class);
        $soldname = $soldTimeModel->newGetSoldTime();

        $page=I('post.page');
        if($user_id){
            $tomorrow_clear = $goodsModel->getClearGoods(3,1,$user_id,$page);
        }else{
            $tomorrow_clear = $goodsModel->getClearGoods(3,1,'',$page);
        }
        foreach($soldname as &$v){
            $v['starttime'] = $v['starttime'] + 86400;
        }
        $starttime = $soldname[0]['starttime'];
        $time = time();
        $flag = $soldname[0]['id'];
        if($starttime>$time){
            $flagShow = 1;
        }else{
            $flagShow = 0;
        }

        //获取日期并转换成数组
        $tomorrow = date('m-d/', strtotime("+1 day"));
        $tomorrow = str_split($tomorrow);
        foreach($tomorrow as &$v){
            if($v == '-'){
                $v = '月';
            }
            if($v == '/'){
                $v = '日';
            }
        }

        $tomorrowData               = array(
            'tomorrow_clear'     => $tomorrow_clear,
            'starttime'          => $starttime,
            'time'               => $time,
            'flag'               => $flag,
            'flagShow'           => $flagShow,
        );


        $this->assign('tomorrowData',$tomorrowData);
        $this->assign('soldnames',$soldname);
        $this->assign('tomorrow',$tomorrow);


        $this->display();
//




    }


    public function yesterday(){
        //上方广告图
        $yespic = D('Ad')->getNavBanner("pc昨日清仓");
        $this->assign('yespic',$yespic);

        $goods =$this->getGoodsBySold(2,'');

        $count = count($goods);
        $page = new Page($count, C("PRODUCT_PAGE"));

        $resultGoodsImgs = array_slice($goods, $page->firstRow, $page->listRows);
        $page_show = $page->show();

//        showData($resultGoodsImgs);

        $this->assign('page_show', $page_show);
        $this->assign('resultGoodsImgs',$resultGoodsImgs);

        $this->display();


    }


    /**
     * 根据清仓类型选取商品
     * @param $cond where条件
     * @param $sortcond 排序
     * @param $cid 清仓时间  1、当日 2、昨日 3、明天
     * @param $sid 清仓时段
     * @return mixed
     */
    public function getGoodsBySold($cid,$sid=1){
        $where = [];
        $where['shelves'] = 1;
        $where['status'] = 0;
        $where['is_clear'] = 1;
        $where['p_id'] = ['gt',0];
        $today = strtotime(date('Y-m-d', time()));
        switch($cid){
            case 1:
                $where['sold_date'] =array('eq',$today);
                $where['sold_time'] = $sid;
                break;
            case 2:
                $where['sold_date'] =array('eq',$today-86400);
                break;
            case 3:
                $where['sold_date'] =array('eq',$today+86400);
                $where['sold_time'] = $sid;
                break;
        }

        $goods = M('Goods')->where($where)->group("p_id")->select();
        $goodsImg = $this->goods_image($goods);
        return $goodsImg;

    }

    private function goods_image($goods)
    {
        foreach ($goods as $k => $v) {
            $b = M('goods_images')->where([
                'goods_id' => $v['p_id'],
                'is_thumb' => 1
            ])
                ->limit(1)
                ->find();
            $goods[$k]['pic_url'] = $b['pic_url'];
            //用父商品名称
            $goods[$k]['title']=M('goods')->where(['id'=>$v['p_id']])->getField('title');
            $goods[$k]['stock']=M('goods')->where(['id'=>$v['p_id']])->getField('stock');
            $goods[$k]['price_member']= $v['price_clear'];
        }

//        $goodsImage = BaseModel::getInstance(GoodsModel::class);
//        $goods = $goodsImage->add_water($goods);
        return $goods;
    }



}