var page = 2;//楼层
var flag = true;//请求的标志,false 不请求

function getData(p){
    $.post(newGoodsClassUrl, {page: p}, function (res) {

        if (res.status === 1) {
            var data = res.data;

            var goodsLeftList = '<div class="q_class_list">'+
            '<p class="q_class_tit">'+data.class_name+' <span>'+data.english_name+'</span></p>'+
            '<div class="q_class_content clearfix">'+
            '<div class="q_class_banner fl">'+
            '<img class="q_nav_img" src='+data.ad.pic_url+' alt="banner"/>'+
            '<div class="q_nav_tit">'+
            '<p class="q_zone">'+data.class_name+'专区</p>'+
            '<p class="q_zone_describe">'+data.description+'</p>'+
            '<a target="_blank" href="'+ProductListUrl.replace('.html', '/cid/' + data.id + '.html')+'">'+
            '<div class="q_buy_now q_buy_color'+p+' fl">BUY NOW ></div>'+
            '</a>'+
            '</div>'+
            '<div class="q_nav_class">';

            for(var i = 0; i<data.classChilds.length; i++){
                goodsLeftList+='<a target="_blank" href='+ProductListUrl.replace('.html', '/cid/' + data.classChilds[i].id + '.html')+'><span class="q_class_name q_tit_color'+p+'">'+data.classChilds[i].class_name+'</span></a>'
            }
            //console.log(goodsLeftList)

            goodsLeftList += '</div>'+
            '</div>'+
            '<div class="q_goods_list fl">'+
            '<ul class="clearfix" style="width: 970px;">';

            for(var n = 0; n<data.goods.length; n++){
                goodsLeftList+= '<li class="fl">'+
                '<a target="_blank" href='+goodsDetailsUrl.replace('.html', '/id/' + data.goods[n].id + '.html')+'>'+
                '<div class="q_goods_img">'+
                '<img src="'+data.goods[n]['pic_url']+'" alt=""/>';
                if(data.goods[n].stock == 0){
                    goodsLeftList += '<img class="no_stock" src="/Public/Home/img/sellout2.jpg" alt=""/>';
                }
                goodsLeftList += '</div>'+
                '<div class="q_goods_info clearfix">'+
                '<p class="goods_info_name">'+data.goods[n]['title']+'</p>'+
                '<div class="goods_info_price fl">';

                //是否登录显价格

                if(IS_LOGIN){
                    goodsLeftList += '<i>￥</i><span>'+data.goods[n]['price_member']+'</span>'+
                    '</div>'+
                    '<span class="fl goods_info_tip"><i>￥</i>'+data.goods[n]['price_market']+'</span>';
                }else{
                    goodsLeftList += '<i>￥</i><span>'+data.goods[n]['price_market']+'</span>'+
                    '</div>'+
                    '<span class="fl goods_info_tip">会员价更低</span>';
                }
                if(data.goods[n].is_customs == 0){
                    goodsLeftList += '<img class="fr buy_cart" data-id='+data.goods[n]['id']+' src="/Public/Home/img/buy_cart.png" alt=""/>';
                }
                goodsLeftList +='</div></a></li>';
            }

            goodsLeftList += '</ul>'+
            '</div>'+
            '</div>'+
            '</div>';

            flag = true;
            page ++;
            $('#loading').remove();

            $('.q_goods_class .w').append(goodsLeftList);


        }else{
            $('#loading').remove();
            $('#goods_class').after('<div id="loading">--我是有底线的--</div>');
        }
    },'json')
}

$(window).scroll(function(){
    var scrollTop = $(this).scrollTop();
    var scrollHeight = $(document).height();
    var windowHeight = $(this).height();
    if(scrollTop + windowHeight > scrollHeight-650 && flag){
        flag = false;
        $('#goods_class').after('<div id="loading">正在加载...</div>');
        getData(page);
    }
});