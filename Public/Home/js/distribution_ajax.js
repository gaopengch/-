//页码
$(function () {
    distribution_ajax.search(1);
});
var distribution_ajax =
    {
        appendStr: '<dd class="clearfix">\n' +
        '<span class="fl z-timer">%id%</span>\n' +
        '<span class="fl z-timer">%oid%</span>\n' +
        '<span class="fl z-deposit">%time%</span>\n' +
        '<span class="fl z-expenditure">￥%total_price%</span>\n' +
        '<span class="fl z-expenditure">￥%rebate_money%</span>\n' +
        '<span class="fl z-expenditure">%nick_name%</span>\n' +
        '<span class="fl z-expenditure">%mobile%</span>\n' +
        '</dd>',
        search: function (page) {
            var json = {};
            json['startTime'] = $("input[name='startTime']").val();
            json['endTime'] = $("input[name='endTime']").val();
            json['nickName'] = $("input[name='nickName']").val();
            json['mobile'] = $("input[name='mobile']").val();
            json['type'] = $("select[name='type']").val();
            
            $.post(url + '?p=' + page, {data: json}, function (res) {
                var str = '';
                var appendStr = distribution_ajax.appendStr;
                if (res.status === 0) {
                    str = '<dd align="center" style="color:red;margin-top:10px;">' + res.message + '</dd>';
                    distribution_ajax.append(str);
                    return false;
                }
                for (var i = 0; i < res.data.data.length; i++) {
                    var appendStr = distribution_ajax.appendStr;
                    appendStr = appendStr.replace('%id%', res.data.data[i].id);
                    appendStr = appendStr.replace('%oid%', res.data.data[i].oid);
                    appendStr = appendStr.replace('%time%', res.data.data[i].time);
                    appendStr = appendStr.replace('%total_price%', res.data.data[i].total_price);
                    appendStr = appendStr.replace('%rebate_money%', res.data.data[i].rebate_money);
                    appendStr = appendStr.replace('%nick_name%', res.data.data[i].nick_name?res.data.data[i].nick_name:'无');
                    appendStr = appendStr.replace('%mobile%', res.data.data[i].mobile);
                    str += appendStr;
                }
                page = '<div class="page">' + res.data.page + '</div>';
                distribution_ajax.append(str, page);
                return false;

            });

        },
        append: function (str, page ) {
            var obj = $('.z-balance-detailed');
            obj.find('dd').remove();
            obj.find('dl').append(str);
            if (page !== '') {
                obj.find('div').remove();
                obj.find('dl').after(page);
                $('.pagination a').on('click', function () {
                    distribution_ajax.search($(this).data('p'));
                });
            }
        },

        withdrawal: function (_this) {
            layer.open({
                type: 1,
                area: ['600px', '400px'],
                shade: 0.5,
                title: false, //不显示标题
                content: '<div id="distribution_withdrawal" ><script >var loding = layer.load(0, {shade: false}); </script></div>',
                cancel: function () {
                    $('.layui-layer-loading0').remove();
                }
            });

            $.post($(_this).data('url'), {}, function (str) {
                $('.layui-layer-loading0').remove();
                $('#distribution_withdrawal').html(str);

            });


        }




    };