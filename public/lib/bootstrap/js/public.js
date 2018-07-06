//输入框的值为空时不能点击,inputid是form父类字节对象，buttonid点击按钮对象
function button_disable(inputid,buttonid,type){
	$(buttonid).attr("disabled",true);
	var inputs= $(inputid).find("form").find("input");
	inputs.each(function(index,ele){
		$(ele).on("keyup",function(){
			if($.trim($(this).val())){
				$(buttonid).attr("disabled",false);					
			}else{
				$(buttonid).attr("disabled",true);
			}
								
							
		})
		
		
	})
		
	/*$(inputid).on("change",function(){
		if($.trim($(this).val())){
			$(buttonid).attr("disabled",false);
		}else{
			$(buttonid).attr("disabled",true);
									
		}
							
	})
	*/
		
}