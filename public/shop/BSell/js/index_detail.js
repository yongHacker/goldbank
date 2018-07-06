        　       $(function(){
                    setUser();
                });
                function setUser(){
                    $(".del").each(function(index, obj) {
                        $(this).unbind().click(function(){
                            var id=$(this).attr('name');
                            delUser(id);
                        })
                    });
                }
                function delUser(id){
                    $('#myModal').find('button').each(function(index, element) {
                        $(this).unbind().click(function(){
                            if($(this).attr('id')=='del'){
                                $.ajax({
                                    url: API_URL+"&m=BSell&a=revoke",
                                    type: 'post',
                                    data:{id:id},
                                    success: function(data) {
                                        if(data.status==1){
                                            window.location.href = data.url;
                                        }
                                        else{
                                            alert(data.msg);
                                        }
                                    }
                                })
                            }
                        })
                    });
                }
