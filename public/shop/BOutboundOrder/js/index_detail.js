    $(function(){
        //构造树
        setUser();
    });
    function setUser(){
        $(".del").each(function(index, obj) {
            $(this).click(function(){
                var id=$(this).attr('name');
                delUser(id);
            })
        });
    }

    function delUser(id){
        $('#myModal').find('button').each(function(index, element){
            $(this).unbind("click").click(function(){
                if($(this).attr('id')=='del'){
                    $.ajax({
                        url: API_URL+"&m=BOutboundOrder&a=delete",
                        type: 'post',
                        data:{id:id},
                        success: function(data) {
                            if(data.status==1){
                                //alert(data.msg);
                                jump_refer();
                            }else{
                                alert(data.msg);
                            }

                        }
                    })
                }
            })
        });
    }
