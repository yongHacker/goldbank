$('.check_tr').click(function(){
    var checkbox=$(this).find('input[type=radio]');
    if(checkbox.is(':checked')){
        /*checkbox.prop('checked',false);
        localStorage.removeItem('u_name'); 
        localStorage.removeItem('uid'); 
        localStorage.removeItem('mobile');*/
    }else{
        checkbox.prop('checked',true);
        var name=$(this).find('.check_box').attr("u_name");
        var uid=$(this).find('.check_box').attr("uid");
        var mobile=$(this).find('.check_box').attr("mobile");
        //alert(name);
        localStorage.setItem('sell_name',name);
        localStorage.setItem('sell_uid',uid);
        localStorage.setItem('sell_mobile',mobile);
    }
});
$('.check_box').click(function(){
    if($(this).is(':checked')){
        /*$(this).prop('checked',false);*/
        var name=$(this).attr("u_name");
        var uid=$(this).attr("uid");
        var mobile=$(this).attr("mobile");
        //alert(name);
        localStorage.setItem('sell_name',name);
        localStorage.setItem('sell_uid',uid);
        localStorage.setItem('sell_mobile',mobile);
    }else{
        $(this).prop('checked',true);
        /*localStorage.removeItem('u_name'); 
        localStorage.removeItem('uid'); 
        localStorage.removeItem('mobile');*/
    }
});

$('#from').click(function(){
    var mobile =$('#mobile').val();
    //var myreg = /^1[3|5|7|8]\d{9}$/; 
    /*  if(mobile ==""){
            $('.tishi').html('请输会员姓名或入手机号码');
            $('.tishi').show();
            $("#table").html('');
            $('#hide').hide();
            return false; 
        }*/

         /* $.ajax({
                type: 'post',  
                url: "{:U('Sells/p_list')}",  
                cache:false,//不从浏览器缓存中加载请求信息  
                data: {mobile : mobile},//向服务器端发送的数据  
                dataType: 'json',//服务器返回数据的类型为json  
                success: function (json) {  
                        if(json.status ==1){
                        $('#hide').hide();
                        $('.tishi').hide();
                    } else {  
                    //  $("#table").html('');
                        $('#hide').show();
                        $('.tishi').hide();
                   
                    }  
                }  
            }); 
           */
        });

function keyLogin(event){
 if (event.keyCode==13|| event.whick==13)  //回车键的键值为13
   document.getElementById("from").click(); //调用登录按钮的登录事件
}

