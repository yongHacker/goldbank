/**
 * create by alam 2016/06/05
 * 采购退货的页面操作、数据提交js
 */

// 当前页面的标识ID
var location_id = location_id();
var is_loading = false;

// 切换仓库切换iframe地址
$('select[name=wh_id]').change(function() {
	update_iframe_url();
	$('.plus').remove();
	reflush_table();
});
// 切换供应商切换iframe地址
$('select[name=supplier_id ]').change(function() {
	update_iframe_url();
	$('.plus').remove();
	reflush_table();
});
// 修改抹零金额
$('input[name=extra_price]').keyup(function(){
	reflush_table();
});

// 改变iframe的url
function update_iframe_url()
{
	var wh_id = $('.wh_id').val();
	var supplier_id = $('.supplier_id').val();
	if (wh_id == 0) {
		return false;
	}

	var product_url = $('#product_list').data('url');
	var procure_url = $('#procure_list').data('url');

	var url_postfix = '&location_id=' + location_id + '&wh_id=' + wh_id + '&supplier_id=' + supplier_id;

	$('#product_list').attr('src', product_url + url_postfix);
	$('#procure_list').attr('src', procure_url + url_postfix);
}

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
	refresh_ifram_redio();
	return product_codes;
}

// 刷新localStorage中的货品ID并返回ID序列
function refresh_product_ids() {
	var _tr = $('#order_product').find('table').find('tbody').find('tr');
	var product_ids = '';
	_tr.each(function() {
		if ($(this).attr('id') != 'last') {
			var product_id = $(this).data('productid');
			product_ids += (String(product_id) + ',');
		}
	});
	product_ids = product_ids.substring(0, product_ids.length - 1);
	localStorage.setItem('product_ids' + location_id, product_ids);
	refresh_ifram_redio();
	return product_ids;
}

// 判断货品编码或者货品ID是否已存在表单列表中
function check_exist(product_id, product_code) {
	if (product_id != undefined || product_id != '') {
		var product_ids = refresh_product_ids();
		product_ids = product_ids.split(',');
		var exist = product_ids.indexOf(product_id);
	} else if (product_code != undefined || product_code != '') {
		var product_codes = refresh_product_codes();
		product_codes = product_codes.split(',');
		var exist = product_codes.indexOf(product_codes);
	}
	return (exist == -1) ? false : true;
}

/*
 * 添加一行退货清单
 */
function _add_row(product_id, product_code, sub_product_code, product_name, goods_code, pricemode, weight, buy_m_fee, cost_price)
{
	var count_fee = '-';
	if (pricemode == "计重") {
		count_fee = parseFloat(parseFloat(weight) * parseFloat(buy_m_fee)).toFixed(2);
	}
	var html = '';
	html += '<tr class="plus" data-productid="' + product_id + '" data-productcode="' + product_code + '">';
	html += '<td class="text-center"></td>';
	html += '<td class="text-center product_code">' + product_code+ '</td>';
	html += '<td class="text-center sub_product_code">' + sub_product_code+ '</td>';
	html += '<td class="text-center product_name">' + product_name + '</td>';
	html += '<td class="text-center goods_code">' + goods_code + '</td>';
	html += '<td class="text-center pricemode">' + pricemode + '</td>';
	html += '<td class="text-center weight">' + weight + '</td>';
	html += '<td class="text-center buy_m_fee">' + buy_m_fee + '</td>';
	html += '<td class="text-center cost_price">' + cost_price + '</td>';
	html += '<td class="text-center count_fee">' + count_fee + '</td>';
	html += '<td class="text-center" style="vertical-align: inherit;"><a class="del fa fa-trash" title="删除"></a></td>';
	html += '</tr>'
	return html;
}

/**
 * product iframe 点击操作
 */
$('#product_list').load(function() {
	$("#product_check").unbind('click').click(function() {
		var html = $('#product_list').contents();
		var tr = html.find("input[class='goods_input']:checked").parent().parent();
		var append_html = '';
		tr.each(function() {
			var tr_ = $(this);
			if (!check_exist(tr_.attr('data-productid'))) {
				append_html += _add_row(
					tr_.attr('data-productid'),
					tr_.find('.product_code').html(),
					tr_.find('.sub_product_code').html(),
					tr_.find('.product_name').html(),
					tr_.find('.goods_code').html(),
					tr_.find('.pricemode').html(),
					tr_.find('.weight').html(),
					tr_.find('.buy_m_fee').html(),
					tr_.find('.cost_price').html()
				);
			}
		});
		$("#last").before(append_html);

		reflush_table();
		del_input();
	});
	$("#product_add").unbind('click').click(function() {
		$("#product_check").trigger("click");
	})
});

/**
 * procure iframe 点击操作
 */
