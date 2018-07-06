/**
 * create by alam 2016/06/13
 * 销售退货的页面操作、数据提交js
 */

// 当前页面的标识ID
var location_id = location_id();
// 当前页面的操作类型 add-添加 edit-编辑
var action_name = param_get('a');
var action_type = action_name == 'add' ? 1: (action_name == 'edit' ? 2: 0);

// 切换门店 切换iframe地址 修改退款方式
$('#shop_id').change(function() {
	update_iframe_url();
	$('.plus').remove();
	reflush_table();
	init_payment();
});

// 修改单据详情 - 抹零金额
$('input[name=extra_price]').unbind('keyup').keyup(function(){
	reflush_table();
});

// 修改单据详情 - 退款总额
$('#return_price').unbind('keyup').keyup(function(){
    stat_finance();
});

// 修改表单明细 - 退款金额
change_pay_price();
function change_pay_price(){
    $("input[name='pay_price']").unbind('keyup').keyup(function() {
        var val = $(this).val();
        if (val < 0) {
            $(this).val(val * -1);
        }
        stat_finance();
    });
}
// 修改表单列表 - 退货价格
change_detail_return_price();
function change_detail_return_price(){
    $("input[name='detail_return_price']").unbind('keyup').keyup(function() {
        reflush_table();
    });
}

// 改变iframe的url
function update_iframe_url(){
	var client_id = $('#client_id').val();
	var shop_id = $('#shop_id').val();

	var sell_iframe = $('#sell_iframe');
	var product_iframe = $('#product_iframe');

	var url_postfix = '&client_id=' + client_id + '&shop_id=' + shop_id + '&location_id=' + location_id;

	sell_iframe.attr('src', sell_iframe.data('src') + url_postfix); 
	product_iframe.attr('src', product_iframe.data('src') + url_postfix); 
}

// 选择客户
$('#select_client').click(function(){
    var ckbox = $("#client_iframe").contents().find('.check_box:checked');

    if(ckbox.length != 0){
        var client_id = ckbox.attr("client_id");
        var name = ckbox.attr("u_name");
        var mobile = ckbox.attr("mobile");
        var client_idno = ckbox.attr("client_idno");

        if(empty(name)){
            name = '';
        }
        $('#client_id').val(client_id);
        $('#mobile').val(name);
        $('#click_name').val(name + '(' + mobile + ')');
        $('#client_idno').val(client_idno);
        // 切换iframe地址
        update_iframe_url();
    }
})

$('#sell_iframe').load(function(){
	$("#sell_check").unbind('click').click(function() {
        $('.sellModal').html('导入中...');

		var url = $(this).data('url');

		var html = $('#sell_iframe').contents();
        var tr = html.find('.check_tr');

        // 选中的销售单ID列表
        var sell_ids = "0";
        tr.each(function(){
            if($(this).find('.check_box').is(':checked')){
                sell_ids += "," + $(this).find('.check_box').attr('id');
            }
        });

        var return_id = $("#return_id").val();
        var client_id = $("#client_id").val();
        var shop_id = $("#shop_id").val();
		var detail_ids = refresh_detail_ids();
        var append_html = '';

		$.ajax({
            url : url,
            data : {sell_ids:sell_ids, detail_ids:detail_ids, return_id:return_id, client_id:client_id, shop_id:shop_id},
            type : 'post',
            dataType : 'json',
            async : false,
            success : function(result){
                var product_list = result.datas.product_list;
                for(var i = 0; i < product_list.length; i ++){
                	var product = product_list[i];
					if (!check_exist(product.detail_id)) {
						append_html += _add_row(
							product.detail_id,
							product.product_id,
							product.product_pic,
							product.goods_name,
							product.goods_code,
                            product.product_code,
                            product.sub_product_code,
							product.p_total_weight,
							product.purity,
							product.p_gold_weight,
							product.sell_feemode,
							product.detail_sell_fee,
							product.gold_price,
							product.sell_price,
							product.discount_price,
							product.actual_price,
							product.sell_pricemode
						);
					}
				}

                $("#detail_last").before(append_html);
                change_detail_return_price();
                $('.sellModal').html('选择销售单');
            }
        });

		reflush_table();
		del_input();
	});
	$("#sell_add").unbind('click').click(function() {
		$("#sell_check").trigger("click");
	});
});

