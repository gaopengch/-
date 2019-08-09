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
namespace Home\Controller;

use Home\Model\AdModel;
use Home\Model\GoodsClassModel;
use Home\Model\GoodsModel;
use Common\Model\BaseModel;
use Home\Model\AnnouncementModel;
use Think\Hook;
use Common\Tool\Tool;
use Think\Page;

// 前台模块
class SupermarketController extends BaseController
{
    // 首页
    public function index()
    {
        //优化前标记

        /* --------------------------------肥胖的分割线------------------------------- */

        // 亿速公告

        $announcementModel = new AnnouncementModel();

        $announcements = $announcementModel->getAnnouncement();

        // 商品推荐模块----办公文具

        $bigInfor   = BaseModel::getInstance( GoodsClassModel::class )->getRecommendParentClass();

        //分类小图标
        $classIcon = D('Ad')->getNavBannerByOrder('超市分类小图标');

        //1楼数据
        $goodsData = $this->getGoodsClass(1);

        $show = $_SESSION['user_id'] ? true : false ;


        /* -------------------性感的分割线-------------------- */

        $this->assign( 'show',$show );
        $this->assign( 'classIcon',$classIcon );
        $this->assign( 'goodsData',$goodsData );

        $this->assign( 'bigInfor',$bigInfor );
        $this->assign( "announcements",$announcements );
        $this->assist();
        $this->product = S( 'product' );
        $this->ads     = S( 'ad_space' );

        $this->assign( 'show_category',true );

        $this->display();
    }

    /**
     * 广告图
     */
    private function assist()
    {
        $ad = M('ad');
        $top_big_ad = $ad->field('id,pic_url,ad_link')
            ->where([
                'ad_space_id' => 1
            ])
            ->order('create_time desc')
            ->limit(1)
            ->select();
        $this->assign('top_big_ad', $top_big_ad);

        $top_small_ad = $ad->field('id,pic_url,ad_link')
            ->where([
                'ad_space_id' => 2
            ])
            ->order('create_time desc')
            ->limit(1)
            ->select();
        $this->assign('top_small_ad', $top_small_ad);
    }


    /*
     *  商超首页获取分类 接口
     */
    public function getGoodsClass($page = 1)
    {
        $page = max( 1,I( 'post.page/d' ) )?max( 1,I( 'post.page/d' ) ):$page;
        $floor_num = C('supermarket_floor_num');
        if($page>$floor_num){
            $this->ajaxReturn(['data'=>[],'status'=>0]);
        }

        $goods = S('IndexGoodsPage-'.$page);

        if($goods){
            if(IS_AJAX) {
                $this->ajaxReturn(['data' => $goods, 'status' => 1]);
            }
            return $goods;
        }

        $goods = D('GoodsClass')->getGoodsClassPage($page);//无广告

        $goods['ad'] =  $this->getAdList($page);
        S('IndexGoodsPage-'.$page,$goods,60);

        if(IS_AJAX){
            if($goods['id']){
                $this->ajaxReturn(['data'=>$goods,'status'=>1]);
            }
            $this->ajaxReturn(['data'=>[],'status'=>0]);
        }
        return $goods;

    }


    public function test(){
        $res = $this->getAdList(1);
    }

    private function getAdList($page)
    {
        $classLargeImg = S('GoodsLargeImg');
        if(empty($classLargeImg)){
            //分类大图
            $classLargeImg = D('Ad')->getNavBannerByOrder('超市分类大图');
            S('GoodsLargeImg',$classLargeImg,60);
        }
        $p = $page - 1;
        $data = $classLargeImg[$p];
        return $data;
    }

    /**
     * 获取首页banner的方法
     */
    public function banner()
    {
        $ad_space_id = M('ad_space')->where("name = '聚清超市轮播图'")->getField('id');
        $banners = M( "Ad" )->where( [
//            'ad_space_id' => 3
            'ad_space_id' => $ad_space_id
        ] )
            ->order( 'id desc,create_time' )
            ->limit( 6 )
            ->select();

        $this->ajaxReturn( $banners );
    }

