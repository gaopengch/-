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

use Common\Model\BaseModel;
use Home\Model\GoodsModel;
use Think\Controller;
use Common\Tool\Tool;
use Common\TraitClass\NoticeTrait;
use Common\TraitClass\InternetTopTrait;
use Common\TraitClass\WhoIsShe;
use Common\TraitClass\SmsVerification;

/**
 * 前台控制器基类
 * 
 * @author shopsn团队
 */
class BaseController extends Controller
{
    use WhoIsShe;
    use NoticeTrait;
    use InternetTopTrait;
    use SmsVerification;

    public function _initialize()
    {
        //检测平台
        $isPass =  Tool::isMobile();
        
        if ($isPass) {
            header('Location:/mobile/index.html');
        }
        
        // 网站是否维护
        $isOpen = $this->getConfig('is_open');
        if ($isOpen == 1) {
            echo file_get_contents('./ErrorFiles/index.html');
            die();
        }
        
        // 公用数据（带测试查不多，在打开缓存后，在删除下面的测试公用数据）
        // 商品分类
//        if (! $goods_categories = S('goods_categories')) {
//            $goods_class_model = D("GoodsClass");
//
//            $goods_categories = $goods_class_model->getList('id,class_name,fid,css_class');
//            S('goods_categories', $goods_categories, 5);
//        }
        if (! $menu_sold_time = S('menu_sold_time')) {
            $sold_time_model = D("GoodsSoldtime");

            $menu_sold_time = $sold_time_model->menusoldtime('id,name');
            S('menu_sold_time', $menu_sold_time, 5);
            $goodsModel = BaseModel::getInstance(GoodsModel::class);
            foreach($menu_sold_time as $k=>$v){
                $menu_sold_time[$k]['goods']= $goodsModel->getMenuGoods($v['id']);
            }

        }
//        showData($menu_sold_time,1);

        
        // 底部文章分类
        $article_lists = $this->arctile();
        
        $str = $this->getFamily();


        
        // 购物车数量
        $cartCount = 0;
        
        // 未使用代金券数量
        $UsableCoupon = 0;
        
        // 已使用代金券数量
        $UsedCoupon = 0;
        
        // 已过期代金券
        $OverdueCoupon = 0;
        
        $carts = [];
        // 判断是否有session值
        if (! empty($_SESSION['user_id'])) {
            
            $this->assign('userId', [
                'user_name' => $_SESSION['user_name']
            ]);
            $where = [];
            // 查询购物车条数以及相关信息
            $where['a.user_id'] = $_SESSION['user_id'];
            $where['a.is_del'] = 0;
            $where['b.status'] = array(
                'lt',
                3
            );
            $cartCount = M('goods_cart as a')->field('a.id,a.goods_num,a.price_new,b.title,a.goods_id,a.buy_type,b.p_id')
                ->join('db_goods as b ON a.goods_id=b.id')
                ->where($where)
                ->count();
            
            // dump($cartCount);exit;
            $carts = M('goods_cart as a')->field('a.id,a.goods_num,a.price_new,b.title,a.goods_id,a.buy_type,b.p_id')
                ->join('db_goods as b ON a.goods_id=b.id')
                ->where($where)
                ->order('a.buy_type ASC')
                ->limit(5)
                ->select();
            $a = GoodsModel::getInitnation();
            $carts = $a->goods_image($carts);
            // dump($carts);exit;
            // 查询可用优惠券数量
            $Usable['user_id'] = $_SESSION['user_id'];
            $Usable['use_time'] = '';
            $Usable['use_end_time'] = array(
                'GT',
                time()
            );
            $UsableCoupon = M('coupon_list as a')->join('left join db_coupon as b on a.c_id=b.id')
                ->where($Usable)
                ->count();
            // 查询已用优惠券数量
            $Used['user_id'] = $_SESSION['user_id'];
            $Used['use_time'] = array(
                'neq',
                0
            );
            $UsedCoupon = M('coupon_list')->where($Used)->count();
            // 查询已过期优惠券数量
            $Over['user_id'] = $_SESSION['user_id'];
            $Over['use_time'] = '';
            $Over['use_end_time'] = array(
                'LT',
                time()
            );
            $OverdueCoupon = M('coupon_list as a')->join('left join db_coupon as b on a.c_id=b.id')
                ->where($Over)
                ->count();
            
            $member_status = $_SESSION['member_status'];
            $this->assign('member_status', $member_status);
            $mes['user_id'] = $_SESSION['user_id'];
            $mes['status'] = 0;
            $this->mes_count = M('order_logistics_message')->where($mes)->count();
        }
        
        // 代金券总数
        $z_count = $UsedCoupon + $OverdueCoupon + $UsableCoupon;
        
        $nav_data = S('nav_data');
        
        if (! $nav_data) { // 导航菜单
            $nav_data = M("Nav")->field("id,nav_titile,link,type")
                ->where([
                'status' => 1
            ])
                ->order('sort')
                ->limit(11)
                ->select();
            ;
            S('nav_data', $nav_data, 30);
        }
        
        // 判断是否需要展示商品分类,首页展示,其它页面折叠
        if (CONTROLLER_NAME == 'Index') {
            $show_categroy = true;
        } else {
            $show_categroy = false;
        }
        // 获取组配置
        
        $information = $this->getIntnetInformation();
        // 底部公司名称
        $company_name = $this->get_intnetConfig()['company_name'];
        //个人中心logo
        $logo = M('system_config')->where(array('parent_key'=>'information_by_intnet'))->getField('config_value');
        $logo = unserialize($logo)['logo_name'];
        $this->assign('logo',$logo);

        //分类列表
        $goods_class_model = D("GoodsClass");
        $goods_categories = $goods_class_model->getClassList();
        $this->assign( 'goods_categories',$goods_categories );

        ////获取商品打标列表
        $goods_marking = M('goods_marking')->where(['status' => 1])->getField('id,name');
        $this->assign('goods_marking', $goods_marking);

//        $this->assign("goods_categories", $goods_categories);
        $this->assign("menu_sold_time", $menu_sold_time);

        
        $this->assign('z_count', $z_count);
        
        $this->assign('OverdueCoupon', $OverdueCoupon);
        
        $urls = "/index.php/Home/" . CONTROLLER_NAME . "/" . ACTION_NAME;
        
        $this->assign('UsableCoupon', $UsableCoupon);
        
        $this->assign('UsedCoupon', $UsedCoupon);
        
        $this->assign('nowurl', $urls);
        
        $this->assign('carts', $carts);
        
        $this->assign('show_category', $show_categroy);
        
        $this->assign('areaLocation', $this->getLocationArea());

        $this->assign("article_lists", $article_lists);
        
        $this->assign($information); // 网站设置
        
        $this->assign('company_name', $company_name);
        
        $this->assign('str', $str);
        
        $this->assign('hot_words', self::keyWord());
        
        $this->assign('navs', $nav_data);
        
        $this->assign('cartCount', $cartCount);
        
        $this->assign('cart_goods', S('cart_goods'));
        
        $this->assign('intnetTitle', $information['intnet_title']);
    }

