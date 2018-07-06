    $('.check_tr').unbind().click(function(){
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
    var check = localStorage.getItem('checkbox_id');
    if(check){
        var tr_all=$("tr");
        tr_all.each(function(){
            var all_checkbox_id=$(this).find("td:nth-child(1)").find("input").attr("checkbox_id");
            if(all_checkbox_id){
                var index=check.indexOf(all_checkbox_id);
            }
            if(index>=0){
                var img= "<img checkbox_id='"+all_checkbox_id+"' src='/public/images/gou.png'/>";
                var checkbox=$("input[checkbox_id='"+all_checkbox_id+"']");
                checkbox.parent().append(img);
                checkbox.prop('checked',false);
                checkbox.hide();
            }
        });
    }