    /**
     * 商品分类列表
     *    分为2种商品分类列表：
     *    1商品分类点击进来的
     *      a.通过cid进行选择商品
     *      b.对应不同的条件
     *
     *     2商品搜索进来的
     *      a,通过show关键词来表示选择商品
     *      b.对应不同的条件
     */
    public function ProductList(){

//        $this->promptParse(!empty($_GET['cid']) && is_numeric($_GET['cid']), ' :(:( :(');

        $goodsModel = M("Goods");
        $goodsClassModel = M("GoodsClass");
        $id = $_GET['cid'];

        $nav_title = '聚清超市';
        $this->assign('nav_title',$nav_title);

        //通过关键词来表示搜索商品
        $show = I("get.show");
        if(I("get.keyword")){
            $arrs = explode(' ',I("get.keyword"));
            foreach($arrs as $arr){
                $arr_keyword[] = '%'.$arr.'%';
            }
            $cond['title']=['like',$arr_keyword];

        }

        $begin_price = I("get.begin_price");
        $end_price = I("get.end_price");
        $this->assign("show",$show);
        if($begin_price&&$end_price){
            $cond['price_market']=['between', [$begin_price,$end_price]];
        }elseif(!$begin_price&&!$end_price){


        }elseif(!$begin_price&&$end_price){

            //假如文本框输入的开始值为空，就赋值从0开始，尾值就是结束值
            $cond['price_market']=['between', [0,$end_price]];
        }else{

            //假如文本框输入的结束值为空,取值就大于第一个文本框的值
            $cond['price_market'] = ['egt',$begin_price];
        }

        //通过cid的方式来选择商品

        //查询条件

        (I('get.brand')=="all")?false:($cond['brand_id']=I('get.brand/d'));
        if(!$cond['brand_id']){
            unset($cond['brand_id']);
        }

        I("get.cid")?$cond['class_id']=I("get.cid/d"):false;
        ( I("get.goods_spec")=="all")?false:$aj_goods_spec =I("get.goods_spec");
        if($aj_goods_spec){
            $goods_type = $this->ajaxGoodsSpec($aj_goods_spec);
            $goods_type?$cond['goods_type']=['in',$goods_type]:false;

        }
        (I("get.price")=="all")?false:$price=I("get.price");
        $line = "-";


        if($price){
            if(strpos($price, $line) !== false){
                $arr_num = explode("-",$price);
                $begin_price = $arr_num[0];
                $end_price = $arr_num[1];
                //文本框都有值时
                if($begin_price&&$end_price){
                    $cond['price_market']=['between', [$begin_price,$end_price]];
                }elseif(!$begin_price){
                    //假如文本框输入的开始值为空，就赋值从0开始，尾值就是结束值
                    $cond['price_market']=['between', [0,$end_price]];
                }else{
                    //假如文本框输入的结束值为空,取值就大于第一个文本框的值
                    $cond['price_market'] = ['egt',$begin_price];
                }

            }else{
                $count_price=(int)$price;
                $cond['price_market'] = ['egt',$count_price];
            }
        }

        $result = $goodsClassModel->field('fid,class_name,id')->find($id);


        //畅销排行top10
//        $ranks = D("PromotionGoods")->careSelect();;
//        $this->assign('ranks',$ranks);

        //商品列表广告图
        $goods_ad = M("Ad")->field("ad_link,id,pic_url")->where(['ad_space_id'=>36])->order("id desc")->find();
        $this->assign("goods_ad",$goods_ad);



        //针对2种情况，第1种是从商品三级分类进到商品分类列表 cid>0
        //             第2种是搜索页面进入商品列表  cid为空

        if($id){
            //顶部菜单
            if($result['fid']==0){
                $str = $this->getCategory($id);
                $str = rtrim($str,",");
                $str? $str1['class_id'] = array('in',$str):false;
                $results = $goodsClassModel->field('id,fid,class_name')->where($str1)->select();

                // 该id的商品
                $ids = $this->getCategory($id);
                $ids = trim($ids,",");
                //品牌
//                $brand_list =  $this->brandClassList($ids);
                //考虑在mysql查询数据的时候只能出现一次in,商品要查分类和扩展分类
                //所以通过分类和扩展分类分别查出数据 进行累加
                $cond['shelves']=['eq',1];
                $cond['is_market']=1;
                //扩展分类
                $excond = $cond;
                $ids? $excond['extend'] = array('in',$ids):false;
                unset($excond['class_id']);
                //分类
                $ids? $cond['class_id'] = array('in',$ids):false;

                if(I("get.sortCond") == "价格") {
                    $sortcond = "price_market desc";
                    $resultGoods = $this->allGoods($cond,$sortcond);
                    //扩展分类商品
//                    $exResultGoods = $this->allGoods($excond,$sortcond);
//                    $resultGoods = array_merge($resultGoods,$exResultGoods);
                    $resultGoods = $this->remove_duplicate($resultGoods);
                }elseif(I("get.sortCond") == "评论数"){
                    $sortcond = "comment_member desc";
                    $resultGoods = $this->allGoods($cond,$sortcond);
//                    $exResultGoods = $this->allGoods($excond,$sortcond);
//                    $resultGoods = array_merge($resultGoods,$exResultGoods);
                    $resultGoods = $this->remove_duplicate($resultGoods);
                }elseif(I("get.sortCond") == "上架时间"){
                    $sortcond = "id desc";
                    $resultGoods = $this->allGoods($cond,$sortcond);
//                    $exResultGoods = $this->allGoods($excond,$sortcond);
//                    $resultGoods = array_merge($resultGoods,$exResultGoods);
                    $resultGoods = $this->remove_duplicate($resultGoods);
                }else{
                    $sortcond = "sort asc";
                    $resultGoods = $this->allGoods($cond,$sortcond);
                    //扩展分类商品
//                    $exResultGoods = $this->allGoods($excond,$sortcond);
//                    $resultGoods = array_merge($resultGoods,$exResultGoods);
                    $resultGoods = $this->remove_duplicate($resultGoods);
                }

                $reGoodsImgs = $this->allGoodsImgs($resultGoods);


                $count=count($reGoodsImgs);
                $page=new Page($count,C("PRODUCT_PAGE"));

                $resultGoodsImgs = array_slice($reGoodsImgs,$page->firstRow,$page->listRows);
                $page_show = $page->show();
                $this->assign('page_show',$page_show);

                //把会员应有的会员价重新赋值数组中           --meng
                if($_SESSION['user_id']){
                    $goodsIdStr = implode(',',array_column($resultGoodsImgs , 'id'));
                    $Goodsobj = new GoodsModel();
                    $priceArr = $Goodsobj -> getGoodsPricre($goodsIdStr);

                    foreach ($resultGoodsImgs  as $k1 => $v1){
                        foreach ($priceArr as $k2 => $v2 ){
                            if($v1['id'] == $k2){
                                $resultGoodsImgs[$k1]['price_member'] = $v2['price_level'];
                            }
                        }
                    }
                }
                //把会员应有的会员价重新赋值数组中           --meng

                if(IS_AJAX){
                    $data=array('data'=>$resultGoodsImgs,'page'=> $page_show);
                    $this->ajaxReturn($data);
                }

                //类型全部
                $all_right_id = $id;

                $this->assign('all_right_id',$all_right_id);
                $this->assign("cid",$id);
                //顶级菜单
                $top_cate = M("GoodsClass")->field("id,fid,class_name")->find($id);
                //当前位置
                $local_position =$top_cate['class_name'];
                $this->assign("local_position",$local_position);

                //猜一猜
//                $guess_goods = $this->guess();

//                $this->assign("guess_goods",$guess_goods);


                $this->assign("top_cate",$top_cate);
                //右边的分类
                $this->assign("results",$results);
                //规格
                $this->assign("goods_speces",$this->goodsSpec());
                //商品分类
                $this->assign("resultGoodsImgs",$resultGoodsImgs);

                //品牌数据
//                $this->assign("brand_list" ,$brand_list);

                //选择类型id
                $this->assign('all_id',$id);

                $this->display();
                exit;
            }else{
                $result1 = $goodsClassModel->field('fid,class_name,id')->find($result['fid']);

                //二级菜单
                if($result1['fid']==0){

                    $str = $this->getCategory($result1['id']);
                    $str = rtrim($str,",");
                    $str? $str2['class_id'] = array('in',$str):false;
                    $results = M("GoodsClass")
                        ->field('id,fid,class_name')
                        ->where($str2)
                        ->select();
                    // 该id的商品
                    $ids = $this->getCategory($id);
                    $ids = trim($ids,",");
                    //品牌
//                    $brand_list =  $this->brandClassList($ids);
                    $cond['shelves']=['eq',1];
                    $cond['is_market']=1;
                    //扩展分类
                    $excond = $cond;
                    $ids? $excond['extend'] = array('in',$ids):false;
                    unset($excond['class_id']);
                    //分类
                    $ids? $cond['class_id'] = array('in',$ids):false;

                    if(I("get.sortCond") == "价格") {
                        $sortcond = "price_market desc";
                        $resultGoods = $this->allGoods($cond,$sortcond);
//                        $exResultGoods = $this->allGoods($excond,$sortcond);
//                        $resultGoods = array_merge($resultGoods,$exResultGoods);
                        $resultGoods = $this->remove_duplicate($resultGoods);
                    }elseif(I("get.sortCond") == "评论数"){
                        $sortcond = "comment_member desc";
                        $resultGoods = $this->allGoods($cond,$sortcond);
//                        $exResultGoods = $this->allGoods($excond,$sortcond);
//                        $resultGoods = array_merge($resultGoods,$exResultGoods);
                        $resultGoods = $this->remove_duplicate($resultGoods);
                    }elseif(I("get.sortCond") == "上架时间"){
                        $sortcond = "id desc";
                        $resultGoods = $this->allGoods($cond,$sortcond);
//                        $exResultGoods = $this->allGoods($excond,$sortcond);
//                        $resultGoods = array_merge($resultGoods,$exResultGoods);
                        $resultGoods = $this->remove_duplicate($resultGoods);
                    }else{
                        $sortcond = "sort asc";
                        $resultGoods = $this->allGoods($cond,$sortcond);
//                        //扩展分类商品
//                        $exResultGoods = $this->allGoods($excond,$sortcond);
//                        $resultGoods = array_merge($resultGoods,$exResultGoods);
                        $resultGoods = $this->remove_duplicate($resultGoods);
                    }
                    $reGoodsImgs = $this->allGoodsImgs($resultGoods);
                    //去除商品相册为空的数据
                    $resultGoodsImgs = $this->removeNullImgs($reGoodsImgs);
                    $count=count($resultGoodsImgs);
                    $page=new Page($count,C("PRODUCT_PAGE"));
                    $resultGoodsImgs = array_slice($resultGoodsImgs,$page->firstRow,$page->listRows);
                    $page_show = $page->show();
                    //把会员应有的会员价重新赋值数组中           --meng
                    if($_SESSION['user_id']){
                        $goodsIdStr = implode(',',array_column($resultGoodsImgs , 'id'));
                        $Goodsobj = new GoodsModel();
                        $priceArr = $Goodsobj -> getGoodsPricre($goodsIdStr);

                        foreach ($resultGoodsImgs  as $k1 => $v1){
                            foreach ($priceArr as $k2 => $v2 ){
                                if($v1['id'] == $k2){
                                    $resultGoodsImgs[$k1]['price_member'] = $v2['price_level'];
                                }
                            }
                        }
                    }
                    //把会员应有的会员价重新赋值数组中           --meng

                    if(IS_AJAX){

                        $data=array('data'=>$resultGoodsImgs,'page'=> $page_show);

                        $this->ajaxReturn($data);
                    }




                    $this->assign('page_show',$page_show);


                    //顶级菜单
                    $top_cate = $goodsClassModel->field("id,fid,class_name")->find($result1['id']);

                    //当前点击的二次菜单
                    $current_cat = $goodsClassModel->field("id,fid,class_name")->find($id);

                    //当前位置
                    $local_position =$top_cate['class_name'].">".$current_cat['class_name'];
                    $this->assign('local_position',$local_position);

                    //全部
                    $all_right_id = $current_cat['fid'];
                    $this->assign('all_right_id',$all_right_id);
                    $this->assign("current_cat",$current_cat);

                    //猜一猜
//                    $guess_goods = $this->guess();
//                    $this->assign("guess_goods",$guess_goods);



                    $this->assign("top_cate",$top_cate);
                    //右边的分类
                    $this->assign("results",$results);
                    //商品分类
                    $this->assign("resultGoodsImgs",$resultGoodsImgs);

                    //规格
                    $this->assign("goods_speces",$this->goodsSpec());
                    //品牌数据
//                    $this->assign("brand_list" ,$brand_list);
                    //选择类型id
                    $this->assign("all_id",$result1['fid']);
                    $this->display();

                }else{//三级分类
                    //品牌
//                    $brand_list =  $this->brandClassList($id);
                    $str = $this->getCategory($result1['fid']);
                    $str = rtrim($str,",");
                    $str? $str3['class_id'] = array('in',$str):false;
                    $results = $goodsClassModel
                        ->field('id,fid,class_name')
                        ->where($str3)
                        ->select();

                    if(I("get.sortCond") == "价格") {
                        $sortcond = "price_market desc";

                    }elseif(I("get.sortCond") == "评论数"){
                        $sortcond = "comment_member desc";

                    }elseif(I("get.sortCond") == "上架时间"){
                        $sortcond = "id desc";

                    }else{
                        $sortcond = "sort asc";
                    }
                    $cond['p_id'] = ['gt',0];
                    $cond['shelves'] = ['eq',1];
                    $cond['is_market']=1;
                    $cond['status'] = 0;
                    //扩展分类
                    $excond = $cond;
                    $excond['extend'] = $excond['class_id'];
                    unset($excond['class_id']);
                    // 该id的商品
                    $feild = "id,title,p_id,class_id,price_market,price_member,price_supermarket,comment_member,sales_sum,stock,goods_marking";
                    if($sortcond){
                        $resultGoods = $goodsModel
                            ->field($feild)
                            ->where($cond)
                            ->order($sortcond)
                            ->group('p_id')
                            ->select();
                        var_dump(1);
                        die;

                        $resultGoods = $this->remove_duplicate($resultGoods);

                    }else{
                        $resultGoods = $goodsModel
                            ->field($feild)
                            ->where($cond)
                            ->group("p_id")
                            ->order("sort asc")
                            ->select();
                        var_dump($resultGoods);
                        die;

                        $resultGoods = $this->remove_duplicate($resultGoods);

                    }



                    $reGoodsImgs = $this->allGoodsImgs($resultGoods);

                    //去除商品相册为空的数据
                    $resultGoodsImgs = $this->removeNullImgs($reGoodsImgs);

                    $count=count($resultGoodsImgs);
                    $page=new Page($count,C("PRODUCT_PAGE"));
                    $resultGoodsImgs = array_slice($resultGoodsImgs,$page->firstRow,$page->listRows);
                    $page_show = $page->show();

                    //把会员应有的会员价重新赋值数组中           --meng
                    if($_SESSION['user_id']){
                        $goodsIdStr = implode(',',array_column($resultGoodsImgs , 'id'));
                        $Goodsobj = new GoodsModel();
                        $priceArr = $Goodsobj -> getGoodsPricre($goodsIdStr);
                        foreach ($resultGoodsImgs  as $k1 => $v1){
                            foreach ($priceArr as $k2 => $v2 ){
                                if($v1['id'] == $k2){
                                    $resultGoodsImgs[$k1]['price_member'] = $v2['price_level'];
                                }
                            }
                        }
                    }
                    //把会员应有的会员价重新赋值数组中           --meng

                    if(IS_AJAX){
                        $data=array('data'=>$resultGoodsImgs,'page'=> $page_show);
                        $this->ajaxReturn($data);
                    }


                    //考虑到点击第3级菜单的时候，商品列表中显示的菜单和以前的不一样，要显示出自己和兄弟出来
                    //先找到父亲，通过父亲查找子类显示出来
                    $third_parent = M("GoodsClass")->field("fid")->find($id) ;
                    $third_parent_id = $third_parent['fid'];

                    $third_brother = $this->getCategory($third_parent_id);
                    $third_brother = rtrim($third_brother,",");
                    $third_brother? $cond2['class_id'] = array('in',$third_brother):false;
                    $third_parent_childs = M("GoodsClass")
                        ->field("id,class_name,fid")
                        ->where($cond2)
                        ->select();

                    //猜一猜
//                    $guess_goods = $this->guess();

//                    $this->assign("guess_goods",$guess_goods);

                    $this->assign("third_parent_id",$third_parent_id);
                    $this->assign("third_parent_childs",$third_parent_childs);
                    $this->assign('page_show',$page_show);

                    //当前菜单的父级菜单
                    $top_cate = $goodsClassModel->field("id,fid,class_name")->find($result1['fid']);

                    //二级菜单
                    $two_cate = $goodsClassModel->where(['id'=>$top_cate['id']])->getField('class_name');

                    //当前点击的三次菜单
                    $current_third_cat = M("GoodsClass")->field("id,fid,class_name")->find($id);
                    //全部
                    $all_right_id = $current_third_cat['fid'];

                    //当前位置
                    $local_position =$top_cate['class_name'].">". $two_cate.">".$current_third_cat['class_name'];

                    $this->assign('local_position',$local_position);
                    $this->assign('all_right_id',$all_right_id);

                    $this->assign("current_third_cat",$current_third_cat);
                    $this->assign("top_cate",$top_cate);
                    //右边的分类
                    $this->assign("results",$results);
                    //商品分类
                    $this->assign("resultGoodsImgs",$resultGoodsImgs);

                    //品牌数据
//                    $this->assign("brand_list" ,$brand_list);

                    //规格
                    $this->assign("goods_speces",$this->goodsSpec());

                    //选择类型id
                    $this->assign("all_id",$result1['id']);
                    $this->display();
                    exit;
                }

            }
        }else{//搜索页面
            $feild = "id,title,p_id,class_id,price_market,price_member,price_supermarket,comment_member,sales_sum,stock,goods_marking";
            $cond['shelves']=['eq',1];
            $cond['is_market']=1;
            $cond['status'] = 0;
            //新品
            if(I("get.new")){
                $goodses = $this->getNew($cond);
            }
            if(I("get.sortCond") == "价格") {
                $sortcond = "price_market desc";
                if(IS_AJAX){
                    $goodses = $goodsModel
                        ->field($feild)
                        ->where($cond)
                        ->order($sortcond)
                        ->group("p_id")
                        ->select();
                }
            }elseif(I("get.sortCond") == "评论数"){
                $sortcond = "comment_member desc";
                $goodses = $goodsModel
                    ->field($feild)
                    ->where($cond)
                    ->order($sortcond)
                    ->group("p_id")
                    ->select();
            }elseif(I("get.sortCond") == "上架时间"){
                $sortcond = "id desc";
                $goodses = $goodsModel
                    ->field($feild)
                    ->where($cond)
                    ->order($sortcond)
                    ->group("p_id")
                    ->select();
            }else{
                $goodses = $goodsModel
                    ->field($feild)
                    ->where($cond)
                    ->order("sort asc")
                    ->group("p_id")
                    ->select();

            }


            $reGoodsImgs = $this->allGoodsImgs($goodses);

            //去除商品相册为空的数据
            $resultGoodsImgs = $this->removeNullImgs($reGoodsImgs);
            $count=count($resultGoodsImgs);

            $page=new Page($count,C("PRODUCT_PAGE"));
            $resultGoodsImgs = array_slice($resultGoodsImgs,$page->firstRow,$page->listRows);
            $page_show = $page->show();

            //把会员应有的会员价重新赋值数组中           --meng
            if($_SESSION['user_id']){
                $goodsIdStr = implode(',',array_column($resultGoodsImgs , 'id'));
                $Goodsobj = new GoodsModel();
                $priceArr = $Goodsobj -> getGoodsPricre($goodsIdStr);
                foreach ($resultGoodsImgs  as $k1 => $v1){
                    foreach ($priceArr as $k2 => $v2 ){
                        if($v1['id'] == $k2){
                            $resultGoodsImgs[$k1]['price_member'] = $v2['price_level'];
                        }
                    }
                }
            }
            //把会员应有的会员价重新赋值数组中           --meng
            if(IS_AJAX){
                $data=array('data'=>$resultGoodsImgs,'page'=> $page_show);
                $this->ajaxReturn($data);
                exit;
            }
            //猜一猜
//            $guess_goods = $this->guess();

//            $this->assign("guess_goods",$guess_goods);
            //商品
            $this->assign("resultGoodsImgs",$resultGoodsImgs);
            //分页
            $this->assign("page_show",$page_show);
            //品牌
//            $this->assign("brand_list",$this->searchBrandLists());
            //类型
            $this->assign("goods_classes",$this->getGooodsClass());
            //规格
            $this->assign("goods_speces",$this->goodsSpec());
            //判断是不是搜索
            $this->assign("show1",$show);
            $this->display();
        }
    }
    /**
     * 寻找子类的id
     * @param integer $category_id 父级分类
     * @return string $category_ids 该父级分类的子类
     */
    private  function getCategory($category_id ){
        $category_ids = $category_id.",";
        $child_category = M("GoodsClass") -> field("id,class_name")->where(['fid'=>$category_id])->select();
        foreach( $child_category as $key => $val ){
            $category_ids .= $this->getCategory( $val["id"] );
        }
        return $category_ids;
    }
    /**
     * 获取全部分类的商品基本信息
     * @param string $categoy 商品分类的id
     * @return mixed  商品的基本信息
     */
    private function allGoods($category,$sortcond){
        $cond = $category;
        $cond['p_id'] = ['gt',0];
        $cond['shelves'] = 1;
        $cond['status'] = 0;
        if($sortcond){
            $categoryGoods=M('Goods')
                ->field('id,title,price_market,price_member,price_supermarket,p_id,comment_member,sales_sum,stock,goods_marking')
                ->where($cond)
                ->group("p_id")
                ->order($sortcond)
                ->select();

        }else{
            $sortcond = "sales_sum desc";
            $categoryGoods=M('Goods')
                ->field('id,title,price_market,price_member,price_supermarket,p_id,comment_member,sales_sum,stock,goods_marking')
                ->where($cond)
                ->group("p_id")
                ->order($sortcond)
                ->select();

        }
        return $categoryGoods;
    }

