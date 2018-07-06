    var detail_list=$("input[name='detail_list']").val();
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
    if(detail_list == '' || detail_list == null || typeof(detail_list) == 'undefined'){
        $('.ta').hide();
        $('.ts').show();
    }

    refresh_redio();
    function refresh_redio()
    {
        var detail_ids = localStorage.getItem('detail_ids' + param_get('location_id'));
        if (detail_ids != null && detail_ids != '') {
            detail_ids = detail_ids.split(',');
        } else {
            detail_ids = ''
        }
        var _tr = $('tbody').find('tr');
        _tr.each(function(){
            var tr = $(this);
            if (detail_ids.indexOf(String(tr.find('td:first').data('detailid'))) != -1) {
                tr.find('input[type=checkbox]').attr("checked", true);
            } else {
                tr.find('input[type=checkbox]').attr("checked", false);
            }
        });
    }
