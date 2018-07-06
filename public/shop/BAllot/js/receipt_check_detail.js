    $(".pass").on('click',function(){
        var id=$(this).attr("name");
        var check_memo=$('#check_memo').val();
        $.ajax({
            url: API_URL+"&m=BAllot&a=receipt_check_post",
            type: 'post',
            data:{id:id,check_memo:check_memo,type:1},
            beforeSend:function(){
                $(".pass").attr("disabled",true);
                $(".pass").text("审核中...");
            },
            success: function(data) {
                if(data.status==1){
                    $(".tips_error").text("审核完成！");
                    setTimeout(self.location=data.url,3000);
                }else{
                    $(".tips_error").text(data.msg);
                    $(".pass").text("审核通过");
                    $(".pass").attr("disabled",false);
                }
            }

        })
    })

    $(".nopass").on('click',function(){
        var id=$(this).attr("name");
        var check_memo=$('#check_memo').val();
        $.ajax({
            url: API_URL+"&m=BAllot&a=receipt_check_post",
            type: 'post',
            data:{id:id,check_memo:check_memo,type:2},
            beforeSend:function(){
                $(".nopass").attr("disabled",true);
                $(".nopass").text("审核中...");
            },
            success: function(data) {
                if(data.status==1){
                    $(".tips_error").text("审核完成！");
                    setTimeout(self.location=data.url,3000);
                }else{
                    $(".tips_error").text(data.msg);
                    $(".nopass").text("审核不通过");
                    $(".nopass").attr("disabled",false);
                }

            }
        })
    });



