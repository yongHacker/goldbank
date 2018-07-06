        heightAuto($("#myModal2"));
        heightAuto($("#procureModal"));
        localStorage.removeItem('checkbox_id');
        var del_out_detail=[];//删除的出库明细id
        var del_product_detail=[];//删除出库明细对应的货品id
        var del_recovery_product_detail=[];//删除出库转料的金料id
        //添加调拨单
        $("#baocun,#submit").click(function(){
            var data_type=$(this).attr("data-type");
            var names = [];
            var tr= $(".ta").find('table tr');
            var allid="";
            var i=0;
            var productids=[];
            tr.each(function(){
                var id=$(this).find(".product_id").text();
                var old_data=$(this).attr('old_data');
                if(id&&!old_data){
                    if(i==0){
                        allid+=id;
                    }else{
                        allid+=",";
                        allid+=id;
                    }
                    i=i+1;
                }
                if(id){
                    productids.push(id)
                }

            })
            if(allid){
                names.push({'id':allid});
            }

            if($('#tbody').find('tr').length<2){
                $(".tips_error").text("请选出库货品!");
                //alert("请选择需要调拨的货品");
                return;
            }
            var outbound_type=$("input[name=outbound_type]:checked").val();
            console.log(outbound_type);
            names=JSON.stringify(names);
            var comment=$("#comment").val();
            var trance_time=$("#trance_time").val();
            var mystore=$("#mystore").val();
            var add_type=$(this).data("type");
            var order_id=$("input[name='boutbound_data_id']").val();
            if(outbound_type==5) {
                var recovery_products = check_recovery_data();
                if (recovery_products == false) {
                    return false;
                }
            }
            var order={
                id:order_id,//出库单id
                productids:productids.join(','),//出库明细的所有的货品id
                add_type:add_type,//-1 保存 0 提交
                mystore:mystore,  //仓库
                trance_time:trance_time, //时间
                comment:comment,       //备注
                outbound_type:outbound_type,  //出库类型
                del_out_detail:del_out_detail.join(','),  //删除的出库明细
                del_product_detail:del_product_detail.join(','), //删除的出库明细对应的货品id
                del_recovery_detail:del_recovery_product_detail.join(','), //删除的金料明细对应的货品id
            }
            if($('#mobile-name').val()){
                order.client_id=$('#uid').val();
                order.employee_id=$('#employee_id').val();
            }
            $.ajax({
                url: API_URL+"&m=BOutboundOrder&a=edit",
                type: 'post',
                async: false,
                data: {order:order,order_detail:eval(names),recovery_products:recovery_products},
                beforeSend:function(){
                    $(".baocun").attr("disabled",true);
                },
                success: function (data) {
                    //console.log(data);
                    //self.location=document.referrer;

                    if(data.status==1){
                        $(".tips_error").text("保存成功！");
                        if(data_type=="0"){
                            setTimeout(self.location=API_URL+'&m=BOutboundOrder&a=index',3000);
                        }


                    }else{
                        $(".tips_error").text(data.msg);
                        $(".baocun").attr("disabled",false);
                    }


                },error: function (data,status) {
                    //alert("请检查网络");
                    $(".tips_error").text("请检查网络");
                }
            })


        })

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
                $(this).unbind('click').click(function(){
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
                    if($(this).attr('out_id')){
                        del_out_detail.push($(this).attr('out_id'));
                        del_product_detail.push($(this).attr('product_id'));
                    }
                    var product_code=checkbox.val();
                    del_recovery_tr(product_code);
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
        //iframe子页面加载触发
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
                        var product_code= $.trim($(this).find(".product_code").text());
                        var product_id= $.trim($(this).find("td:last").text());
                        var purity=$.trim($(this).find(".purity").text());

                        html+="<tr>";
                        html+='<td class="text-center"></td>';
                        html += '<td class="text-center" >' + product_code + '</td>';
                        /* html += ' <td class="text-right" style="padding-right:10px;">' + $(this).find(".weight").text() + '</td>';

                         html += ' <td class="text-center">' + $(this).find(".purity").text() + '</td>';*/
                        html += ' <td style="padding-left:10px;">' + $(this).find(".goodsname").text() + '</td>';

                        html+='<td class="">' + $(this).find(".product_detail").text() + '</td>';
                        html+='<td class="text-center">';

                        html+='<a href="javascript:void(0);"  class="del" role="button" data-toggle="modal">删除</i></a>';
                        html+='</td>';
                        html+=' <td hidden=hidden class="product_id">'+$(this).find("td:last").text()+'</td>';
                        html+='</tr>';
                    }
                    add_recovery_tr(product_code,product_id,purity);

                });
                $("#last").before(html);
                table_order();
                del_input();
                //刷新页面读取已勾选数据
                var check=localStorage.getItem('checkbox_id');
                if(check){
                    var tr_all=htmlobj.find("input[class='goods_input']").parent().parent();

                    tr_all.each(function(){
                        var all_checkbox_id=$(this).find("td:nth-child(1)").find("input").attr("checkbox_id");
                        if(all_checkbox_id){
                            var index=check.indexOf(all_checkbox_id);
                        }
                        var imglength = $(this).find("td:nth-child(1)").find("img").length;
                        if(index>=0&&imglength==0){
                            var img= "<img checkbox_id='"+all_checkbox_id+"' src='/public/images/gou.png'/>";
                            var checkbox=tr_all.find("input[checkbox_id='"+all_checkbox_id+"']");
                            checkbox.parent().append(img);
                            // checkbox.parent().html(img);
                            //checkbox.remove();
                            checkbox.prop('checked',false);
                            checkbox.hide();
                        }
                    });
                }
            })
            initData()
        });
        initData()
        //刷新页面读取已勾选数据
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
            del_input();
        }
        //选择收货仓库，自动完善收货管理员信息
        var srcfirst=$("#goods_index").attr("src");
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
                html+='<td class="text-center" href="#myModal2" class="myModal2 on" style="cursor:pointer;" data-toggle="modal" role="button"> <a>+</a> </td>';
                html+='<td hidden=hidden class="product_id"></td>';
                html+='</tr>';
                $("#tbody").html(html);
            }
            var src=srcfirst+"&mystore="+$("#mystore").val();
            $("#goods_index").attr("src",src);
        })
    //选择客户后显示
    $('#add-2').click(function(){
        var ckbox = $("#goods_index2").contents().find('.check_box:checked');
        if(ckbox != undefined){
            var name = ckbox.attr("u_name");
            var uid = ckbox.attr("uid");
            var employee_id = ckbox.attr("employee_id");
            var mobile = ckbox.attr("mobile");

            if(empty(name)){
                name='';
            }
            $('#mobile').val(name);
            $('#mobile-name').val(name+'('+mobile+')');
            $('#uid').val(uid);
            $('#employee_id').val(employee_id);
        }
    })
    //添加一件截金信息
    var cut_gold_price=$("input[name='cut_gold_price']").val();
    function add_recovery_tr(product_code,product_id,purity){
        var html='<tr class="recovery_type_tr" product_code="'+product_code+'">';
        html+='<td class="text-center"></td>'
        html+='<td class="text-center product_code">'+product_code+'<input type="hidden" name="product_id" value="'+product_id+'" placeholder="货品id"></td>'
        html+='<td class="text-center recovery_name"><input type="text" autocomplete="off" name="recovery_name" value="" ></td>'
        /*html+='<td class="text-center rproduct_code"><input type="text" autocomplete="off" name="rproduct_code" value="" ></td>'*/
        html+='<td class="text-center total_weight"><input type="number" class="no_arrow" autocomplete="off" name="total_weight" value="" placeholder="总重"></td>'
        html+='<td class="text-center purity" >'+purity+'<input type="hidden" name="purity" value="'+(purity/1000)+'" placeholder="纯度"></td>'
        html+='<td class="text-right gold_weight"><input  type="number" class="no_arrow"  autocomplete="off" name="gold_weight" value="" placeholder="金重"></td>'
        html+='<td class="text-center recovery_price" ><input  type="number" class="no_arrow"  autocomplete="off" name="recovery_price" value="'+cut_gold_price+'" placeholder="截金金价"></td>'
        html+='<td class="text-center service_fee"><input  type="number" class="no_arrow"  autocomplete="off" name="service_fee" value="" placeholder="服务克工费"></td>'
        /* html+='<td class="text-center attrition" ><input type="text" autocomplete="off" name="attrition" value="" placeholder="损耗"></td>'*/
        html+='<td class="text-center cost_price" ><input  type="number" class="no_arrow"  autocomplete="off" name="cost_price" value="" placeholder="抵扣费用"></td>'
        html+='</tr>';
        $('.recovery_tbody').prepend(html);
        change_td();
    }
    //删除一条截金信息
    function del_recovery_tr(product_code){
        //获取销售截金明细数据
        var tr=$('.recovery_tbody').find("tr[product_code='"+product_code+"']");
        var recovery_product_id=tr.find("input[name='recovery_product_id']").val();
        if(recovery_product_id){
            del_recovery_product_detail.push(recovery_product_id);
        }
        tr.remove();
    }
    //计算截金金重
    function count_gold_weight(tr){
        var total_weight=tr.find('input[name="total_weight"]').val();
        var purity=tr.find('input[name="purity"]').val();
        var weight = total_weight;//*purity;
        return weight;
    }
    //计算截金成本价
    function count_cost_price(tr){
        var cost_price=tr.find('input[name="cost_price"]');
        var gold_weight=count_gold_weight(tr);
        var recovery_price=tr.find('input[name="recovery_price"]').val();
        var service_fee=tr.find('input[name="service_fee"]').val();
        var cost_price=tr.find('input[name="cost_price"]');

        var price=gold_weight*(recovery_price*10000-service_fee*10000)/10000;
        cost_price.val(price.toFixed(2));
    }
    //总重，纯度，损耗率 ，回购金价，克工费 改变 则改变金重和成本价
    function change_td(){
        $(".recovery_tbody input[name='total_weight'],.recovery_tbody input[name='recovery_price'],.recovery_tbody input[name='purity'],.recovery_tbody input[name='service_fee']").unbind('keyup').keyup(function(){
            var tr=$(this).parent().parent();
            count_cost_price(tr);
        })
    }
    //保存时，检测数据是否正确,并返回数据
    function check_recovery_data(){
        var product_list=[];
        var tr= $(".recovery_tbody").find('tr');
        if(tr.length<1){
            return true;
        }
        var i=0;
        var is_true=true;
        tr.each(function(){
            var total_weight=$(this).find('input[name="total_weight"]').val();
            var gold_weight=$(this).find('input[name="gold_weight"]').val();
            var rproduct_code = $(this).find('input[name="rproduct_code"]').val();
            var recovery_price=$(this).find('input[name="recovery_price"]').val();
            var service_fee=$(this).find('input[name="service_fee"]').val();
            var purity=$(this).find('input[name="purity"]').val();
            var cost_price=$(this).find('input[name="cost_price"]').val();
            var product_id=$(this).find('input[name="product_id"]').val();
            var recovery_product_id=$(this).find('input[name="recovery_product_id"]').val();
            var recovery_name=$(this).find('input[name="recovery_name"]').val();
            product_list.push({
                'gold_weight': gold_weight,
                'total_weight': total_weight,
                'rproduct_code': rproduct_code,
                'recovery_price': recovery_price,
                'gold_price': recovery_price,
                'attrition':0,
                'service_fee': service_fee,
                'purity': purity,
                'cost_price': cost_price,
                'product_id': product_id,
                'recovery_product_id':recovery_product_id,
                'recovery_name':recovery_name
            });
            i=i+1;
            /*if(rproduct_code == ''){
                is_true=false;
                $('.tips_error').html('请输入第'+i+'件金料的金料编号');
                return false;
            }*/
            if(purity>1){
                is_true=false;
                $('.tips_error').html('纯度不能大于1000');
                return false;
            }
        })
        if(is_true==false){
            return false;
        }
        return product_list;
    }