    protected function alreadyInData($data, $message = '更新成功')
    {
        if (! empty($data)) {
            $this->error($message);
        }
        return true;
    }

    /**
     * 导航标题
     */
    public function getNavTitle()
    {
        $title = S('navigatData');
        Tool::isSetDefaultValue($_GET, [
            'id' => 0
        ]);
        
        $thisTitle = null;
        
        if (! empty($title)) {
            foreach ($title as $key => $value) {
                if (empty($value['id']) || $_GET['id'] !== $value['id']) {
                    continue;
                }
                $thisTitle = $value['nav_titile'];
            }
        }
        $this->intnetTitle = $this->intnetTitle . ' - ' . $thisTitle;
    }

/**
 * 导航banner图
 */
    /*
     * public function navBanner()
     * {
     * $navBanners = M("Ad")->field("id,ad_link,pic_url")
     * ->where([
     * 'ad_space_id' => 6
     * ])
     * ->order('id,create_time desc')
     * ->limit(4)
     * ->select();
     * if (! empty($navBanners)) {
     * $this->ajaxReturn($navBanners);
     * } else {
     * $this->ajaxReturn([
     * 'msg' => "error"
     * ]);
     * }
     * }
     */

    /**
     * 查询清仓时段右边的商品信息
     */
    private function rightGoods($sid)
    {
        // 调用方法查询分类id
        $where = [];
        $where['shelves'] = 1;
        $where['sold_time'] = $sid;
        $where['status'] = 0;

        $today = strtotime(date('Y-m-d', time()));
        $where['sold_date'] =array('eq',$today);
        $goods = $this->where($where)->order('id desc')->limit(3)->select();

        foreach ($goods as $k => $v) {
            $goodsid[$k] = $v;
            $b = M('goods_images')->where([
                'goods_id' => $v['p_id'],
                'is_thumb' => 1
            ])
                ->limit(1)
                ->find();
            $goodsid[$k]['pic_url'] = $b['pic_url'];
        }

        return $goodsid;
    }


}