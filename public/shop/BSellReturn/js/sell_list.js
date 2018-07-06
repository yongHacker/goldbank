    var sell_list=$("input[name='sell_list']").val();
    $('.check_tr').unbind().click(function(){
        var checkbox = $(this).find('input[type=checkbox]');
        if(checkbox.is(':checked')){
            checkbox.prop('checked',false);
        }else{
            checkbox.prop('checked',true);
        }
    });
    $('.check_box').click(function(){
        if($(this).is(':checked')){
            $(this).prop('checked',false);
        }else{
            $(this).prop('checked',true);
        }
    });
    $('#check_all').click(function(){
        if(this.checked){
            $('.check_box').prop('checked',true);
        }else{
            $('.check_box').prop('checked',false);
        }
    });
    if(sell_list == '' || sell_list == null || typeof(sell_list) == 'undefined'){
        $('.ta').hide();
        $('.ts').show();
    }