/**
 * product iframe 点击操作
 */
$('#product_iframe').load(function() {
	$("#product_check").unbind('click').click(function() {
		var html = $('#product_iframe').contents();
		var tr = html.find("input[class='check_box']:checked").parent().parent();
		var append_html = '';
		tr.each(function() {
			var tr_ = $(this);
			if (!check_exist(tr_.find('td:first').data('detailid'))) {
				append_html += _add_row(
					tr_.find('td:first').data('detailid'),
					tr_.find('td:first').data('productid'),
					tr_.find('.product_pic').text(),
					tr_.find('.goods_name').text(),
					tr_.find('.goods_code').text(),
                    tr_.find('.product_code').text(),
                    tr_.find('.sub_product_code').text(),
					tr_.find('.p_total_weight').text(),
					tr_.find('.purity').text(),
					tr_.find('.p_gold_weight').text(),
					tr_.find('.sell_feemode').text(),
					tr_.find('.detail_sell_fee').text(),
					tr_.find('.gold_price').text(),
					tr_.find('.sell_price').text(),
					tr_.find('.discount_price').text(),
					tr_.find('.actual_price').text(),
					tr_.find('.sell_pricemode').text()
				);
			}
		});
		$("#detail_last").before(append_html);
        change_detail_return_price();
        
		reflush_table();
		del_input();
	});
	$("#product_add").unbind('click').click(function() {
		$("#product_check").trigger("click");
	})
});

// 添加一行退货详情
function _add_row(detail_id, product_id, product_pic, goods_name, goods_code, product_code, sub_product_code, p_total_weight, purity, p_gold_weight, sell_feemode, detail_sell_fee, gold_price, sell_price, discount_price, actual_price, sell_pricemode){
	if (product_pic == null) {
		product_pic = '/admin/themes/simplebootx/Public/assets/images/default-thumbnail.png';
	}
	var html = '';
	html += '<tr class="plus" data-detailid="' + detail_id + '" data-productid="' + product_id + '">';
	html += '<td class="text-center"></td>'; // 序
    html += '<td class="text-center"><img src="' + product_pic.replace(/^\s+|\s+$/g,"") + '"></td>'; // 图片
    html += '<td class="text-left">' + goods_name.replace(/^\s+|\s+$/g,"") + '</td>'; // 品目
    html += '<td class="text-center">' + goods_code.replace(/^\s+|\s+$/g,"") + '</td>'; // 商品编号
    html += '<td class="text-center">' + product_code.replace(/^\s+|\s+$/g,"") + '</td>'; // 货品编号
    html += '<td class="text-center">' + sub_product_code.replace(/^\s+|\s+$/g,"") + '</td>'; // 附属货品编号
    html += '<td class="text-right">' + p_total_weight.replace(/^\s+|\s+$/g,"") + '</td>'; // 重量
    html += '<td class="text-center">' + purity.replace(/^\s+|\s+$/g,"") + '</td>'; // 含金量
    html += '<td class="text-right">' + p_gold_weight.replace(/^\s+|\s+$/g,"") + '</td>'; // 金重
    html += '<td class="text-center">' + sell_feemode.replace(/^\s+|\s+$/g,"") + '</td>'; // 销售工费方式
    html += '<td class="text-right">' + detail_sell_fee.replace(/^\s+|\s+$/g,"") + '</td>'; // 工费
    html += '<td class="text-right">' + gold_price.replace(/^\s+|\s+$/g,"") + '</td>'; // 克单价
    html += '<td class="text-right">' + sell_price.replace(/^\s+|\s+$/g,"") + '</td>'; // 建议售价
    html += '<td class="text-right">' + discount_price.replace(/^\s+|\s+$/g,"") + '</td>'; // 优惠金额
    html += '<td class="text-right">' + actual_price.replace(/^\s+|\s+$/g,"") + '</td>'; // 实际售价
    html += '<td class="text-center">' + sell_pricemode.replace(/^\s+|\s+$/g,"") + '</td>'; // 计价方式
    html += '<td class="text-center"><input type="number" step="0.001" autocomplete="off" name="detail_return_price" class="no_arrow" value="' + parseFloat(actual_price.replace(/[^\d\.-]/g, "")).toFixed(2) +'"></td>'; // 退货价
    html += '<td class="text-center" style="vertical-align: inherit;"><a class="del fa fa-trash" title="删除"></a></td>'; // 删除
    html += '</tr>';
    return html;
}