    /**
     * 二维数组去掉重复值
     * @param $array2D
     * @return array
     */
    function remove_duplicate($array){
        $result=array();
        for($i=0;$i<count($array);$i++){
            $source=$array[$i];
            if(array_search($source,$array)==$i && $source<>"" ){
                $result[]=$source;
            }
        }
        return $result;
    }

    /**
     * 获取全部商品分类的商品信息
     * @param array $allgoods 商品基本信息
     * @return mixed 返回商品相册信息
     */
    private function allGoodsImgs($allgoods){
        $goods_marking = M('goods_marking')->where(['status' => 1])->getField('id,name');

        foreach($allgoods as &$allgood){
            $pic_url = M("GoodsImages")->where(['goods_id'=>$allgood['p_id']])->limit(1)->find();
            $allgood['pic_url'] = $pic_url['pic_url'];
            $allgood['price_member'] = $allgood['price_supermarket'];
            $allgood['goodsMarking'] = $goods_marking[$allgood['goods_marking']];
        }
        unset($allgood);

        return $allgoods;
    }

    /**
     * 去除商品信息中商品相册为空的数据
     * @param array $reGoodsImgs 商品相册信息
     * @return mixed $reGoodsImgs 返回商品信息：商品相册不为空的数据
     */
    private function removeNullImgs($reGoodsImgs){
        foreach($reGoodsImgs as $k=>$reGoodsImg){
            if(empty($reGoodsImg['pic_url'])){
                unset($reGoodsImgs[$k]);
            }
        }
        return $reGoodsImgs;
    }

    /**
     * 当用户搜索商品的时候，跳到这个页面的时候，类型显示为所有的商品分类
     * @return mixed 返回的数据
     */
    public function getGooodsClass(){
        $goodsclassModel = M("goodsClass");
        $topGoodsClass = $goodsclassModel->field("id,class_name")->where(['fid'=>0])->select();
        return $topGoodsClass;
    }

    /**
     * 规格项数据
     * @return mixed array
     */
    public function goodsSpec(){
        $goodsSpec =M("GoodsSpec")->distinct(true)->field("name")->select();
        return $goodsSpec;
    }

    public function ajaxGoodsSpec($aj_goods_spec){
        $goods_types = M("GoodsSpec")->distinct("type_id")->where(['name'=>$aj_goods_spec])->getField("type_id",true);
        return $goods_types;
    }

}





