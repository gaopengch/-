
var t,now,endTime,hour,min,sec,hourTime,minTime,secTime,timer = null;
var tag = false;
$('.period_of_time').on('click','li',function(){
    if(tag == true ){return false;}
    tag = true;
    $(this).addClass('active').siblings().removeClass('active');
    var cid = $(this).find('.time').data('cid');
    var sid = $(this).find('.time').data('sid');
    var state = $(this).find('.time').data('value');
    var start_time = $(this).find('.time').data('starttime');
    var _this = $(this);
    $.get(GET_ClearGood,{cid: cid, sid: sid},function(res){
        console.log(res);
        tag = false;
        if(state){
            clearInterval(timer);
            computationTime(res.data.time,start_time);
        }
        _this.parent().next().find('.q-goods-li').html('');
        if(res.data.goods.length == 0){
            var no_data = $('<div class="q_no_data">本时段暂无商品</div>');
            _this.parent().next().find('.q-goods-li').append(no_data);
        }else{
            for(var i=0; i < res.data.goods.length; i++){
                var goodsId = res.data.goods[i].id;
                var price_clear = res.data.goods[i].price_clear;
                var price_market = res.data.goods[i].price_market;
                var goodsImg = res.data.goods[i].pic_url;
                var stock = res.data.goods[i].stock;
                var title = res.data.goods[i].title;
                var is_customs = res.data.goods[i].is_customs;
                var goodsLi = '';
                var dom1 = '<div class="q_slider_item fl"><a class="q_item_lk" target="_blank" href='+goodsDetailsUrl.replace('.html', '/id/' + goodsId + '.html')+'><div class="q_item_img"><img src="'+goodsImg+'" alt=""/>';
                //库存为零加售空图片
                if(stock == 0){
                    dom1 += '<img class="no_stock" src="/Public/Home/img/sellout2.jpg" alt=""/>';
                }

                dom1 += '</div><div class="q_inventory">库存：'+stock+' </div> <div class="q_item_name">'+title+'</div><div class="q_item_price">';
                //是否登录显价格
                if(IS_LOGIN){
                    dom1 += ' <span class="new_price fl"><i>￥</i><span>'+price_clear+'</span></span><span class="origin_price fl"> <i>￥</i><span>'+price_market+'</span></span>';
                }else{
                    dom1 += ' <span class="new_price fl"><i>￥</i><span>'+price_market+'</span></span><span class="origin_price fl"> <span>会员价更低</span></span>';
                }
                if(is_customs == 0){
                dom1 +='<img class="fr q_buy_cart" data-id='+goodsId+' src="/Public/Home/img/buy_cart.png" alt=""/> ';
                }
                dom1 += '</div> </a> </div>';

                goodsLi = dom1;
                _this.parent().next().find('.q-goods-li').append(goodsLi);
            }
        }
    },'json');
})
function computationTime(now_Time,timestamp){
    timer = setInterval(function(){
        t = null;
        now = new Date(now_Time * 1000);
        endTime = new Date(timestamp * 1000);
        if(now > endTime){
            t = now.getTime() - endTime.getTime();
        }else{
            t = endTime.getTime() - now.getTime();
        }
        if(t>0){
            hour=Math.floor(t/3600000);
            min=Math.floor((t/60000)%60);
            sec=Math.floor((t/1000)%60);
            hourTime = hour < 10 ? "0" + hour : hour;
            minTime = min < 10 ? "0" + min : min;
            secTime = sec < 10 ? "0" + sec : sec;
        }else{
            clearInterval(timer);
            hourTime = '00';
            minTime = '00';
            secTime = '00';
        }
        if(now > endTime){
            $('.q_cs_cd').text('本场秒杀开始已有') ;
        }else{
            $('.q_cs_cd').text('距离本场秒杀开始还有') ;
        }
        $('.cd_item .hour').text(hourTime);
        $('.cd_item .minute').text(minTime);
        $('.cd_item .seconds').text(secTime);
        now_Time +=1;
    },1000);
}
$('.q-goods-li').on('click','.q_buy_cart',function(){
    var id = $(this).data('id');
    var json = {
        goods_id : id ,
        goods_num : 1
    }
    AddCart(json);
    return false;
})

/**
 *购物车局部刷新重新逻辑
 */
 function AddCart(json) {
    return $.post(NewAddCart, json, function (res) {
        if (res.status == 1) {
            var cartHtml='';
            var packageHtml='';
            $.each(res.data,function(i,n){
                if(n.buy_type==1 && n.title!=null) {
                    cartHtml += "<dd class='clearfix active'> <a href='javscript:;' class='fl'> <img src='" + n.pic_url + "' alt=''> </a> <a href='" + n.cart_url + "' class='fl con'>" + n.title + " </a> <strong class='fl'> <span>￥" + n.price_new + "</span>x" + n.goods_num + "<br> <a href='javascript:;' class='dels' data='" + n.id + "'>删除</a> </strong> </dd>"
                }else if(n.buy_type==2 && n.goods_data!=null)
                {
                    packageHtml+="<dd class='clearfix active'> <a href='javscript:;' class='fl'> </a> <a href='"+ n.cart_url+"' class='fl con'>套餐 </a> <strong class='fl'> <span>￥"+ n.price_new+"</span><br> <a href='javascript:;' class='dels' data='"+n.id+"'>删除</a> </strong> </dd>"
                }
            })
            $('#new_cart_data').empty();
            $('#new_cart_data').append(cartHtml);
            $('#new_cart_data').append(packageHtml);
            $('#couts').empty();
            //console.log(res.status);
            $('#couts').append(res.data['new_cart_count']);
            toastr.success(res.message);
        } else if (res.status == 0) {
            toastr.error(res.message);
            setInterval(function() {
                window.location.href = res.data.url;
            }, 3000);
        }
        return false;
    });
}