// 判断货品编码或者销售详情ID是否已存在表单列表中
function check_exist(detail_id) {
	var detail_ids = refresh_detail_ids();
	detail_ids = detail_ids.split(',');
	var exist = detail_ids.indexOf(String(detail_id));
	return (exist == -1) ? false : true;
}

// 刷新localStorage中的销售详情ID并返回ID序列
function refresh_detail_ids() {
	var _tr = $('#return_product').find('table').find('tbody').find('tr');
	var detail_ids = '';
	_tr.each(function() {
		if ($(this).attr('id') != 'detail_last') {
			var detail_id = $(this).data('detailid');
			detail_ids += (String(detail_id) + ',');
		}
	});
	detail_ids = detail_ids.substring(0, detail_ids.length - 1);
	localStorage.setItem('detail_ids' + location_id, detail_ids);
	refresh_ifram_redio();
	return detail_ids;
}

// 刷新货品iframe中选择框
function refresh_ifram_redio()
{
	if (typeof document.getElementById("product_iframe").contentWindow.refresh_redio == 'function') {
		document.getElementById("product_iframe").contentWindow.refresh_redio();
	}
}

// 统一计算表单
function reflush_table()
{
	table_order();
	count_num();
	count_pw();
	refresh_detail_ids();
    stat_finance();
}

/* 退款明细 start */

// 获取退款列表数据
function getall_pay_type(){
    var is_true = true;
    var datas = [];
    var tr = $(".ta").find('table tbody tr[class="pay_type_tr"]');
    var i = 1;
    tr.each(function(){
        var pay_id = $(this).find(".pay_id").find('select').val();
        var currency= $(this).find(".currency").find('select').val();
        var currency_rate = $(this).find(".exchange_rate").text();
        var flow_id = $(this).find(".flow_id").find('input').val();
        var pay_price = $(this).find(".pay_price").find('input').val();
        var actual_price = $(this).find(".actual_price").text();
        var saccount_id = $(this).find("input[name='saccount_id']").val();
        if(!pay_id || pay_id == 0){
            error_appear('退款明细第'+i+'行未选择退款方式');
            is_true = false;
            return false;
        }
        if(!currency || currency==0){
            error_appear('退款明细第'+i+'行退款方式未选择币种');
            is_true = false;
            return false;
        }
        if(!pay_price || pay_price==0){
            error_appear('退款明细第'+i+'行退款方式未填写金额');
            is_true = false;
            return false;
        }
        i = i + 1;
        if(actual_price && currency && pay_id){
            datas.push({
                'pay_id': pay_id,
                'flow_id': flow_id,
                'currency': currency,
                'currency_rate': currency_rate,
                'pay_price': pay_price,
                'actual_price': actual_price,
                'saccount_id': saccount_id
            });
        }
    });
    if(is_true){
        return JSON.stringify(datas);
    }else {
        return false;
    }
}

// 初始化收款方式
function init_payment(){
    var change_select = $('select[name=pay_id]');
    var sector_obj = $('#shop_id');
    var payments_json = JSON.parse(payments);
    var sector_id = sector_obj.val();
    var common_payment = sector_obj.find("option:selected").attr('show_common_payment');

    var job_html = '<option value="0">选择收款方式</option>';
    $.each(payments_json, function (n, v) {
        if(sector_id == v.shop_id || (v.shop_id == 0 && common_payment == '1')){
            job_html += '<option value="' + v.id+ '" pay_type="' + v.pay_type + '">' + v.pay_name + '</option>';
        }
    });
    change_select.html(job_html);
}

