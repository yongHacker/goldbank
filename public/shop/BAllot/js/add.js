// <!--导入调拨-->
// 测试栈的请求为http://mt.gold-banker.cn/index.php?g=Shop&m=BAllot&a=add
// 本地报错为：http://gdbank/index.php?g=Shop&m=BAllot&a=add（404）该URL浏览器中可以直接打开
        //获取已经选择的货品编码
        function get_check_pruduct_codes(){
            var htmlobj = $('#goods_index').contents();
            var product_codes="0";
            var tr=htmlobj.find('.check_tr');
            tr.each(function(){
                console.log($(this).html());
                console.log($(this).find('.allot_p_id').find('.goods_input').css('display'));
                if($(this).find('.allot_p_id').find('.goods_input').css('display')=='none'){
                    product_codes+=","+$(this).find('.goods_input').val();
                }
            });
            console.log(product_codes);
            return product_codes;
        }
        $('.excel_click').click(function(){
            var wh_id=$("#mystore").val();
            if(wh_id>0){
                $('input[name=excel_file]').click();
            }else {
                artdialog_alert("请先选择发货仓库");return;
            }

        });
        var $form = $('#excel_into');
        var $file = $('input[name=excel_file]');
        $file.unbind('change').change(function(){
            if($file.val()){
                $('.excel_click').html('导入中...');
                var wh_id=$("#mystore").val();
                var product_codes=get_check_pruduct_codes();
                $form.ajaxSubmit({
                    url:$form.attr("action")+"&mystore="+wh_id+"&product_codes="+product_codes,
                    success: function (result) {
                        result = eval("("+ result +")");
                        if(result.datas.status==1){
                            var data=result.datas.text;
                            var product_ids=result.datas.product_ids;
                            var checked=localStorage.getItem('checkbox_id');
                            if(checked){
                                checked=checked+","+product_ids;
                            }else{
                                checked=product_ids;
                            }
                            localStorage.setItem('checkbox_id',checked);

                            checkedProductList();

                            $("#last").before(data);
                            table_order();
                            del_input();
                            $('.excel_click').html('导入成功');
                        }
                        $('#excel_into .msg').remove();
                        $('#excel_into').append('<p style="color:red;" class="msg">'+result.datas.msg+'<p>');
                    }
                });
            }
        });
        localStorage.removeItem('checkbox_id');
        //添加调拨单
        $(".baocun").click(function(){
            var add_status=$(this).data('type');
            var names = [];
            var tr= $(".ta").find('table tr');
            var allid="";
            var i=0;
            tr.each(function(){
                var id=$(this).find(".product_id").text();
                //var ck=$(this).find("td:nth-child(5)").text()
                if(id){
                    if(i==0){
                        allid+=id;
                    }else{
                        allid+=",";
                        allid+=id;
                    }
                    i=i+1;
                }

            })
            if(allid){
                names.push({'id':allid});
            }

            if(names.length<1){
                $(".tips_error").text("请选调拨货品!");
                //alert("请选择需要调拨的货品");
                return;
            }
            names=JSON.stringify(names);
            var store=$("#store").val();
            if(store<1){
                $(".tips_error").text("请选收货仓库!");
                //alert("收货仓库不能为空");
                return;
            }
            var mystore=0;
            if($("#mystore").length){
                var mystore=$("#mystore").val();
                if(mystore==store){
                    $(".tips_error").text("发货与收货仓库不能相同!");
                    return;
                }
                if(mystore<1){
                    $(".tips_error").text("请选收货仓库!");
                    //alert("收货仓库不能为空");
                    return;
                }
            }

            var comment=$("#comment").val();
            var trance_time=$("#trance_time").val();
            $.ajax({
                url: API_URL+"&m=BAllot&a=add",
                type: 'post',
                async: false,
                data: {add_status:add_status,trance_time:trance_time,comment:comment,store:store,mystore:mystore,order:eval(names)},
                beforeSend:function(){
                    $(".baocun").attr("disabled",true);
                    $(".baocun").text("保存中...");
                },
                success: function (data) {
                    //console.log(data);
                    //self.location=document.referrer;

                    if(data.status==1){
                        $(".tips_error").text("保存成功！");

                        setTimeout(self.location=API_URL+"&m=BAllot&a=allot_index",3000);
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
                    //alert("请检查网络");
                    console.log(status);
                    console.log(data);
                    $(".tips_error").text("请检查网络");
                }
            });


        });

        //table排序
        var tr_order=1;
        table_order();
        function table_order(){
            //$('table tr:not(:first)').remove();
            var len = $(".ta").find('table tr').length;
            for(var i = 1;i<len;i++){
                $(".ta").find('table tr:eq('+i+') td:first').text(i);
            }

        }

        //tr删除
        function del_input(){
            //删除tr
            $(".del").each(function(){
                $(this).click(function(){
                    var tr=$(this).parent().parent();
                    tr.remove();
                    table_order();
                    var checked=localStorage.getItem('checkbox_id');
                    var checkbox_id=tr.find("td:last").text();
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

                });
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
        $("#allot_list").draggable();

        $('#allot_list').load(function () {
            $("#allot_add").unbind('click').click(function(){
                $("#allot_check").trigger("click");
            });
            $("#allot_check").unbind('click').click(function(){
                var html=$('#allot_list').contents();
                $('.allotModal').html('导入中...');
                var ids="0";
                var tr=html.find('.check_tr');
                tr.each(function(){
                    if($(this).find('.check_box').is(':checked')){
                        ids+=","+$(this).find('.check_box').attr('id');
                    }
                });
                var mystore=$("#mystore").val();
                var product_codes=get_check_pruduct_codes();
                $.ajax({
                    url:API_URL+"&m=BAllot&a=get_allot_product",
                    data:{ids:ids,mystore:mystore,product_codes:product_codes},
                    type:'post',
                    dataType:'json',
                    success:function(result){
                        var data=result.datas.text;
                        var product_ids=result.datas.product_ids;
                        var checked=localStorage.getItem('checkbox_id');
                        if(checked){
                            checked=checked+","+product_ids;
                        }else{
                            checked=product_ids;
                        }
                        localStorage.setItem('checkbox_id',checked);

                        checkedProductList();

                        $("#last").before(data);
                        table_order();
                        del_input();
                        $('.allotModal').html('选择调拨单');
                    }
                });
            });
        });
//加载选择货品页面
        $('#goods_index').load(function () {
            $("#add").unbind('click').click(function(){
                $("#check").trigger("click");

            });
            $("#check").unbind('click').click(function(){
                //alert($(".myModal2.on").parent().prev().text());
                var htmlobj=$('#goods_index').contents();
                var tr=htmlobj.find("input[class='goods_input']:checked").parent().parent();
                var html="";
                tr.each(function(){
                    if ($(this).find("td:nth-child(1)").find("img").length > 0) {

                    }else{
                        var checkbox_id = $(this).find(".allot_p_id").find("input").attr("checkbox_id");
                        var checked=localStorage.getItem('checkbox_id');
                        checked+=","+checkbox_id;
                        localStorage.setItem('checkbox_id',checked);


                        html+="<tr>";
                        html+='<td class="text-center"></td>';
                        html += '<td class="" >' + $(this).find(".product_code").text() + '</td>';
                        html += ' <td style="padding-left:10px;">' + $(this).find(".goodsname").text() + '</td>';
                        html += ' <td class="text-left">' + $(this).find(".product_detail").html() + '</td>';
                        html+='<td class="text-center">';

                        html+='<a href="javascript:void(0);" name="{$v.id}" class="del" role="button" data-toggle="modal">删除</i></a>';
                        html+='</td>';
                        html+=' <td hidden=hidden class="product_id">'+$(this).find("td:last").text()+'</td>';
                        html+='</tr>';
                    }


                });
                $("#last").before(html);
                table_order();
                del_input();

                //刷新页面读取已勾选数据
                checkedProductList();
            });
        });

        function checkedProductList(){
            var htmlobj = $('#goods_index').contents();
            var check=localStorage.getItem('checkbox_id');
            if(check){
                var tr_all = htmlobj.find("input[class='goods_input']").closest('tr');

                tr_all.each(function(){
                    var all_checkbox_id=$(this).find("td:nth-child(1)").find("input").attr("checkbox_id");
                    if(all_checkbox_id){
                        var index=check.indexOf(all_checkbox_id);
                    }
                    var imglength = $(this).find("td:nth-child(1)").find("img").length;
                    if(index>=0&&imglength==0){
                        var img= "<img checkbox_id='"+all_checkbox_id+"' src='/public/images/gou.png'/>";
                        var checkbox = tr_all.find("input[checkbox_id='"+all_checkbox_id+"']");
                        checkbox.parent().append(img);

                        checkbox.prop('checked',false);
                        checkbox.hide();
                    }
                });
            }

        }

        //选择收货仓库，自动完善收货管理员信息
        var srcfirst=$("#goods_index").attr("src");

        var allot_list_src=$("#allot_list").attr("src");
        $('#mystore').change(function(){
            localStorage.removeItem('checkbox_id');
            var trleng=$("#tbody").find("tr").length;
            //setCookie("borrow_whid",$("#store").val());
            if(trleng>1){
                var html='<tr id="last">';
                html+='<td class="text-center"></td>';
                html+='<td class="text-center"></td>';
                html+='<td></td>';
                html+='<td></td>';
                html+='<td class="text-center" href="#myModal2" class="myModal2 on" style="cursor:pointer;"  role="button" data-toggle="modal" role="button"><a href="javascript:void(0);">+</a></td>';
                html+='<td hidden=hidden class="product_id"></td>';
                html+='</tr>';
                $("#tbody").html(html);
            }
            var src=srcfirst+"&mystore="+$("#mystore").val();

            var allot_list_src_new=allot_list_src+"&mystore="+$("#mystore").val();
            $("#goods_index").attr("src",src);

            $("#allot_list").attr("src",allot_list_src_new);
        });
        //选择收货仓库，自动完善收货管理员信息
        $('#store').change(function(){
            if($(this).val()>0){
                var name=$(this).find("option:selected").attr("in_user");
                $("#wh_user").text(name);
            }else{
                $("#wh_user").text("请选择收货仓库");
            }

        });
