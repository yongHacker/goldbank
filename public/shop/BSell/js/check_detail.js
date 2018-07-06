//审核通过
    $('.js-ajax-submit').click(function(){
        $(this).attr('disabled','disabled');
        $('.js-ajax-submitno').attr('disabled','disabled');
    var id=$('#id').val();
    var url =API_URL+"&m=BSell&a=check_post";
    var check_memo=$('#check_memo').val();
        $.ajax({
            type:'post',
            data:{id:id,check_memo:check_memo,type:1},
            dataType:'json',
            url:url,
            success:function(result){
                if(result.status=="1"){
                    $('.tishi').html('操作成功');
                    window.location.href = result.url;
                    //self.location=document.referrer;
                }else{
                    $(this).attr('disabled','');
                    $('.js-ajax-submitno').attr('disabled','');
                    $('.tishi').html('操作失败');
                }
                
            }
        });
    });
    //审核不通过
    $('.js-ajax-submitno').click(function(){
        $(this).attr('disabled','disabled');
        $('.js-ajax-submit').attr('disabled','disabled');
        var id=$('#id').val();
        var url =API_URL+"&m=BSell&a=check_post";
        var check_memo=$('#check_memo').val();
        $.ajax({
            type:'post',
            data:{id:id,check_memo:check_memo,type:2},
            dataType:'json',
            url:url,
            success:function(result){
                if(result.status=="1"){
                    $('.tishi').html('操作成功');
                    window.location.href = result.url;
                }else{
                    $(this).attr('disabled','');
                    $('.js-ajax-submit').attr('disabled','');
                    $('.tishi').html('操作失败');
                }
            }
        });
    });
    
