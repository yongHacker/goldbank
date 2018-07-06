    $('.js-ajax-submit').click(function(){
        $('.js-ajax-submit').attr('disabled','disabled');
        var id=$('#id').val();
        var type=$(this).data("type");
        var url =API_URL+"&m=BRecovery&a=check_post";
        var check_memo=$('#check_memo').val();
            $.ajax({
                type:'post',
                data:{id:id,check_memo:check_memo,type:type},
                dataType:'json',
                url:url,
                success:function(result){
                    if(result.status=="1"){
                        $('.tishi').html('操作成功');
                        window.location.href = result.url;
                        //self.location=document.referrer;
                    }else{
                        $('.js-ajax-submit').attr('disabled','');
                        if(result.info){
                            $('.tishi').html(result.info);
                        }else{
                            $('.tishi').html('操作失败');
                        }
                    }

                }
            });
    });
