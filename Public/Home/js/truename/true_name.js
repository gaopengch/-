
/*Ajax上传至后台并返回图片的url*/
function bindAvatar1(envent) {
    var formData=new FormData();
    formData.append('img', $(envent)[0].files[0]);  /*获取上传的图片对象*/

    $.ajax({
        url: addImg,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (res) {
            console.log(res);  /*服务器端的图片地址*/
            $(envent).siblings('img').attr('src',res.data);  /*预览图片*/
            $(envent).siblings('input').val(res.data);  /*将服务端的图片url赋值给form表单的隐藏input标签*/
            console.log($(envent).siblings('input'))
        }
    })

}

function save_true_name(){
    var true_name = $('#true_name').val();
    var card_id = $('#card_id').val();
    var front_img = $('#front_img').val();
    var back_img = $('#back_img').val();

    if(true_name == ''){
        layer.tips('请填写姓名!', '#true_name');
        return false;
    }
    if(card_id == ''){
        layer.tips('请填写身份证号!', '#card_id');
        return false;
    }
    if(front_img == ''){
        layer.tips('请上传身份证正面照!', '#front_img2');
        return false;
    }
    if(back_img == ''){
        layer.tips('请上传身份证反面照!', '#back_img2');
        return false;
    }
    return true;
}
