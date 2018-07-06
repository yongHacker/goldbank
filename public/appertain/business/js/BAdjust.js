/**
 * create by alam 2016/06/25
 * 货品调整
 */
// 当前页面的标识ID
var location_id = location_id();

// 初始化iframe
function save_init() {
	var adjust_type = $('#adjust_type').val();

	// 修改iframe的地址
	var goods_url = $('iframe#goods_list').data('url');
	var product_url = $('iframe#product_list').data('url');
	goods_url = goods_url + "&adjust_type=" + adjust_type;
	product_url = product_url + "&adjust_type=" + adjust_type;
	$('iframe#product_list').attr('src', product_url);
	$('iframe#goods_list').attr('src', goods_url);

	// 修改下载模板的地址、修改上传excel的form中的adjust_type
	var url = $(".download_template").data('url').replace(/{type}/, adjust_type)  + "?t=" + Date.parse(new Date());
	$(".download_template").attr("onclick", "location.href='" + url + "'");
	$("#excel_into input[name=adjust_type]").val(adjust_type);
	
	// 联动绑定
	$('table.sub-table').each(function(){
		var _type = $(this).data('type');
		if(adjust_type == _type){
			$(this).removeClass('hide');
			$(this).addClass('table-show');
		} else {
			$(this).addClass('hide');
			$(this).removeClass('table-show');
			$(this).find('tbody tr.plus').remove();
		}
	});
	del_input();

	reflush_table();
}
$('#adjust_type').change(function(){
	save_init();
});

// 选择商品规格导入货品
$('#goods_list').load(function() {
	$("#goods_check").unbind('click').click(function() {
		var adjust_type = $('#adjust_type').val();
		var product_codes = $('input[name=product_codes]').val();

		var goods_ids = '';
		var html = $('#goods_list').contents();
		var tr = html.find("input[class='goods_input']:checked").closest('tr');
		tr.each(function() {
			goods_ids += ($(this).data('goodsid') + ',');
		})
		goods_ids = goods_ids.substring(0, goods_ids.length - 1);

		var post_data = {
			adjust_type : adjust_type,
			product_codes : product_codes,
			goods_ids : goods_ids
		};

		var url = $(this).data('url');
		$.ajax({
			url : url,
            type: 'post',
            data: post_data,
            async: false,
            dataType: 'json',
            success : function(result){
            	if (typeof(result.datas.status) != 'undefined' && result.datas.status == 1) {
					$("table.table-show #last").before(result.datas.text);

					del_input();

					reflush_table();
				} else if (typeof(result.datas.state) != 'undefined' && result.datas.state == 'fail') {
					artdialog_alert(result.datas.msg);
                }
            },
            error : function(){
				artdialog_alert('导入失败');
            }
		});
	});
	$("#goods_add").unbind('click').click(function() {
		$("#goods_check").trigger("click");
	})
});

// excel导入
$('.excel_click').click(function(){
    $('input[name=excel_file]').click();
});
var $form = $('#excel_into');
var $file = $('input[name=excel_file]');
$file.unbind('change').change(function(){
    if($file.val()){
        $('.excel_click').html('导入中...');

        $form.ajaxSubmit({
            url:$form.attr("action"),
            success:function(result){
                result = eval("("+ result +")");

                $('#excel_into .msg').remove();

                if (typeof(result.datas.status) != 'undefined' && result.datas.status == 1) {

					$("table.table-show #last").before(result.datas.text);

					del_input();

					reflush_table();
                    
					artdialog_alert('导入成功', 'succeed');
                } else if (typeof(result.datas.state) != 'undefined' && result.datas.state == 'fail') {
					artdialog_alert(result.datas.msg);
                } else {
                	$('#excel_into').append('<p style="color:red;" class="msg">' + result.datas.msg + '<p>');
                }

                setTimeout(function(){$('.excel_click').html('从excel文件中导入');},800);
            },
            error:function(){
				artdialog_alert('导入失败');
            }
        });

        $file.val('');
    }
});

