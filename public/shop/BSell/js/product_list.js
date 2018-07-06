    $('.check_tr').click(function(){
        var checkbox=$(this).find('input[type=checkbox]');
        if(checkbox.is(':checked')){
            checkbox.prop('checked',false);
        }else{
            checkbox.prop('checked',true);
        }
    });
    $('.goods_input').click(function(){
        if($(this).is(':checked')){
            $(this).prop('checked',false);
        }else{
            $(this).prop('checked',true);
        }
    });
    $("#th_input").click(function(){
        var attr=$(this).attr("obj");
        if(attr){
            $(this).attr("obj","");
            $("input[type='checkbox']").prop("checked","");
        }else{
            $(this).attr("obj","obj");
            $("input[type='checkbox']").prop("checked","checked");
        }

    });

    //刷新页面读取已勾选数据
    var check=localStorage.getItem('p_id');
    if(check!=""&&check!=null&&typeof(check)!="undefined"){
        var checked=check.split(',');
        for(var i in checked){
            if(checked[i]!=null&&checked[i]!=""&&typeof(checked[i])!="undefined"){
                var checkbox=$("input[p_id='"+checked[i]+"']");

                if(checkbox.length>0){
                    var img= '<img p_id="'+checked[i]+'" src="/public/images/gou.png"/>';
                    checkbox.parent().append(img);
                    // checkbox.parent().html(img);
                    //checkbox.remove();
                    checkbox.prop('checked',false);
                    checkbox.hide();
                }
            }
        }
    }
    //商品分类  获取分类的数据
    //    productVariety();
