var tag = false;
$('.today_time_frame').on('click','li',function(){
    if(tag == true ){return false;}
    tag = true;
    $(this).addClass('active').siblings().removeClass('active');
    var cid = $(this).find('.q_rob_time').data('cid');
    var sid = $(this).find('.q_rob_time').data('sid');
    var state = $(this).find('.q_rob_time').data('value');
    var start_time = $(this).find('.q_rob_time').data('starttime');
    var _this = $(this);
    $.get(GET_ClearGood,{cid: cid, sid: sid},function(res){
        console.log(res);
        tag = false;
        if(state){
            clearInterval(timer);
            computationTime(res.data.time,start_time);
        }
        _this.parent().siblings('.q_today_g').html('');
        if(res.data.goods.length == 0){
            var no_data = $('<div class="q_no_data">本时段暂无商品</div>');
            _this.parent().siblings('.q_today_g').append(no_data);
        }else{
            for(var i=0; i < res.data.goods.length; i++){
                var goodsId = res.data.goods[i].id;
                var price_clear = res.data.goods[i].price_clear;
                var price_market = res.data.goods[i].price_market;
                var goodsImg = res.data.goods[i].pic_url;
                var stock = res.data.goods[i].stock;
                var title = res.data.goods[i].title;
                var dom1 = '<div class="q_t_goods fl"><a class="q_item_lk" target="_blank" href='+goodsDetailsUrl.replace('.html', '/id/' + goodsId + '.html')+'><div class="q_g_img"><img src="'+goodsImg+'" alt=""/>'
                //库存为零加售空图片
                if(stock == 0){
                    dom1 += '<img class="no_stock" src="/Public/Home/img/sellout2.jpg" alt=""/>';
                }

                    dom1 +='</div><div class="q_g_inventory">库存 ：'+stock+' </div><div class="q_divider"></div><div class="q_g_name">'+title+'</div><div class="q_g_price">'
                //是否登录显价格
                if(IS_LOGIN){
                    dom1 += ' <span class="new_price q_price"><i>￥</i><span>'+price_clear+'</span></span><span class="origin_price q_price"><i>￥</i><span>'+price_market+'</span></span>'
                }else{
                    dom1 += ' <span class="new_price q_price"><i>￥</i><span>'+price_clear+'</span></span><span class="origin_price q_price"><span>会员价更低</span></span>'
                }

                dom1 +=' </div><div class="q_to_bug q_btn">立即开抢</div></a></div>';

                _this.parent().siblings('.q_today_g').append(dom1);
            }
        }
    },'json');
})