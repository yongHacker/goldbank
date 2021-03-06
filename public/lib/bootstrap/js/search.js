(function() {
    /**
     * 百度搜索 API 测试
     */
    $("#baidu").bsSuggest({
        allowNoKeyword: false,   //是否允许无关键字时请求数据。为 false 则无输入时不执行过滤请求
        multiWord: true,         //以分隔符号分割的多关键字支持
        separator: ",",          //多关键字支持时的分隔符，默认为空格
        getDataMethod: "url",    //获取数据的方式，总是从 URL 获取
		effectiveFields:["mobile"],
        url: $('#baidu').attr('data'), //优先从url ajax 请求 json 帮助数据，注意最后一个参数为关键字请求参数
    }).on('onDataRequestSuccess', function (e, result) {
		$('#u_id').val('');
        console.log('onDataRequestSuccess: ', result);
    }).on('onSetSelectValue', function (e, keyword, data) {
        //alert(data.id);
		
			$('#u_id').val(data.id);
		
		
	    console.log('onSetSelectValue: ', keyword, data);
    }).on('onUnsetSelectValue', function () {
        console.log("onUnsetSelectValue");
    });

}());