// 还原旧的退款明细 - 退款方式
function rollback_pay_type()
{
    $("select[name='pay_id']").each(function(key, val){
        var tr = $(this).closest('tr');
        var pay_id = tr.data('payid');
        if (typeof(pay_id) != 'undefined' && pay_id != '') {
            var option = $(this).find("option[value=" + pay_id + "]");
            option.attr("selected", true);
        }
    });
}

// 联动 - 添加一列收款记录
function add_pay_type(){
    $("#add_pay_type").unbind('click').click(function () {
        var pay_id_html=$("#pay_html").html();
        var currency_html=$("#currency_html").html();
        var html='<tr class="pay_type_tr">';
        html+='<td class="text-center"></td>';
        html+='<td class="text-center pay_id">'+pay_id_html+'</td>';
        html+='<td class="text-center currency">'+currency_html+'</td>';
        html+='<td class="text-center pay_price"><input type="number" step="0.001" autocomplete="off" name="pay_price" value="0.00" class="no_arrow right" placeholder="收款金额"></td>';
        html+='<td class="text-center unit"></td>';
        html+='<td class="text-center exchange_rate"></td>';
        html+='<td class="text-right actual_price">0.00</td>';
        html+='<td class="text-center pay_del" style="color: #3daae9;cursor:pointer;">删除</td>';
        html+='</tr>';
        $(".pay_tbody").find("#pay_last").before(html);
        table_order();
        change_exchange_rate();
        del_pay_type();
        change_pay_price();
    })
}

// 联动 - 删除收款记录
function del_pay_type() {
    $(".pay_del").unbind("click").click(function() {
        // 添加被删除的收款记录id
        var saccount_id = $(this).parent("tr").find("input[name='saccount_id']").val();
        if (typeof(saccount_id) != 'undefined' && saccount_id != '') {
            var del_saccount_detail = $('#del_saccount_detail').val();
            $('#del_saccount_detail').val(del_saccount_detail + (del_saccount_detail == '' ? '' : ',') + saccount_id);
        }
        // 移除行
        $(this).parent("tr").remove();
        // 排序
        table_order();
        // 退款明细计算
        stat_finance();
    });
}

// 联动 - 币种选取，修改币种参数以及计算金额和已经支付金额，还需支付金额
function change_exchange_rate(){
    $(".currency").find("select[name='currency']").unbind('change').change(function () {
        var exchange_rate=$(this).find("option:selected").attr("exchange_rate");
        var unit=$(this).find("option:selected").attr("unit");
        $(this).parent().parent().find(".exchange_rate").text(exchange_rate);
        $(this).parent().parent().find(".unit").text(unit);
        //默认币种汇率
        var shop_id=$("#shop_id").val();
        if(shop_id==0){
            var default_exchange_rate=_default_exchange_rate;
            if(!default_exchange_rate){
                default_exchange_rate=100;
            }
        }else {
            var default_exchange_rate=$("#shop_id").find("option:selected").attr("default_rate");
            if(!default_exchange_rate){
                default_exchange_rate=100;
            }
        }
        var pay_price=$(this).parent().parent().find("input[name='pay_price']").val();
        var actual_price=pay_price*exchange_rate/default_exchange_rate;
        actual_price=actual_price.toFixed(2);
        $(this).closest(".actual_price").text(actual_price);
        
        stat_finance();
    })
}

// 联动 - 清除退款方式信息
function clear_pay_info(){
    if($('.pay_type_tr').length>2){
        var html='<tr id="pay_last" class="pay_type_tr">';
        html+=$(".pay_tbody").find("#pay_last").html();
        html+='</tr>';
        $(".pay_type_tr").remove();
        $("#count_pay").before(html);
        change_countprice();//更新已退款金额
    }
}

// 计算 - 退款明细统一计算
function stat_finance(){
    $('#count_return_price').text(parseFloat($('#return_price').val()).toFixed(2));
    change_countprice();
    change_needprice();
}

