    $(".js-ajax-submit").on('click',function(){
        var id=$("#id").val();
        var check_memo=$('#check_memo').val();
        var type=$(this).data('type');
        $.ajax({
            url: API_URL+"&m=BOutboundOrder&a=check_post",
            type: 'post',
            data:{id:id,check_memo:check_memo,type:type},
            beforeSend:function(){
                $(".js-ajax-submit").attr("disabled",true);
            },
            success: function(data) {
                if(data.status==1){
                    $(".tips_error").text("审核完成！");
                    setTimeout(self.location=data.url,3000);
                }else{
                    $(".tips_error").text(data.msg);
                    $(".js-ajax-submit").attr("disabled",false);
                }
            }

        })
    })
