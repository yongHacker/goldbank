/**
 * add by alam 2018/06/12 其它费用公用js
 */
// 运行附加的function，用于添加或删除其它费用时调用页面的方法
var expence_postfix_function = new Array();
// 使用方法 自动加载 expence_postfix_function = ['test'];
function operation_function(){
    if (typeof expence_postfix_function != 'undefined') {
        for (var i = 0; i < expence_postfix_function.length; i++) {
            eval(expence_postfix_function[i] + '()');
        }
    }
}

// 添加一行新的其它费用
$("#expence_add").unbind('click').click(function() {
    var expence_html = $("#expence_html").html();
    var html = '<tr>'
             + '<td class="text-center"></td>'
             + '<td class="text-center">'+expence_html+'</td>'
             + '<td class="text-center"><input type="number" name="expence_price" autocomplete="off" step="0.001" value="0.00" placeholder="类目金额"></td>'
             + '<td class="text-center" role="button"><a class="del fa fa-trash" title="删除"></a></td>'
             + '</tr>';
    $('#expence_last').before(html);
    
    expence_table_order();

    expence_del_input();

    expence_alter();
})

// 修改其它费用时运行附加函数
expence_alter();
function expence_alter() {
    $('input[name=expence_price]').unbind('keyup').keyup(function() {
        operation_function();
    });
}

// 其它费用表格排序
function expence_table_order(){
    var len = $('#expence_table').find('table tr').length;
    for(var i = 1; i < len; i ++){
        $("#expence_table").find('table tr:eq('+i+') td:first').text(i);
    }
    // 增删行都会重新排序，关联运行附加funciton
    operation_function();
}

// 其它费用表格删除行方法绑定
expence_del_input();
function expence_del_input(){
    $('#expence_table').find('.del').each(function(){
        $(this).unbind('click').click(function(){
            $(this).parent('td').parent('tr').remove();
            expence_table_order();
        });
    });
}

// 获取其它费用数据
function get_expence_data() {
    var sub_datas = [];
    var expence_tr = $('#expence_table').find('table tbody[id="expence_tbody"] tr');
    var is_false = false;
    expence_tr.each(function() {
        var tr_ = $(this);
        if (tr_.attr('id') != 'expence_last' && is_false == false) {
            var sub_id = $(this).data('subid');
            var expence_id = $(this).find('td').find('select[name=expence_id]').val();
            var expence_cost = $(this).find('td').find('input[name=expence_price]').val();
            if (parseInt(expence_id) == 0) {
                var num = $(this).find('td:first').text();
                artdialog_alert('请选择序号为' + num + '的其它费用费用类目！');
                is_false = true;
                return false;
            }
            sub_datas.push({
                'sub_id' : sub_id,
                'expence_id': expence_id,
                'sub_cost': expence_cost
            });
        }
    })
    if (is_false) {
        return false;
    }
    return sub_datas;
}

// 统计其它费用的总金额
function count_expence_price() {
    var expence_price = 0;
    $('#expence_table').find('table tr').each(function() {
        var tr = $(this);
        if (tr.attr('class') != 'expence_last') {
            var _expence_price = parseFloat(tr.find('td').find('input[name=expence_price]').val());
            expence_price += (isNaN(_expence_price) ? 0 : _expence_price);
        }
    });
    return expence_price;
}