// 计算 - 修改已经支付的金额和计算金额
function change_countprice() {
    //默认币种汇率
    var shop_id=$("#shop_id").val();
    if(shop_id==0){
        var default_exchange_rate=_default_exchange_rate;
        if(!default_exchange_rate){
            default_exchange_rate=100;
        }
    }else {
        var default_exchange_rate=$("#shop_id").find("option:selected").attr("default_rate");
        if(!default_exchange_rate){
            default_exchange_rate=100;
        }
    }
    var con = 0;
    $("input[name='pay_price']").each(function(key,val) {
        var pay_price=$(this).val();
        var actual_price=0;
        if(pay_price==''){
            pay_price=0;
        }else {
            var exchange_rate =$(this).parent().parent().find(".exchange_rate").text();
            var actual_price=pay_price*(exchange_rate)/default_exchange_rate;
        }
        actual_price=actual_price.toFixed(2);
        $(this).parent().parent().find(".actual_price").text(actual_price);
        con = parseFloat(con) + parseFloat(actual_price);
    })
    $("#count_price").text(con.toFixed(2));
}

// 计算 - 修改还需支付的金额
function change_needprice() {
    var count_return_price = parseFloat($("#count_return_price").text());
    var count_price = parseFloat($("#count_price").text());
    var need_price = count_return_price - count_price;
    $("#need_price").text(isNaN(need_price) ? 0 : need_price.toFixed(2));
}

/* 退款明细 end */

/* 提交单据 start */

var is_loading = false;

$('#save, #commit').on('click', function(){

    var need_price = parseFloat($('#need_price').text());
    if (need_price != 0) {
        error_appear('还需支付' + need_price + '元！');
        return false;
    }

    var expence_num = $('#expence_table').find('table tbody tr').length;
    var product_num = $('#return_product').find('table tbody tr').length;
    if (expence_num == 1 && product_num == 1) {
        error_appear('这是一张空的退货单！');
        return false;
    }

    var return_id = $('#return_id').val();
    var url = $(this).data('url');
    var type = $(this).data('type');                // 操作类型 -1 保存 0 提交
    var client_id = $('#client_id').val();          // 客户ID
    var return_time = $('#return_date').val();      // 退货时间
    var shop_id = $('#shop_id').val();              // 商户id
    var return_price = $('#return_price').val();    // 退货总额
    var extra_price = $('#extra_price').val();      // 抹零金额
    var client_idno = $('#client_idno').val();      // 身份证号
    var count = $('#return_num').text();            // 退货件数
    var memo = $('#memo').val();                    // 退货件数

    var del_saccount_detail = $('#del_saccount_detail').val(); // 删除的退款明细id
    if(parseFloat(return_price)<=0){
    	error_appear('退货总额需大于0！');
    }
    // 其它费用
    var sub_datas = [];
    if (typeof get_expence_data == 'function') {
        sub_datas = get_expence_data();
    }
    // 表单列表
    var detail_datas = get_detail_datas();
    // 退款明细
    var saccout_record = getall_pay_type();
    // 获取三个data时 判断数据的正确性并返回对象或false
    if (sub_datas === false || detail_datas === false || saccout_record === false) {
        return false;
    }

    var post_data = {
        return_id:return_id,
        type:type,
        client_id:client_id,
        return_time:return_time,
        shop_id:shop_id,
        return_price:return_price,
        extra_price:extra_price,
        client_idno:client_idno,
        count:count,
        memo:memo,
        del_saccount_detail:del_saccount_detail,
        main_currency:{
            id : $("#shop_id").find("option:selected").attr("currency_id"),
            rate : $("#shop_id").find("option:selected").attr("default_rate")
        },

        sub_datas:sub_datas,
        detail_datas:detail_datas,
        saccout_record:eval(saccout_record)
    };
    if (is_loading) {
        return false;
    }
    is_loading = true;

    $.ajax({
        url : url,
        data : post_data,
        type : 'post',
        dataType : 'json',
        async : false,
        success : function(result) {
            if (result.code == '200') {
                $('#return_id').val(result.datas.return_id);
                $('#shop_id').attr('disable', true);
                if (typeof(result.datas.url) != undefined && result.datas.url != '') {
                    setTimeout(function() {
                        location.href = result.datas.url;
                    }, 800);
                }
            }
            var msg = (result.datas.msg == undefined) ? result.datas.error : result.datas.msg;
            error_appear(msg);
        }
    });

    is_loading = false;
});