// 选择货品
$('#product_list').load(function() {
	var type = $('#adjust_type').val();
	$("#product_check").unbind('click').click(function() {
		var html = $('#product_list').contents();
		var tr = html.find("input[class='goods_input']:checked").closest('tr');
		var append_html = '';
		var product_ids = _product_ids();
		tr.each(function() {
			var tr_ = $(this);
			if(check_product_id(tr_.data('productid'), product_ids)){
				if(type == 1){
					append_html += _add_procure_product_row(
						tr_.find('.product_code').html(),
						tr_.find('.product_name').html(),
						tr_.find('.goods_code').html(),
						tr_.find('.pricemode').html(),
						tr_.find('.weight').html(),
						tr_.find('.buy_m_fee').html(),
						tr_.find('.total_fee').html(),
						tr_.find('.cost_price').html(),
						tr_.find('.product_status').html(),
						tr_.data('pricemode'),
						tr_.data('productid')
					);
				} else if (type == 2){
					append_html += _add_sell_product_row(
						tr_.find('.product_code').html(),
						tr_.find('.product_name').html(),
						tr_.find('.goods_code').html(),
						tr_.find('.sell_pricemode').html(),
						tr_.find('.sell_feemode').html(),
						tr_.find('.sell_price').html(),
						tr_.find('.sell_fee').html(),
						tr_.find('.product_status').html(),
						tr_.data('sellpricemode') == '1' ? 1 : 0,
						tr_.data('sellfeemode') == '1' ? 1 : 0,
						tr_.data('productid')
					);
				} else if (type == 3) {
					append_html += _add_product_row(
						tr_.find('.product_code').html(),
						tr_.find('.product_name').html(),
						tr_.find('.goods_code').html(),
						tr_.find('.product_status').html(),
						tr_.data('productid')
					);
				}
			}
		})
		$("table.table-show #last").before(append_html);

		del_input();

		reflush_table();
	});
	$("#product_add").unbind('click').click(function() {
		$("#product_check").trigger("click");
	})
});

// 刷新localStorage中的货品编码并返回CODE序列
function refresh_product_codes() {
	var _tr = $('#order_product').find('table').find('tbody').find('tr');
	var product_codes = '';
	_tr.each(function() {
		if ($(this).attr('id') != 'last') {
			var product_code = $(this).data('productcode');
			product_codes += (String(product_code) + ',');
		}
	});
	product_codes = product_codes.substring(0, product_codes.length - 1);
	localStorage.setItem('product_codes' + location_id, product_codes);
	$('input[name=product_codes]').val(product_codes);
	return product_codes;
}

// 检查表单中是否有重复的数据
function check_product_id(product_id, product_ids){
	if(product_ids.indexOf(',' + product_id + ',') !== -1){
		return false;
	}else{
		return true;
	}
}

// 获取前后带英文逗号的货品id序列
function _product_ids() {
	var ids = '';
	$("table.table-show tbody tr.plus").each(function(){
		if($(this).attr('type') != 'del'){
			ids += ',' + $(this).data('productid');
		}
	});
	ids += ',';
	return ids;
}

// 添加行 start

//添加一行采购信息调整
function _add_procure_product_row(product_code, product_name, goods_code, pricemode_name, weight, buy_m_fee, total_fee, cost_price, status_name, pricemode, product_id){
	var weight_html = '';
	var buy_m_fee_html = '';
	var cost_price_html = '';
	if (pricemode == 1) {
		weight_html = '<input type="text" class="text-right" name="weight" value="' + trim(weight) + '">';
		buy_m_fee_html = '<input class="text-right" type="text" name="buy_m_fee" value="' + trim(buy_m_fee) + '">';
		cost_price_html = trim(cost_price);
	} else if (pricemode == 0) {
		weight_html = trim(weight);
		buy_m_fee_html = trim(buy_m_fee);
		cost_price_html = '<input class="text-right" type="text" name="cost_price" value="' + trim(cost_price) + '">';
	}
	html = '<tr class="plus" data-productid="' + trim(product_id) + '" data-productcode="' + trim(product_code) + '" data-pricemode="' + trim(pricemode) + '">';
	html += '<td class="text-center"></td>';
	html += '<td class="text-center product_code">' + trim(product_code) + '</td>';
	html += '<td class="text-center product_name">' + trim(product_name) + '</td>';
	html += '<td class="text-center goods_code">' + trim(goods_code) + '</td>';
	html += '<td class="text-center pricemode">' + trim(pricemode_name) + '</td>';
	html += '<td class="text-center weight">' + trim(weight_html) + '</td>';
	html += '<td class="text-center buy_m_fee">' + trim(buy_m_fee_html) + '</td>';
	html += '<td class="text-right total_fee">' + trim(total_fee) + '</td>';
	html += '<td class="text-center cost_price">' + trim(cost_price_html) + '</td>';
	html += '<td class="text-center status_name">' + trim(status_name) + '</td>';
	html += '<td class="text-center" style="vertical-align: inherit;"><a class="del fa fa-trash" title="删除""></a></td>';
	html += '</tr>';
	return html;
}

