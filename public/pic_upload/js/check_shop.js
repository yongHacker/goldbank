$(".baocun").click(function(){
    checkjhmc();checkcoded();checkqymc();checkemail();checkzxfzr_dh();
    if($("#jhmc").val()==""){
        alert("店铺名称不能为空");
        return false;
    }
    if($("#ssyyzx").val()==""){
        alert("所属运营中心不能为空");
        return false;
    }
    if($("#yyzx_id").val()==""){
        alert("所属运营中心填写错误");
        return false;
    }
    if($("#coded").val()==""){
        alert("行号不能为空");
        return false;
    }
    if($("#qymc").val()==""){
        alert("企业名称不能为空");
        return false;
    }

        // if($("#baidu").val()==""&&$("#u_id").val()==""){
        //     return true;
        // }
        if($("#u_id").val()==""){
            alert("行长必须为用户");
            return false;
        }

    if($("#fzr_id").val()==""){
        alert("负责人信息填写错误");
        return false;
//                if($("zxfzr").val()==""){
//                    alert("负责人信息填写错误");
//                    return false;
//                }
    }
    //判断手机格式是否正确
    var myreg = /^(0|86|17951)?(13[0-9]|15[012356789]|17[678]|18[0-9]|14[57])[0-9]{8}$/;
    if(!$.trim($('#zxfzr_dh').val())) {
        alert("请填写电话号码")
        return false;
    }
    if(!myreg.test($('#zxfzr_dh').val())){
        alert("电话号码格式错误")
        return false;
    }




////            alert($("#agent_level option:selected").text());return false;
//            if($("#agent_level option:selected").text()=="请选择代理级别"||$("#agent_level option:selected").text()==""){
//                alert("请选择代理级别");
//                return false;
//            }

    if(checkjhmc()&&checkcoded()&&checkqymc()&&checkzxfzr_dh()){
        $.ajax({
            url: "{:U('Shop/add')}",
            type: 'post',
            data: $('.user_bc').serialize(),
            beforeSend:function(){
                $(".baocun").attr("disabled",true);
            },
            success: function (data) {
                if(data=="success"){
                    window.location.href = "{:U('Shop/index')}";

                }else{
                    window.location.href = "{:U('Shop/index')}";
                }
                $(".baocun").attr("disabled",false);
            }
        })
    }
})
$('#jhmc').blur(function(){
    checkjhmc();
})
$('#coded').blur(function(){
    checkcoded();
})
$('#qymc').blur(function(){
    checkqymc();
})
//        $('#zcdz').blur(function(){
//            checkzcdz();
//        })
$('#email').blur(function(){
    checkemail();
})
$('#zxfzr_dh').blur(function(){
    checkzxfzr_dh();
})
//        $('#business_area').blur(function(){
//            checkbusiness_area();
//        })
//        $('#area').blur(function(){
//            checkarea();
//        })
//        $('#address').blur(function(){
//            checkaddress();
//        })


function checkjhmc(){
    if(!$.trim($('#jhmc').val())){
        $('#jhmcwar').text('请填写金行名称');
        return false;
    }else{
        $('#jhmcwar').text('');
        return true;
    }
}

function checkcoded(){
    if(!$.trim($('#coded').val())){
        $('#codedwar').text('请填写编号');
        return false;
    }else{
        $('#codedwar').text('');
        return true;
    }
}
function checkqymc(){
    if(!$.trim($('#qymc').val())){
        $('#qymcwar').text('请填写企业名称');
        return false;
    }else{
        $('#qymcwar').text('');
        return true;
    }
}
function checkzcdz(){
    if(!$.trim($('#zcdz').val())){
        $('#zcdzwar').text('请填写注册地址');
        return false;
    }else{
        $('#zcdzwar').text('');
        return true;
    }
}
function checkzxfzr_dh(){
    if(!$.trim($('#zxfzr_dh').val())){
        $('#zxfzr_dhwar').text('请填写电话号码');
        return false;
    }else{
        $('#zxfzr_dhwar').text('');
        return true;
    }
}
function checkbusiness_area(){
    if(!$.trim($('#business_area').val())){
        $('#business_areawar').text('请填写经营范围');
        return false;
    }else{
        $('#business_areawar').text('');
        return true;
    }
}
function checkarea(){
    if(!$.trim($('#area').val())){
        $('#areawar').text('请填写所属区域');
        return false;
    }else{
        $('#areawar').text('');
        return true;
    }
}
function checkaddress(){
    if(!$.trim($('#address').val())){
        $('#addrewar').text('请填写地址');
        return false;
    }else{
        $('#addrewar').text('');
        return true;
    }
}

function checkemail(){		//判断邮箱格式是否正确
    var myreg = /^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,3}$/;
    if(!$.trim($('#email').val())) {
//                $('#emailwar').text('请填写邮箱');
        return true;
    }
    if(!myreg.test($('#email').val())){
        $('#emailwar').text('邮箱格式错误');
        return false;
    }
    else{
        $('#emailwar').text('');
        return true;
    }
}
function checkzxfzr_dh(){			//判断手机格式是否正确
    var myreg = /^(0|86|17951)?(13[0-9]|15[012356789]|17[678]|18[0-9]|14[57])[0-9]{8}$/;
    if(!$.trim($('#zxfzr_dh').val())) {
        $('#zxfzr_dhwar').text('请填写电话号码');
        return false;
    }
    if(!myreg.test($('#zxfzr_dh').val())){
        $('#zxfzr_dhwar').text('电话号码格式错误');
        return false;
    }
    else{
        $('#zxfzr_dhwar').text('');
        return true;
    }
}
