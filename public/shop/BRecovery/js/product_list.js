    $('.check_tr').click(function(){
        var checkbox=$(this).find('input[type=radio]');
        if(checkbox.is(':checked')){

        }else{
            checkbox.prop('checked',true);

        }
    });
    $('.check_box').click(function(){
        if($(this).is(':checked')){

        }else{
            $(this).prop('checked',true);

        }
    });
