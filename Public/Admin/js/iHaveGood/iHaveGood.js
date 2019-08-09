var iHaveGood =
    {
        flag : 0,
        tobodyObj : $('#tbody'),
        pageObj : $('#page'),
        //填充字符串
        str : '<tr>\n' +
            '<td class="text-center"><input type="checkbox" name="selected" value="%id%">\n' +
            '<td class="text-right">%id%</td>\n' +
            '<td class="text-right">%uid%</td>\n' +
            '<td class="text-right">%nick_name%</td>\n' +
            '<td class="text-right">%goods_title%</td>\n' +
            '<td class="text-center">%supplier%</td>\n' +
            '<td class="text-center">%mobile%</td>\n' +
            '<td class="text-center">%email%</td>\n' +
            '<td class="text-center">%time%</td>\n' +
            '<td class="text-center goods_dec"  onclick="iHaveGood.showDetail(this)"  width="164"><span style=" display:block; width: 200px; overflow: hidden;text-overflow:ellipsis;white-space: nowrap;">%goods_dec%</span><p class="z_goods_p" style=" display:none;">%goods_dec%</p></td>\n' +
            '<td class="text-center">\n' +
            '<a id="button-delete6" data-toggle="tooltip" data-id="%id%" onclick="iHaveGood.del(this)" title="" class="btn btn-danger" data-original-title="删除"><i class="fa fa-trash-o"></i></a><a id="button-delete6" data-toggle="tooltip" data-id="%id%" onclick="iHaveGood.todetail(this)" title="" class="btn btn-info" data-original-title="查看详情"><i class="fa fa-eye"></i></a>\n' +
            '</td>\n' +
            '</tr>',
        select : function (p) {
            var obj = $('#form');
            var json = obj.serialize();
            var url = obj.data('url');
            $.post(url+'?p='+p,json,function(res){
                if(res.status === 0){
                    iHaveGood.tobodyObj.html('<tr><td colspan="9" class="text-center">没有满足条件的数据</td></tr>');
                    iHaveGood.pageObj.html('');
                    layer.msg(res.message);return false;
                }
                layer.msg(res.message);
                for(var i = 0;i<res.data.data.length;i++){
                    var str_appen = iHaveGood.str;
                    str_appen = str_appen.replace(/%id%/g,res.data.data[i].id);
                    str_appen = str_appen.replace('%uid%',res.data.data[i].uid);
                    str_appen = str_appen.replace('%nick_name%',res.data.data[i].nick_name);
                    str_appen = str_appen.replace('%goods_title%',res.data.data[i].goods_title);
                    str_appen = str_appen.replace('%supplier%',res.data.data[i].supplier);
                    str_appen = str_appen.replace('%mobile%',res.data.data[i].mobile);
                    str_appen = str_appen.replace('%email%',res.data.data[i].email);
                    str_appen = str_appen.replace('%time%',res.data.data[i].time);
                    str_appen = str_appen.replace('%time%',res.data.data[i].time);
                    str_appen = str_appen.replace(/%goods_dec%/g,res.data.data[i].goods_dec);
                    if( iHaveGood.flag === 0 ){
                        iHaveGood.tobodyObj.html(str_appen);
                        iHaveGood.flag = 1;
                    }else{
                        iHaveGood.tobodyObj.append(str_appen);
                    }
                }
                iHaveGood.pageObj.html(res.data.page);
                iHaveGood.flag = 0;
                $('.pagination a').on('click',function () {
                    iHaveGood.select($(this).data('p'));
                });

            },'json');
        },
        del : function (_this) {

            //询问框
            layer.confirm('数据删除后将无法恢复!', {
                btn: ['确定','取消'] //按钮
            }, function(){
                //loading层
                var index = layer.load(1, {
                    shade: [0.1,'#fff'] //0.1透明度的白色背景
                });
                //删除数据
                var url = $('#form').data('delurl');
                var json = {};
                if($(_this).data('id')){
                    $(_this).parent().parent().remove();
                    json = {id:$(_this).data('id')};
                }else{
                    var obj = iHaveGood.tobodyObj.find('input:checked');
                    obj.parent().parent().remove();
                    var val = {};
                    $.each(obj,function (index,value) {
                        val[index] = value.value;
                    });
                    json = {id:val}

                }
                $.get(url,json,function(res){
                    $('.layui-layer-loading1').remove();
                    $('.layui-layer-shade').remove();
                    layer.msg(res.message);
                },'json');
                //删除数据

            }, function(){
                layer.msg('成功取消');
            });
        },

        showDetail : function (_this) {
            layer.open({
                type: 1,
                shade: false,
                title: false, //不显示标题
                content: $(_this).children('.z_goods_p').text(),

            });
        },
        todetail : function(_this){
            var id = $(_this).data('id');
            var url = detail_url.replace("/id/json", '?id=' + id )
            window.location.href=url;
        },


        export : function() {
        var url = export_url;
        var data= [];
        data['timeStart'] = $("#timeStart").val();
        data['timeEnd'] = $("#timeEnd").val();
        data['order_status'] = $('#status option:selected') .val();//选中的值
        var dataa = {timeEnd:data['timeEnd'],timeStart:data['timeStart']}

        submitForm(url, dataa);
    }

    };
function submitForm(action, params) {
    var form = 	$("<form></form>");
    form.attr('action', action);
    form.attr('method', 'get');
    form.attr('target', '_self');
    var input1 = $("<input type='hidden' name='timeStart' value='' />");
    input1.attr('value', params.timeStart);
    form.append(input1);
    var input2 = $("<input type='hidden' name='timeEnd' value='' />");
    input2.attr('value', params.timeEnd);
    form.append(input2);
    form.appendTo('body');
    form.css('display', 'none');
    form.submit();
}

$(function () {
    iHaveGood.select(1);

});