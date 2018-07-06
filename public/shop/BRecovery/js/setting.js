    var expression=$('input[name=expression]');
    /*$('p.save').click(function(){
        var url=$('form').attr('action');
        $.ajax({
            url:url,
            type:'post',
            data:{expression:expression.val()},
            dataType:"json",
            success:function(result){
                var code=result.code;
                var str="";
                if(code==200){
                    str="操作成功！";
                }else{
                    str="操作失败！";
                }
                $(".form-actions").append('<p class="tips" style="display:inline;margin-left:20px;color:red;">'+str+'</p>');
                setTimeout(function(){$('.form-actions .tips').remove();},1000);
            }
        });
    });*/
    $('#del').click(function(){
        expression.val("");
    });
    $('#round').click(function(){
        var value=expression.val();
        expression.val("round("+value+")");
    });
    $('#ceil').click(function(){
        var value=expression.val();
        expression.val("ceil("+value+")");
    });
    $('#floor').click(function(){
        var value=expression.val();
        expression.val("floor("+value+")");
    });
    $('#plus').click(function(){
        var value=expression.val();
        expression.val(value+"+");
    });
    $('#minus').click(function(){
        var value=expression.val();
        expression.val(value+"-");
    });
    $('#multiply').click(function(){
        var value=expression.val();
        expression.val(value+"*");
    });
    $('#divide').click(function(){
        var value=expression.val();
        expression.val(value+"/");
    });
    $('#price').click(function(){
        var value=expression.val();
        expression.val(value+"price");
    });
