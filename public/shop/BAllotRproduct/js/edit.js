    localStorage.removeItem('checkbox_id');

    //编辑调拨单
    $(".baocun,.submit").click(function(){
        // var data_type=$(this).attr("data-type");
        var add_status=$(this).data('type');
        var __hash__=$("input[name='__hash__']").val();
        var names = [];
        var all_products = [];
        var tr = $('#tbody').find('tr');
        var i=0;
        var new_product_id="";
        var products_id = "";

        tr.each(function(){
            var id = $(this).find(".product_id").text();
            //var ck=$(this).find("td:nth-child(5)").text()
            var old_data = $(this).attr("old_data")
            if(id){
                if(i==0){
                    if(old_data!="old_data"){
                        new_product_id+=id;
                    }
                    products_id+=id;
                }else{
                    products_id+=",";
                    products_id+=id;
                    if(old_data!="old_data"){
                        new_product_id+=",";
                        new_product_id+=id;
                    }
                }
                i=i+1;
            }
        })
        names.push({'id':new_product_id});
        all_products.push({'id':products_id});
        if(names.length<1&&$(".del2").length<1){
            $(".tips_error").text("请调拨货品!");
            //alert("请选择需要调拨的货品");
            return;
        }
        names = JSON.stringify(names);

        var store=$("#store").val();
        if(store<1){
            $(".tips_error").text("请选收货仓库!");
            //alert("收货仓库不能为空");
            return;
        }
        var allocationid=$("input[name='allocation_id']").val();
        var comment=$("#comment").val();
        var trance_time=$("#trance_time").val();
        $.ajax({
            url: API_URL+"&m=BAllotRproduct&a=edit",
            type: 'post',
            async: false,
            data: {
                __hash__:__hash__,
                add_status:add_status,
                comment:comment,
                allocationid:allocationid,
                store:store,
                trance_time:trance_time,
                order:eval(names),
                all_products:eval(all_products)
            },
            beforeSend:function(){
               if(add_status=="add"){
                   $("#baocun").text("保存中...");
               }else {
                   $("#baocun").text("提交中...");
               }
                $("#baocun").attr("disabled",true);

            },
            success: function (data) {
                
                if(data.status==1){
                    $(".tips_error").text("保存成功！");
                    // self.location = document.referrer;
                    if(add_status=="submit"){
                        setTimeout(self.location=API_URL+'&m=BAllotRproduct&a=allot_index',3000);
					}

                }else{
                    $(".tips_error").text(data.msg);
                    if(add_status=="add"){
                        $(this).text("保存");
                    }else {
                        $(this).text("提交");
                    }
                    $(".baocun").attr("disabled",false);
                }
            },error: function (data,status) {
                $(".tips_error").text("请检查网络!");

            }
        })
    })

	/*
	 //日期
	 laydate({
	 elem: '#rzsj',
	 istoday: true,
	 });
	 $("#rzsj").text(laydate.now(0, "YYYY-MM-DD"));
	 */
    //table排序
    var tr_order=1;
    table_order();
    myModal2();
    del_input();
    del2_input();
    //table排序
    function del2_input(){
        $(".del2").each(function(){

            $(this).click(function(){
                var tr = $(this).closest('tr');
                var allocationid=$("input[name='allocation_id']").val();
                var id = $(this).attr("name");
                var p_id = tr.find(".product_id").text();

                $.ajax({
                    url: API_URL+"&m=BAllotRproduct&a=detail_delete",
                    type: 'post',
                    async: false,
                    data: {allocationid:allocationid,id:id,p_id:p_id},
                    beforeSend:function(){
                        // $(".baocun").attr("disabled",true);
                    },
                    success: function (data) {
                        if(data.status==1){
                            var tr=$("a[name='"+data.id+"']").parent().parent();
                            del_tr(tr);
                        }else{
                            alert(data.msg);
                        }
                    },error: function (data,status) {
                        alert("请检查网络");
                    }
                })



            });
        });
    }

    function table_order(){
        //$('table tr:not(:first)').remove();
        var len = $(".ta").find('table tr').length;
        for(var i = 1;i<len;i++){
            $(".ta").find('table tr:eq('+i+') td:first').text(i);
        }

    }
    // tr删除
    function del_tr(tr){
        tr.remove();
        table_order();
        var checked=localStorage.getItem('checkbox_id');
        var checkbox_id=tr.find("td:last").text();
        console.log(checkbox_id);
        checked=checked.replace("ck"+checkbox_id+"ck","");
        localStorage.setItem('checkbox_id',checked);
        //删除后将图片换回复选框

        var html=$('#goods_index').contents();
        var img=html.find(".ta").find("tbody img[checkbox_id='ck"+checkbox_id+"ck']");
        var checkbox=html.find(".ta").find("tbody input[checkbox_id='ck"+checkbox_id+"ck']");
        checkbox.show();
        //img.parent().html('<input type="checkbox" value="" class="goods_input" checkbox_id="ck'+checkbox_id+'ck">');
        checkbox.prop('checked',false);
        img.remove();
    }
    function del_input(){
        //删除tr
        $(".del").each(function(){
            $(this).click(function(){
                var tr=$(this).parent().parent();
                del_tr(tr);
            })
        });

        $(".ta").find("input").each(function(){
            //input获取焦点，td加边框
            $(this).focus(function(){
                var td=$(this).parent();
                td.css("border","solid 1px #157ab5");
            });
            //input失去焦点，td失去边框
            $(this).blur(function(){
                var td=$(this).parent();
                td.css("border","");
            });
        });

    }
    //弹框可拖拽
    $("#myModal2").draggable();
    //点击添加一栏tr
    $("#tr_add").click(function(){

        var s=$("#last_tr").prev().find("td:nth-child(1)").text();
        var ss=parseInt(s)+1;
        var html="";
        html+="<tr>";
        html+=$("#tr_info").html();
        html+="</tr>";
        $("#last_tr").before(html);
        $("#last_tr").prev().find("td:nth-child(1)").text(ss);
        table_order();
        myModal2();
        del_input();
    });

    //iframe子页面加载触发
    $('#goods_index').load(function () {
        $("#add").unbind('click').click(function(){
            $("#check").trigger("click");
        })

        $("#check").unbind('click').click(function(){
            //alert($(".myModal2.on").parent().prev().text());
            var html=$('#goods_index').contents();
            var tr=html.find("input[class='goods_input']:checked").parent().parent();
            tr.each(function(){
            	if($(this).find("img").length>0){
					
				}else{
	                var checkbox_id=$(this).find("td:nth-child(1)").find("input").attr("checkbox_id");
	                var checked=localStorage.getItem('checkbox_id');
	                checked+=","+checkbox_id;
	                localStorage.setItem('checkbox_id',checked);
	                var html="";
                    html+="<tr>";
                    html+='<td class="text-center"></td>';
                    html += '<td class="text-center" >' + $(this).find(".product_code").text() + '</td>';
                    html += ' <td style="padding-left:10px;">' + $(this).find(".goodsname").text() + '</td>';
                    html += '<td class="text-right" >' + $.trim($(this).find(".total_weight").text()) + '</td>';
                    html += ' <td  class="text-right">' + $.trim($(this).find(".purity").text()) + '</td>';
                    html += '<td class="text-right" >' + $.trim($(this).find(".gold_weight").text()) + '</td>';
                    /*html += ' <td class="text-right">' + $.trim($(this).find(".recovery_price").text()) + '</td>';*/
                   /* html += ' <td class="text-right">' + $.trim($(this).find(".cost_price").text()) + '</td>';*/
                    html+='<td class="text-center">';
                    html+='<a href="javascript:void(0);" name="{$v.id}" class="del" role="button" data-toggle="modal">删除</i></a>';
                    html+='</td>';
                    html+=' <td hidden=hidden class="product_id">'+$.trim($(this).find(".rproduct_id").text())+'</td>';
                    html+='</tr>';
	                $("#last").before(html);
	                table_order();
	                myModal2();
	                del_input();
				}
            })
            //刷新页面读取已勾选数据
            var check=localStorage.getItem('checkbox_id');
            if(check!=""&&check!=null&&typeof(check)!="undefined"){
                var checked=check.split(',');
                for(var i in checked){
                    if(checked[i]!=null&&checked[i]!=""&&typeof(checked[i])!="undefined"){
                        var checkbox=tr.find("input[checkbox_id='"+checked[i]+"']");

                        if(checkbox.length>0&&checkbox.parent().find('img').length==0){
                            var img= "<img checkbox_id='"+checked[i]+"' src='/public/images/gou.png'/>";
                            checkbox.parent().append(img);
                            // checkbox.parent().html(img);
                             //checkbox.remove();
                             checkbox.prop('checked',false);
                             checkbox.hide();
                        }
                    }
                }
            }
            //document.getElementById("goods_index").contentWindow.location.reload(true);
        })

        initData();
    });

    initData();

    function checkedProductList(){
        var html = $('#goods_index').contents();

        var check = localStorage.getItem('checkbox_id');
        if(check){
            var tr_all = html.find("input[class=goods_input]").closest('tr');
            tr_all.each(function(){
                var all_checkbox_id = $(this).find("td:nth-child(1)").find("input").attr("checkbox_id");
                if(all_checkbox_id){
                    var index = check.indexOf(all_checkbox_id);
                }
                var imglength = $(this).find("td:nth-child(1)").find("img").length;
                if(index >= 0 && imglength==0){
                    var img= "<img checkbox_id='"+all_checkbox_id+"' src='/public/images/gou.png'/>";
                    var checkbox = tr_all.find("input[checkbox_id='ck"+all_checkbox_id+"ck']");

                    checkbox.parent().append(img);
                    checkbox.prop('checked', false);
                    checkbox.hide();
                }
            });
        }

    }

    function initData(){
        var tr = $('#tbody').find('tr');
        var checked = [];

        tr.each(function(){
            var id = $(this).find(".product_id").text();

            if(id != ''){
                checked.push('ck'+id+'ck');
            }
        })

        localStorage.setItem('checkbox_id', checked.join(','));

        checkedProductList();
    }

    //设置父页面选择的tr,以便对应位置添加获取内容
    function myModal2(){
        $('.myModal2').each(function () {

            $(this).click(function(){
                $(".myModal2.on").removeClass("on");
                $(this).addClass("on");

            })

        })
    }
    //选择收货仓库，自动完善收货管理员信息
    $('#store').change(function(){
        //alert($(this).val());
        if($(this).val()>0){
            var in_user=$(this).find("option:selected").attr("in_user");
            $("#in_user").text(in_user);
        }else{
            $("#in_user").text("请选择收货仓库");
        }

    })