//添加一行销售信息调整
function _add_sell_product_row(product_code, product_name, goods_code, sell_pricemode_name, sell_feemode_name, sell_price, sell_fee, status_name, sell_pricemode, sell_feemode, product_id){
	var sell_fee_html = '';
	var sell_price_html = '';
	if (sell_pricemode == 1) {
		sell_price_html = trim(sell_price);
		sell_fee_html = '<input class="text-right" name="sell_fee" type="text" value="' + trim(sell_fee) + '">';
	} else if (sell_pricemode == 0){
		sell_price_html = '<input class="text-right" name="sell_price" type="text" value="' + trim(sell_price) + '">';
		sell_fee_html = trim(sell_fee);
	}
	html = '<tr class="plus" data-productid="' + trim(product_id) + '" data-productcode="' + trim(product_code) + '" data-sellpricemode="' + trim(sell_pricemode) + '" data-sellfeemode="' + trim(sell_feemode) + '">';
	html += '<td class="text-center"></td>';
	html += '<td class="text-center product_code">' + trim(product_code) + '</td>';
	html += '<td class="text-center product_name">' + trim(product_name) + '</td>';
	html += '<td class="text-center goods_code">' + trim(goods_code) + '</td>';
	html += '<td class="text-center sell_pricemode">' + trim(sell_pricemode_name) + '</td>';
	html += '<td class="text-center sell_feemode">' + trim(sell_feemode_name) + '</td>';
	html += '<td class="text-center sell_price">' + sell_price_html + '</td>';
	html += '<td class="text-center sell_fee">' + sell_fee_html + '</td>';
	html += '<td class="text-center status_name">' + trim(status_name) + '</td>';
	html += '<td class="text-center" style="vertical-align: inherit;"><a class="del fa fa-trash" title="删除""></a></td>';
	html += '</tr>';
	return html;
}

// 添加一行商品规格调整
function _add_product_row(product_code, product_name, goods_code, status_name, product_id) {
	html = '<tr class="plus" data-productid="' + trim(product_id) + '" data-productcode="' + trim(product_code) + '">';
	html += '<td class="text-center"></td>';
	html += '<td class="text-center product_code">' + trim(product_code) + '</td>';
	html += '<td class="text-center product_name">' + trim(product_name) + '</td>';
	html += '<td class="text-center old_goods_code">' + trim(goods_code) + '</td>';
	html += '<td class="text-center new_goods_code"><input type="text" class="text-center" name="new_goods_code" value=""></td>';
	html += '<td class="text-center status_name">' + trim(status_name) + '</td>';
	html += '<td class="text-center" style="vertical-align: inherit;"><a class="del fa fa-trash" title="删除""></a></td>';
	html += '</tr>';
	return html;
}

// 添加行 end

// 提交数据 start

var is_loading = false;
$('#save, #commit').click(function(){
	var adjust_id = $('#adjust_id').val();
	var adjust_type = $('#adjust_type').val();
	var num = $('#adjust_num').html();
	var memo = $('#memo').val();
	var status = $(this).data('type');
	var product_data = get_product_data();
	if(empty(product_data)){
		artdialog_alert('请选择货品');
		return false;
	} else if (typeof(product_data) == 'string') {
		artdialog_alert(product_data);
		return false;
	}
	var	data = {adjust_type:adjust_type, adjust_id:adjust_id, status:status, num:num, memo:memo, product_data:product_data};

 	if(is_loading){
		artdialog_alert('请勿频繁点击');
		return false;
	}
	is_loading = true;
	
	$.ajax({
		url : $('.nav-tabs a').attr('href'),
		data : data,
		type : 'post',
		dataType : 'json',
		success : function(result){
			if(result.code == 200){
				$('#adjust_id').val(result.datas.adjust_id);

				var msg = (typeof(result.datas.msg) != 'undefined' && result.datas.msg != '') ? result.datas.msg : '操作成功';
				var url = (typeof(result.datas.url) != 'undefined' && result.datas.url != '') ? result.datas.url : '';

				artdialog_alert(msg, 'succeed');
				if (status == 0 && url != '') {
					setTimeout(function() {
						location.href = url;
					}, 800);
				}
			} else if (result.code == 400){
				var msg = (typeof(result.datas.error) != 'undefined' && result.datas.error != '') ? result.datas.error : '操作失败';
				artdialog_alert(msg);
			}
			is_loading = false;
		},
		error : function(){
			is_loading = false;
		}
	});
});

