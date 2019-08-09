
// +----------------------------------------------------------------------
// | OnlineRetailers [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2003-2023 www.yisu.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 亿速网络（http://www.yisu.cn）
// +----------------------------------------------------------------------
// | Author: 王强 <opjklu@126.com>
// +----------------------------------------------------------------------
$(function() {
    //导出下级会员
    $(".export-down").on('click',function(){
        //电话
        var uid = $(this).attr("data-value");
        var data = {uid:uid};
        var url = EXP_URL;
        var tj_value = JSON.stringify(data);
        submitForm2(url,tj_value);
    });

    /**
     * js模拟表单get提交
     * @param action url地址
     * @param params 要传递的值
     */
    function submitForm2(action, params) {
        var form = $("<form></form>");
        form.attr('action', action);
        form.attr('method', 'get');
        form.attr('target', '_self');
        var input1 = $("<input type='hidden' name='uid' value='' />");
        input1.attr('value', params);
        form.append(input1);
        form.appendTo("body");
        form.css('display', 'none');
        form.submit();
    }
});
