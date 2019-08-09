var Withdrawal =
{
    flag: 0,
    tobodyObj: $('#tbody'),
    pageObj: $('#page'),
    //填充字符串
    str: '<tr>\n' +
    '<td class="text-center"><input type="checkbox" name="selected" value="%id%">\n' +
    '<td class="text-right">%id%</td>\n' +
    '<td class="text-right">%drawal_id%</td>\n' +
    '<td class="text-right">%nick_name%</td>\n' +
    '<td class="text-right">%mobile%</td>\n' +
    '<td class="text-center">%bank_num%</td>\n' +
    '<td class="text-center">%bank_name%</td>\n' +
    '<td class="text-center">%bank_user%</td>\n' +
    '<td class="text-center">%ali_account%</td>\n' +
    '<td class="text-center">%money%</td>\n' +
    '<td class="text-center">%bestir%</td>\n' +
    '<td class="text-center">%total%</td>\n' +
    '<td class="text-center">%status%</td>\n' +
    '<td class="text-center">%result%</td>\n' +
    '<td class="text-center">%opinion%</td>\n' +
    '<td class="text-center">%admin%</td>\n' +
    '<td class="text-center">%last_time%</td>\n' +
    '<td class="text-center">\n' +
    '%OTHER%' +
    '</td>\n' +
    '</tr>',
    select: function (p) {
        var obj = $('#form');
        var json = obj.serialize();
        var url = obj.data('url');
        $.post(url + '?p=' + p, json, function (res) {
            if (res.status === 0) {
                Withdrawal.tobodyObj.html('<tr><td colspan="13" class="text-center">没有满足条件的数据</td></tr>');
                Withdrawal.pageObj.html('');
                layer.msg(res.message);
                return false;
            }
            layer.msg(res.message);
            for (var i = 0; i < res.data.data.length; i++) {

                var str_appen = Withdrawal.str;
                str_appen = str_appen.replace(/%id%/g, res.data.data[i].id);
                str_appen = str_appen.replace('%drawal_id%', res.data.data[i].drawal_id);
                str_appen = str_appen.replace('%nick_name%', res.data.data[i].user_name);
                str_appen = str_appen.replace('%mobile%', res.data.data[i].mobile);
                str_appen = str_appen.replace('%bank_num%', res.data.data[i].bank_num=='0'?'':res.data.data[i].bank_num);
                str_appen = str_appen.replace('%bank_name%', res.data.data[i].bank_name==null?'':res.data.data[i].bank_name);
                str_appen = str_appen.replace('%bank_user%', res.data.data[i].bank_user==null?'':res.data.data[i].bank_user);
                str_appen = str_appen.replace('%ali_account%', res.data.data[i].ali_account=='0'?'':res.data.data[i].ali_account);
                str_appen = str_appen.replace('%money%', res.data.data[i].money);
                str_appen = str_appen.replace('%bestir%', res.data.data[i].bestir);
                str_appen = str_appen.replace('%total%', res.data.data[i].total);
                str_appen = str_appen.replace('%status%', res.data.data[i].status_str);
                str_appen = str_appen.replace('%result%', res.data.data[i].result);
                str_appen = str_appen.replace('%opinion%', res.data.data[i].opinion=='0'?'':res.data.data[i].opition);
                str_appen = str_appen.replace('%last_time%', res.data.data[i].last_time);
                str_appen = str_appen.replace('%admin%', res.data.data[i].admin=='0'?'':res.data.data[i].admin);
                if (res.data.data[i].status === 0) {

                    var sstr = '<a id="button-devare6" data-toggle="tooltip" data-id=' + res.data.data[i].id + ' data-status='+res.data.data[i].status+' onclick="Withdrawal.doSome(this)" class="btn btn-primary" data-original-title="审批">审批</a></td>';
                    str_appen = str_appen.replace('%OTHER%', sstr);
                } else if (res.data.data[i].status === 1) {
                    var sstr = '<a id="button-devare6" data-toggle="tooltip" data-id=' + res.data.data[i].id + ' data-status='+res.data.data[i].status+' onclick="Withdrawal.doSome(this)" class="btn btn-primary" data-original-title="打款确认">确认打款</a></td>';
                    str_appen = str_appen.replace('%OTHER%', sstr);
                } else {
                    str_appen = str_appen.replace('%OTHER%', '无需操作</td>');
                }
                if (Withdrawal.flag === 0) {
                    Withdrawal.tobodyObj.html(str_appen);
                    Withdrawal.flag = 1;
                } else {
                    Withdrawal.tobodyObj.append(str_appen);
                }
            }
            Withdrawal.pageObj.html(res.data.page);
            Withdrawal.flag = 0;
            $('.pagination a').on('click', function () {
                Withdrawal.select($(this).data('p'));
            });

        }, 'json');
    },
    del: function (_this) {

        //询问框
        layer.confirm('数据删除后将无法恢复!', {
            btn: ['确定', '取消'] //按钮
        }, function () {
            //loading层
            var index = layer.load(1, {
                shade: [0.1, '#fff'] //0.1透明度的白色背景
            });
            //删除数据
            var url = $('#form').data('delurl');
            var json = {};
            if ($(_this).data('id')) {
                $(_this).parent().parent().remove();
                json = {id: $(_this).data('id')};
            } else {
                var obj = Withdrawal.tobodyObj.find('input:checked');
                obj.parent().parent().remove();
                var val = {};
                $.each(obj, function (index, value) {
                    val[index] = value.value;
                });
                json = {id: val}

            }
            $.get(url, json, function (res) {
                $('.layui-layer-loading1').remove();
                $('.layui-layer-shade').remove();
                layer.msg(res.message);
            }, 'json');
            //删除数据

        }, function () {
            layer.msg('成功取消');
        });
    },


    doSome: function (sta) {
        var id = $(sta).attr('data-id');
        var status = $(sta).attr('data-status');
        if(status == 0){
            alert('确认审核通过？');
        }else if(status == 1){
            alert('确认已打款？');
        }
        $.post(TO_EXAMINE,{status:status,id:id},function(res){
            if(res.status == 1){
                if(res.data == 1){
                    alert('审核通过');
                    self.location.reload();
                }else if(res.data == 2){
                    alert('打款通过');
                    self.location.reload();
                }
            }
        });


    }



};

$(function () {
    Withdrawal.select(1);

});

$(function() {
    //当前页导出
    $(".current-export").on('click',function(){
        var start = $("#timeStart").val();
        var end = $("#timeEnd").val();
        var tel = $("#mobile").val();
        var status = $("#status").val();
        var data = {timeStart:start, timeEnd: end,mobile:tel,status:status};
        var url = EXCEL_OUT;
        submitForm(url,data);
    });
    /**
     * js模拟表单get提交
     * @param action url地址
     * @param params 要传递的值
     */
    function submitForm(action, params) {
        var form = $("<form></form>");
        form.attr('action', action);
        form.attr('method', 'get');
        form.attr('target', '_self');
        var input1 = $("<input type='hidden' name='timeStart' value='' />");
        var input2 = $("<input type='hidden' name='timeEnd' value='' />");
        var input3 = $("<input type='hidden' name='mobile' value='' />");
        var input4 = $("<input type='hidden' name='status' value='' />");
        input1.attr('value', params.timeStart);
        input2.attr('value', params.timeEnd);
        input3.attr('value', params.mobile);
        input4.attr('value', params.status);
        form.append(input1);
        form.append(input2);
        form.append(input3);
        form.append(input4);
        form.appendTo("body");
        form.css('display', 'none');
        form.submit();
    }
});