// 提交表单操作 驳回 通过 不通过 撤销
$('.reject, .pass, .unpass, .cancel').on('click', function(){

    if (is_loading)
        return;
    is_loading = true;

    var return_id = $('.return_id').val();
    var status = $(this).data('status');
    var check_memo = $('textarea[name="check_memo"]').val();
    var url = $('.form_url').val();
    $.ajax({
        async : false,
        url : url,
        type : 'post',
        data : {
            return_id : return_id,
            status : status,
            check_memo : check_memo
        },
        dataType : 'json',
        success : function(result) {
            is_loading = false;

            appear_error(result.msg);
            if (result.referer) {
                window.location.href = result.referer;
                return false;
            } else {
                if (result.status === '1') {
                    window.location.href = window.location.href;
                    return false;
                }
            }
        },
        error : function() {
            is_loading = false;
            appear_error('操作失败，网络错误！');
        }
    });
    return false;
});

// 获取退货表单列表
function get_detail_datas(){
    var detail_data = [];
    var detail_tr = $('#return_product').find('table tbody[id="detail_tbody"] tr');
    detail_tr.each(function() {
        var tr_ = $(this);
        if (tr_.attr('id') != 'detail_last' && detail_data !== false) {
            var redetail_id = $(this).data('redetailid');
            var sell_detail_id = $(this).data('detailid');
            var product_id = $(this).data('productid');
            var detail_return_price = $(this).find('td').find('input[name=detail_return_price]').val();
            /*if (parseInt(detail_return_price) == 0) {
                var num = $(this).find('td:first').text();
                error_appear('请填写序号为' + num + '退货货品的退货价格！');
                detail_data = false;
                return false;
            } else {*/
                detail_data.push({
                    'redetail_id' : redetail_id,
                    'sell_detail_id' : sell_detail_id,
                    'product_id' : product_id,
                    'return_price' : detail_return_price
                });
            /*}*/
        }
    })
    if (detail_data === false) {
        return false;
    }
    return detail_data;
}

/* 提交单据 end */

// ---------- 助手函数 ----------

// 给表单每一行排序
table_order();
function table_order() {
	var len = $(".ta").find('table tr').length;
	for (var i = 1; i < len; i++) {
		$(".ta").find('table tr:eq(' + i + ') td:first').text(i);
	}
}

// 统计表单条数
function count_num() {
	var num = 0;
	$(".ta").find('table tr').each(function() {
		if ($(this).attr('class') == 'plus') {
			num++;
		}
	});
	$('#return_num').html(num);
}

// 统计金额和克重
function count_pw() {
	var total_price = 0;
	var extra_price = parseFloat($('input[name=extra_price]').val());
	$(".ta").find('table tr').each(function() {
		var tr = $(this);
		if (tr.attr('class') == 'plus') {
			var price = parseFloat(tr.find('input[name=detail_return_price]').val());
			total_price += (isNaN(price) ? 0 : price);
		}
	});

	if (typeof(count_expence_price) == 'function') {
		total_price += count_expence_price();
	}
	total_price -= (isNaN(extra_price) ? 0 : extra_price);
	$('input[name=return_price]').val(total_price.toFixed(2));	// 表单详情 - 退货总额
	$('#count_return_price').text(total_price.toFixed(2));		// 退款明细 - 总计
}

// 删除一行
function del_input() {
	$('.del').each(function() {
		$(this).unbind('click').click(function() {
			var tr = $(this).closest('tr');
			tr.remove();
			reflush_table();
		});
	});
}
del_input();

// 离开页面前清空所有缓存
window.onbeforeunload = function(){
	localStorage.removeItem('product_ids' + location_id);
	localStorage.removeItem('product_codes' + location_id);
}

function appear_error(str){
    var tips=$('.tips_error');
    if(!tips.length){
        $('.form-actions').append('<span class="tips_error" style="color:red;">'+str+'</span>');
    }else{
        tips.html(str);
    }
}