$('#procure_list').load(function(){
	$("#procure_check").unbind('click').click(function() {
        $('.procureModal').html('导入中...');

		var url = $(this).data('url');

		var html = $('#procure_list').contents();
        var tr = html.find('.check_tr');

        // 选中的货品ID列表
        var ids = "0";
        tr.each(function(){
            if($(this).find('.check_box').is(':checked')){
                ids += "," + $(this).find('.check_box').attr('id');
            }
        });
        // 仓库ID
        var wh_id = $("select[name=wh_id]").val();
        // 选中的货品编码列表
        var product_codes = refresh_product_codes();
        var append_html = '';
		$.ajax({
            url : url,
            data : {ids:ids, wh_id:wh_id, product_codes:product_codes},
            type : 'post',
            dataType : 'json',
            async : false,
            success : function(result){
                var product_list = result.datas.product_list;
                for(var i = 0; i < product_list.length; i ++){
                	var product = product_list[i];
					if (!check_exist(product.id)) {
						append_html += _add_row(
							product.id,
							product.product_code,
							product.sub_product_code,
							product.product_name,
							product.goods_code,
							(product.pricemode == 1) ? '计重' : '计件',
							(product.pricemode == 1) ? product.weight : '-',
							(product.pricemode == 1) ? product.buy_m_fee : '-',
							(product.pricemode == 1) ? '-' : product.cost_price
						);
					}
				}

                $("#last").before(append_html);
                $('.procureModal').html('选择采购单');
            }
        });

		reflush_table();
		del_input();
	});
	$("#procure_add").unbind('click').click(function() {
		$("#procure_check").trigger("click");
	})
});

// 刷新货品iframe中选择框
function refresh_ifram_redio()
{
	if (typeof document.getElementById("product_list").contentWindow.refresh_redio == 'function') {
		document.getElementById("product_list").contentWindow.refresh_redio();
	}
}

/**
 * 提交表单
 */
$('#save, #commit').unbind('click').click(function() {
	$('.tips_error').remove();

	if (is_loading)
		return;
	is_loading = true;

	var url = $(this).data('url');
	var return_id = $('input[name=return_id]').val();
	var return_date = $('input[name=return_date]').val();
	var wh_id = $('select[name=wh_id]').val();
	var supplier_id = $('select[name=supplier_id]').val();
	var return_num = $('#return_num').html();
	var return_weight = $('input[name=return_weight]').val();
	var return_price = $('input[name=return_price]').val();
	var extra_price = $('input[name=extra_price]').val();
	var memo = $('textarea[name=memo]').val();
	var product_ids = refresh_product_ids();
	var status = $(this).attr('data-type');
	var sub_datas = '';
	if (typeof get_expence_data == 'function') {
		sub_datas = get_expence_data();
		if (sub_datas === false) {
			return false;
		}
	}
	if (wh_id == 0) {
		error_appear('请选择仓库！');
		return false;
	}
	if (supplier_id == 0) {
		error_appear('请选择供应商！');
		return false;
	}
	if (product_ids == '' && sub_datas == '') {
		error_appear('这是一张空的退货单！');
		return false;
	}
	$.ajax({
		async : false,
		url : url,
		data : {
			return_id : return_id,
			return_date : return_date,
			wh_id : wh_id,
			supplier_id : supplier_id,
			return_num : return_num,
			return_weight : return_weight,
			return_price : return_price,
			extra_price : extra_price,
			status : status,
			memo : memo,
			product_ids : product_ids,
			sub_datas: sub_datas
		},
		type : 'post',
		dataType : 'json',
		success : function(result) {
			if (result.code == '200') {
				error_appear(result.datas.msg);
				$('input[name=return_id]').val(result.datas.return_id);
				if (status == 0) {
					setTimeout(function() {
						location.href = result.datas.url;
					}, 800);
				}
			} else {
				var msg = (result.datas.msg == undefined) ? result.datas.error : result.datas.msg;
				error_appear(msg);
			}
			is_loading = false;
		},
		error : function() {
			is_loading = false;
		}
	});
});

/**
 * 统一计算表单
 */
function reflush_table() {
	table_order();				// 表单列表排序
	count_num();				// 统计件数
	count_pw();					// 统计金额和克重
	refresh_product_ids();		// 刷新缓存的ID序列
	refresh_product_codes();	// 刷新缓存的CODE序列
}

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
				if (result.status === 'success') {
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
	var total_price = 0, total_weight = 0;
	var extra_price = parseFloat($('input[name=extra_price]').val());
	$(".ta").find('table tr').each(function() {
		var tr = $(this);
		if (tr.attr('class') == 'plus') {
			var weight = tr.find('.weight').html() == '-' ? 0 : parseFloat(tr.find('.weight').html());
			var buy_m_fee = tr.find('.buy_m_fee').html() == '-' ? 0 : parseFloat(tr.find('.buy_m_fee').html());
			var cost_price = tr.find('.cost_price').html() == '-' ? 0 : parseFloat(tr.find('.cost_price').html());
			total_price += (weight * buy_m_fee) + cost_price;
			total_weight += weight;
		}
	});

	$('#expence_table').find('table tr').each(function() {
		var tr = $(this);
		if (tr.attr('class') != 'expence_last') {
			var expence_price = parseFloat(tr.find('td').find('input[name=expence_price]').val());
			total_price += (isNaN(expence_price) ? 0 : expence_price);
		}
	});
	total_price -= (isNaN(extra_price) ? 0 : extra_price);
	$('input[name=return_price]').val(total_price.toFixed(2));
	$('input[name=return_weight]').val(total_weight.toFixed(2));
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

// 图片预览
function initPicReview() {
	$('.click_pic').unbind('click').click(function() {
		var img_url = $(this).find('img').attr('src');
		image_preview_dialog(img_url);
	})
}