// 获取当前表单列表中的货品数据
function get_product_data(){
	var adjust_type = $('#adjust_type').val();
	var datas = new Array();
	var msg = '';
	$('table.table-show tbody tr.plus').each(function(){
		if (msg == '') {
			var data = new Object();
			var _tr = $(this);
			var num = _tr.find("td:first-child").text();
			if(_tr.data('id') > 0){
				data.id = _tr.data('id');
			}
			data.product_id = _tr.data('productid');
			data.product_code = _tr.data('productcode');
			if (adjust_type == 1){
				data.price_mode = _tr.data('pricemode');

				var weight_input = _tr.find('input[name=weight]');
				var buy_m_fee_input = _tr.find('input[name=buy_m_fee]');
				var cost_price_input = _tr.find('input[name=cost_price]');
				if(weight_input.size() > 0){
					data.weight = weight_input.val();
					if (data.weight == '') {
						msg = '第' + num + '行 克重 不能为空';
						return;
					}
				}
				if(buy_m_fee_input.size() > 0){
					data.buy_m_fee = buy_m_fee_input.val();
					if (data.buy_m_fee == '') {
						msg = '第' + num + '行 工费 不能为空';
						return;
					}
				}
				if(cost_price_input.size() > 0){
					data.cost_price = cost_price_input.val();
					if (data.cost_price == '') {
						msg = '第' + num + '行 成本价 不能为空';
						return;
					}
				}
			} else if (adjust_type == 2){
				data.sell_pricemode = _tr.data('sellpricemode');
				data.sell_feemode = _tr.data('sellfeemode');

				var sell_price_input = _tr.find('input[name=sell_price]');
				var sell_fee_input = _tr.find('input[name=sell_fee]');
				if(sell_price_input.size() > 0){
					data.sell_price = sell_price_input.val();
					if (data.sell_price == '') {
						msg = '第' + num + '行 销售价 不能为空';
						return;
					}
				}
				if(sell_fee_input.size() > 0){
					data.sell_fee = sell_fee_input.val();
					if (data.sell_price == '') {
						msg = '第' + num + '行 销售工费 不能为空';
						return;
					}
				}
			} else if (adjust_type == 3) {
				var goods_code = _tr.find('input[name=new_goods_code]').val();
				if (goods_code == '') {
					msg = '第' + num + '行 规格编码不能为空 不能为空';
					return;
				}
				data.goods_code = goods_code;
			}
			datas.push(data);
		}
	});
	if (msg !== '') {
		return msg;
	}
	return datas;
}

// 提交数据 end

// 提交表单操作 驳回 通过 不通过 撤销
$('.reject, .pass, .unpass, .cancel').on('click', function(){

    if (is_loading)
        return;
    is_loading = true;

    var adjust_id = $('#adjust_id').val();
    var status = $(this).data('status');
    var check_memo = $('textarea[name="check_memo"]').val();
    var url = $('.form_url').val();
    $.ajax({
        async : false,
        url : url,
        type : 'post',
        data : {
            adjust_id : adjust_id,
            status : status,
            check_memo : check_memo
        },
        dataType : 'json',
        success : function(result) {
			var msg = result.msg;
			var referer = result.referer;
			var state = result.state;

			if (state != "fail") {
				artdialog_alert(msg, 'succeed');
				if (referer != '') {
					setTimeout(function() {
						location.href = referer;
					}, 800);
				}
			} else {
				artdialog_alert(msg);
			}
			is_loading = false;
        },
        error : function() {
            is_loading = false;
            appear_error('操作失败，网络错误！');
        }
    });
    return false;
});

//计算成本价
function count_totalfee(){
	$('input[name=weight], input[name=buy_m_fee]').unbind('keyup').keyup(function(){
		if($(this).val() < 0){
			artdialog_alert('填入的值不能小于0');
		}
		var tr = $(this).parents('tr.plus');
		var weight = parseFloat(tr.find('input[name=weight]').val());
		weight = weight > 0 ? weight : 0;
		var buy_m_fee = parseFloat(tr.find('input[name=buy_m_fee]').val());
		buy_m_fee = buy_m_fee > 0 ? buy_m_fee : 0;
		var totalfee = weight * buy_m_fee;
		tr.find('td.total_fee').html(totalfee.toFixed(2));
	});
}

function reflush_table(){
	table_order();
	count_num();
	count_totalfee();
	refresh_product_codes();
}

// 助手函数

//表单排序
function table_order() {
	var len = $(".ta").find('table.table-show tr').length;
	var j = 1;
	for (var i = 1; i < len; i++) {
		var td = $(".ta").find('table.table-show tr:eq(' + i + ') td:first');
		if(td.is(':hidden')){} else {
			td.text(j);
			j++;
		}
	}
}
//统计表单条数
function count_num() {
	var num = 0;
	$(".ta").find('table.table-show tr').each(function() {
		if ($(this).hasClass('plus') && !$(this).hasClass('hide')) {
			num++;
		}
	});
	$('#adjust_num').html(num);
}
//删除一行
function del_input() {
	$('.del').each(function() {
		$(this).unbind('click').click(function() {
			$(this).parent('td').parent('tr').remove();
			reflush_table();
		});
	});
}
// 删除左右两端的空格
function trim(str){
	if (typeof(str) == 'number' || typeof(str) == 'undefined') {
		return str;
	}
	return str.replace(/(^\s*)|(\s*$)/g, "");
}