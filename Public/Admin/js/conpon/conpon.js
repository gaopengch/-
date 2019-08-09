
// +----------------------------------------------------------------------
// | OnlineRetailers [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2003-2023 www.yisu.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 亿速网络（http://www.yisu.cn）
// +----------------------------------------------------------------------
// | Author: 王强 <opjklu@126.com>
// +----------------------------------------------------------------------
/**
 *优惠券js 
 */
(function(){
	
	function Conpon() {
		
		this.addConpon = function (url, id) {
			var isExe = document.getElementById(id);
			if(!isExe) {
				return false;
			}
			
			var form = $('#'+id).formToArray();
			var flag = 0;
			
			for(var i in form) {
				
				if(!form[i].value) {
					//return false;
				} else {
					flag ++;
				}
			}

			if(flag === 0) {
				return false;
			}

			return this.ajax(url, form, function(res){
				return Tool.notice(res);
			});
		}

        /**
         * 选择商品
         */
        this.selectGoods = function (url){
            var goodsId = [];
            // 过滤选择重复商品
            $('input[name*="goods_id"]').each(function(i,o){
                goodsId.push($(o).val());
            });
            return window.open(url, '请选择商品', "width=900, height=650, top=100, left=100");
        }
        /**
         *
         */
        this.selectClass = function (url){
            var classId = [];

            return window.open(url, '请选择分类', "width=900, height=650, top=100, left=100");
        }

    }
	Conpon.prototype = Tool;
	
	window.Conpon = new Conpon();
	
	return window.Conpon;
})(window);
$(function(){
	
	
});

window.onload = function () {
	
	Conpon.dataPick('send_start_time');
	Conpon.dataPick('send_end_time');
	Conpon.dataPick('use_start_time');
	Conpon.dataPick('use_end_time');

    //发放方式
    //$('input[type="radio"]').click(function(){
    $('#order-status').find('input[type="radio"]').click(function(){
        if($(this).val() == 0){
        	$('.timed').find('input[type="text"]').each(function(){
        		$(this).attr('disabled', 'disabled');
        	});
        	$('.timed').hide();
        }else{
        	$('.timed').show();
        	$('.timed').find('input[type="text"]').each(function(){
        		$(this).attr('disabled', false);
        	});
        }
    });
    $('input[type="radio"]:checked').trigger('click');


    //优惠金额or折扣
    $('.money_or_discount').find('input[type="radio"]').click(function(){
        if($(this).val() == 1){
            $('.or_money').show();
            $('.or_discount').hide();
        }else if($(this).val() == 2){
            $('.or_money').hide();
            $('.or_discount').show();
        }
    });
    //有效时间
    $('.time_type').find('input[type="radio"]').click(function(){
        if($(this).val() == 1){
            $('.time_absolute').show();
            $('.time_relative').hide();
        }else if($(this).val() == 2){
            $('.time_absolute').hide();
            $('.time_relative').show();
        }
    });
    //商品、分类限制
    $('.goods_or_class').find('input[type="radio"]').click(function(){
        if($(this).val() == 0){
            $('.or_goods').hide();
            $('.or_class').hide();
            $('.goods_or_class_show').hide();
        }else if($(this).val() == 1){
            $('.or_goods').show();
            $('.or_class').hide();
            $('.goods_or_class_show').find('input').val('');
            $('.goods_or_class_show').show();
        }else{
            $('.or_goods').hide();
            $('.or_class').show();
            $('.goods_or_class_show').find('input').val('');
            $('.goods_or_class_show').show();
        }
    });
}
