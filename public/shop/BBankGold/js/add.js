	change_gold_type();
	function change_gold_type(){
		$('select[name=bg_id]').change(function(){
			var bmt_name=$(this).find("option:selected").attr('bmt_name');
			$('input[name=bmt_name]').val(bmt_name);
		})
	}
	var expression=$('input[name=expression]');

	$('#del').click(function(){
		expression.val("");
	});
	$('#round').click(function(){
		var value=expression.val();
		expression.val("round("+value+")");
	});
	$('#ceil').click(function(){
		var value=expression.val();
		expression.val("ceil("+value+")");
	});
	$('#floor').click(function(){
		var value=expression.val();
		expression.val("floor("+value+")");
	});
	$('#plus').click(function(){
		var value=expression.val();
		expression.val(value+"+");
	});
	$('#minus').click(function(){
		var value=expression.val();
		expression.val(value+"-");
	});
	$('#multiply').click(function(){
		var value=expression.val();
		expression.val(value+"*");
	});
	$('#divide').click(function(){
		var value=expression.val();
		expression.val(value+"/");
	});
	$('#price').click(function(){
		var value=expression.val();
		expression.val(value+"price");
	});
