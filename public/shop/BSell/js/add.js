// <!--收款方式-->
                var rproduct_code_num=$("input[name='rproduct_code_num']").val();
                var day=$("input[name='date']").val();
                function update_rproduct_code(rproduct_code_tr,rproduct_type){
                    var tbody=rproduct_type==1?$('#old_product_tbody'):$('.recovery_tbody');
                    if(rproduct_code_tr){
                        var rproducts_codes=[];
                        rproduct_code_tr=rproduct_code_tr.split(',');
                        $.each(rproduct_code_tr,function(i,item){
                            if(item){
                                rproduct_code_num=parseInt(rproduct_code_num)+1;
                                var str='000'+rproduct_code_num;
                                var rproduct_code=day+str.substr(str.length-4);
                                tbody.find('input[name=rproduct_code]').eq(item-1).val(rproduct_code);
                                rproducts_codes.push(rproduct_code);
                            }
                        });
                        return rproducts_codes.join(',');
                    }
                }
                $(document).ready(function(){
                    $('#mobile').val('');
                    $('#uid').val('');
                })

                //获取收款列表数据
                function getall_pay_type(){
                    var is_true=true;
                    var datas = [];
                    var tr= $(".ta").find('table tbody tr[class="pay_type_tr"]');
                    var i=1;
                    tr.each(function(){
                        var pay_id =$(this).find(".pay_id").find('select').val();
                        var currency=$(this).find(".currency").find('select').val();
                        var currency_rate=$(this).find(".exchange_rate").text();
                        var flow_id =$(this).find(".flow_id").find('input').val();
                        var pay_price =$(this).find(".pay_price").find('input').val();
                        var actual_price =$(this).find(".actual_price").text();
                        if(!pay_id||pay_id==0){
                            $('.tishi').html('第'+i+'行未选择收款方式');
                            is_true=false;
                            return false;
                        }
                        if(!currency||currency==0){
                            $('.tishi').html('第'+i+'行未选择币种');
                            is_true=false;
                            return false;
                        }
                        if(!pay_price||pay_price==0){
                            $('.tishi').html('第'+i+'行未填写金额');
                            is_true=false;
                            return false;
                        }
                        i=i+1;
                        if(actual_price&&currency&&pay_id){
                            datas.push({
                                'pay_id': pay_id,
                                'flow_id': flow_id,
                                'currency': currency,
                                'currency_rate': currency_rate,
                                'pay_price': pay_price,
                                'actual_price': actual_price
                            });
                        }
                    });
                    if(is_true){
                        return JSON.stringify(datas);
                    }else {
                        return false;
                    }
                }
                //删除收款记录
                function del_pay_type() {
                    $(".pay_del").unbind("click").click(function(){
                        $(this).parent("tr").remove();
                        change_countprice();
                        change_needprice();
                    });
                }
                //总金额修改或收款金额修改
                function change_pay_price() {
                    $("#count").unbind('change').change(function() {
                        change_needprice();
                    })
                    $("input[name='pay_price']").unbind('keyup').keyup(function() {
                        change_countprice();
                        change_needprice();
                    });
                    /*change by alam 2018/5/8 start*/
                    $("#extra_price").unbind('keyup').keyup(function() {
                        count_price2();
                        change_needprice();
                    });
                    /*change by alam 2018/5/8 end*/
                }
                //修改还需支付的金额
                function change_needprice() {
                    var count_price = $("#count_price").text();
                    var count = $("#count").html();
                    var need_price = count - count_price;
                    $("#need_price").text(need_price.toFixed(2));
                }
                //修改已经支付的金额和计算金额
                function change_countprice() {
                    //默认币种汇率
                    var shop_id=$("select[name='shop_id']").val();
                    //if(shop_id==0){
                        var default_exchange_rate=$("input[name='currency0_exchange_rate']").val();
                        if(!default_exchange_rate){
                            default_exchange_rate=100;
                        }
                   /* }else {
                        var default_exchange_rate=$("select[name='shop_id']").find("option:selected").attr("default_rate");
                        if(!default_exchange_rate){
                            default_exchange_rate=100;
                        }
                    }*/
                    var con = 0;
                    $("input[name='pay_price']").each(function(key,val) {
                        var pay_price=$(this).val();
                        var actual_price=0;
                        if(pay_price==''){
                            pay_price=0;
                        }else {
                            var exchange_rate =$(this).parent().parent().find(".exchange_rate").text();
                            var actual_price=pay_price*(exchange_rate)/default_exchange_rate;
                            console.log(pay_price+"//"+exchange_rate+"//"+default_exchange_rate);
                        }
                        actual_price=actual_price.toFixed(2);
                        $(this).parent().parent().find(".actual_price").text(actual_price);
                        con = parseFloat(con) + parseFloat(actual_price);
                    })
                    $("#count_price").text(con.toFixed(2));
                }
                //修改收款方式
                function change_pay_type(){
                    $(".pay_id").find("select[name='pay_id']").unbind('change').change(function () {
                        var pay_type=$(this).find("option:selected").attr("pay_type");
                        if(pay_type>0){
                            $(this).parent().parent().find(".pay_type").text("系统内支付");
                            $(this).closest('tr').find('.flow_id').html('<input type="text" autocomplete="off" name="flow_id" value="" placeholder="流水号">');
                        }else {
                            $(this).parent().parent().find(".pay_type").text("系统外支付");
                            $(this).closest('tr').find('.flow_id').html('-<input type="hidden" name="flow_id" >');
                        }
                    })
                }
                //币种选取，修改币种参数以及计算金额和已经支付金额，还需支付金额
                function change_exchange_rate(){
                    $(".currency").find("select[name='currency']").unbind('change').change(function () {
                        var exchange_rate=$(this).find("option:selected").attr("exchange_rate");
                        var unit=$(this).find("option:selected").attr("unit");
                        $(this).parent().parent().find(".exchange_rate").text(exchange_rate);
                        $(this).parent().parent().find(".unit").text(unit);
                        //默认币种汇率
                        var shop_id=$("select[name='shop_id']").val();
                        if(shop_id==0){
                            var default_exchange_rate=$("input[name='currency0_exchange_rate']").val();
                            if(!default_exchange_rate){
                                default_exchange_rate=100;
                            }
                        }else {
                            var default_exchange_rate=$("select[name='shop_id']").find("option:selected").attr("default_rate");
                            if(!default_exchange_rate){
                                default_exchange_rate=100;
                            }
                        }
                        var pay_price=$(this).parent().parent().find("input[name='pay_price']").val();
                        var actual_price=pay_price*exchange_rate/default_exchange_rate;
                        actual_price=actual_price.toFixed(2);
                        $(this).parent().parent().find(".actual_price").text(actual_price);
                        change_countprice();
                        change_needprice();
                    })
                }
                //添加一列收款记录
                function add_pay_type(){
                    $("#add_pay_type").unbind('click').click(function () {
                        var pay_id_html=$("#pay_html").html();
                        var currency_html=$("#currency_html").html();
                        var html='<tr class="pay_type_tr">';
                        html+='<td class="text-center"></td>';
                        html+='<td class="text-center pay_id">'+pay_id_html+'</td>';
                        html+='<td class="text-center currency">'+currency_html+'</td>';
                        // html+='<td class="text-right flow_id"><input type="text" autocomplete="off" name="flow_id" value="" placeholder="流水号"></td>';
                        html+='<td class="text-right flow_id">-<input type="hidden" name="flow_id" ></td>';
                        html+='<td class="text-center pay_price"><input type="text" autocomplete="off" name="pay_price" value="0" placeholder="收款金额"></td>';
                        html+='<td class="text-center unit"></td>';
                        html+='<td class="text-right exchange_rate"></td>';
                        html+='<td class="text-center pay_type"></td>';
                        html+='<td class="text-right actual_price">0.00</td>';
                        html+='<td class="text-center pay_del" style="color: #3daae9;cursor:pointer;">删除</td>';
                        html+='</tr>';
                        $(".pay_tbody").find("#last").before(html);
                        table_order();
                        change_pay_type();
                        change_exchange_rate();
                        change_pay_price();
                        del_pay_type();
                    })
                }
                change_pay_type();
                add_pay_type();
                change_exchange_rate();
                change_pay_price();

                // heightAuto($("#myModal2"));
                // heightAuto($("#myModal3"));

                localStorage.removeItem('p_id');
                $("#myModal3").on("shown.bs.modal",function () {
                    $("#goods_index2").contents().find("#mobile").focus();
                })
                //销售总价
                function count_price2(){
                    var con = 0;
                    $('tr td .price2').each(function(key,val){
                        if($(this).val()){
                            con = parseFloat(con) + parseFloat($(this).val());
                        }
                    })
                    var cut_cost_price = count_price();
                    var old_cost_price = count_old_product_price();
                    con = con - parseFloat(cut_cost_price)- parseFloat(old_cost_price);
                    /*change by alam 2018/5/8 start*/
                    // 其它费用
                    $('tr td .sub_cost').each(function(key,val){
                        if($(this).val()){
                            con = parseFloat(con) + parseFloat($(this).val());
                        }
                    })
                    // 抹零金额
                    var extra_price = $('#extra_price').val();
                    con = con - extra_price;
                    /*change by alam 2018/5/8 end*/
                    $('#count').html(con.toFixed(2));
                    $('#count_sell_price').html(con.toFixed(2));
                    $('#total_price').val(con.toFixed(2));
                    change_needprice();
                }
                //销售价、其它费用改变 销售总价改变
                function count(){
                    $("input.price2").unbind('keyup').keyup(function(){
                        count_price2();
                    })
                    /*change by alam 2018/5/8 start*/
                    $("input.sub_cost").unbind('keyup').keyup(function(){
                        count_price2();
                    })
                    /*change by alam 2018/5/8 end*/
                }
                //优惠价,克工费,克单价，实称克重改变销售价和销售总价
                function td_change(){
                    $("input[name='discount_price'],input[name='sell_g_price'],input[name='goods_weight']").unbind('keyup').keyup(function(){
                        var tr = $(this).closest('tr');

                        var gold_price = parseFloat(tr.find('.gold_price').find('input').val());//金价
                        gold_price = isNaN(gold_price)? 0 : gold_price;

                        var discount_price = parseFloat(tr.find('.discount_price').find('input').val());//单品优惠
                        discount_price = isNaN(discount_price) ? 0 : discount_price;

                        var m_fee = parseFloat(tr.find('.goods_gram_price').text())>0?parseFloat(tr.find('.goods_gram_price').text()):0;//工费
                        m_fee = m_fee == '' ? 0 : m_fee;

                        var goods_weight = parseFloat(tr.find('.goods_weight').find('input').val());//重量
                        goods_weight = isNaN(goods_weight) ? 0 : goods_weight;

                        //计价
                        var sell_price = tr.find('.goods_unit_price').find('input').attr("price");//销售价
                        var jijia = tr.find('.jijia').text();
                        var price2 = 0;

                        //销售工费方式
                        var sell_feemode=tr.attr('sell_feemode');

                        if(jijia =='计件'){
                            price2 = sell_price - $(this).val();
                        }else{
                            if(sell_feemode==1){
                                price2 = ((parseFloat(gold_price)+parseFloat(m_fee)) * (parseFloat(goods_weight))-parseFloat(discount_price));
                            }else if(sell_feemode==0){
                                price2 = ((parseFloat(gold_price)) * (parseFloat(goods_weight))+parseFloat(m_fee)-parseFloat(discount_price));
                            }

                        }

                        tr.find('.goods_unit_price').find('input').val(price2.toFixed(2));

                        count_price2();
                    })
                }
                var tr_order=1;

                function table_order(){
                    var len = $(".ta").find('table tr').length;
                    for(var i = 1;i<len;i++){
                        $(".ta").find('table tr:eq('+i+') td:first').text(i);
                    }
                    count_num();
                }

                function del(){
                    $(".del").click(function(){
                        tr.remove();
                    });
                }

                function del_input(){
                    //删除tr
                    $(".del").each(function(){
                        $(this).click(function(){
                            var tr=$(this).parent().parent();
                            tr.remove();
                            table_order();
                            var price = tr.find('.goods_unit_price').find('input').val();
                            var count = $('#count').html();
                            if(price){
                                if(count !='' && count != NaN){
                                    count2 =(parseFloat(count)-parseFloat(price)).toFixed(2);
                                }
                            }
                            count_price2();
                            $('#total_price').val(count2);
                            var checked=localStorage.getItem('p_id');
                            var p_id=tr.find(".goods_id").find("input").val();
                            checked=checked.replace("ck"+p_id+"ck","");
                            localStorage.setItem('p_id',checked);
                            //删除后将图片换回复选框
                            var html=$('#goods_index').contents();
                            var img=html.find(".ta").find("tbody img[p_id='ck"+p_id+"ck']");
                            var checkbox=html.find(".ta").find("tbody input[p_id='ck"+p_id+"ck']");
                            checkbox.show();
                            checkbox.prop('checked',false);
                            img.remove();

                            // 删除对应截金
                            var product_code = tr.find('.goods_code').text();
                            del_recovery_tr(product_code);
                        });
                    });
                }
                $("#myModal2").draggable();
                $("#myModal3").draggable();
                $("#tr_add").click(function(){
                    var s=$("#last_tr").prev().find(".goods_order").text();
                    var ss=parseInt(s)+1;
                    var html="";
                    html+="<tr>";
                    html+=$("#tr_info").html();
                    html+="</tr>";
                    $("#last_tr").before(html);
                    $("#last_tr").prev().find(".goods_order").text(ss);
                    table_order();
                    myModal2();
                    del_input();
                });
                $('#goods_index').load(function () {
                    $("#check").unbind('click').click(function(){
                        var html=$('#goods_index').contents();
                        var tr=html.find("input.goods_input:checked").parent().parent();
                        var tr2=$(this).parent().parent();
                        tr.each(function(){
                            if ($(this).find("td:nth-child(1)").find("img").length > 0) {

                            }else{
                                var s=$("#last_tr").prev().find(".goods_order").text();
                                var ss=parseInt(s)+1;
                                var p_id=$(this).find(".goods_id_code").find("input").attr("p_id");
                                var checked=localStorage.getItem('p_id');
                                checked+=","+p_id;
                                localStorage.setItem('p_id',checked);
                                var sell_feemode=$(this).attr('sell_feemode');
                                var html="";
                                html+="<tr id='zz' class='' sell_feemode='"+sell_feemode+"' gold_price='"+$(this).attr('gold_price')+"' gold_type='"+$(this).attr('gold_type')+"' product_code='"+$(this).find(".goods_code").text()+"'>";
                                html+='<td class="goods_order text-center" >'+$(this).find(".goods_order").text()+'</td>';
                                html+=' <td class="goods_code text-center">'+$(this).find(".goods_code").text()+'</td>';
                                html+=' <td class="goods_name">'+$(this).find(".goods_name").text()+'</td>';
                                html+=' <td class="goods_spec t">'+$(this).find(".goods_spec").text()+'</td>';
                                html+=' <td class="goods_purity text-center">'+$(this).find(".goods_purity").text()+'</td>';
                                console.log($(this).find(".goods_way").text());
                                if($(this).find(".goods_way").text()=='计件'){
                                    html+='<td class="sell_feemode">--</td>';
                                    html+=' <td hidden="hidden" class="price">'+$(this).find(".goods_unit_price").text()+'</td>';
                                    html += ' <td class="goods_gram_price">'+$(this).find(".goods_gram_price").text()+'</td>';
                                    html+=' <td class="gold_price"><input type="text" autocomplete="off" name="sell_g_price" readonly="readonly" class="sell_g_price" value='+($(this).attr('gold_price')>0?$(this).attr('gold_price'):0)+'></td>';
                                }else{
                                    var sell_feemode_name=sell_feemode==1?'克工费销售':(sell_feemode==0?'件工费销售':'');
                                    html+='<td class="sell_feemode">'+sell_feemode_name+'</td>';
                                    html+=' <td hidden="hidden">'+$(this).attr('gold_price')+'</td>';
                                    html += ' <td class="goods_gram_price">'+$(this).find(".goods_gram_price").text()+'</td>';
                                    html+=' <td class="gold_price"><input type="text" autocomplete="off" name="sell_g_price" class="sell_g_price" value='+($(this).attr('gold_price')>0?$(this).attr('gold_price'):0)+'></td>';
                                }
                                //    html+=' <td class="goods_gram_price"><input type="text" autocomplete="off" name="m_fee" class="m_fee" value='+$(this).find(".goods_gram_price").text()+'> </td>';
                                html+=' <td class="procure_weight text-right">'+$(this).find(".goods_weight").text()+'</td>';
                                if($(this).find(".product_type").text()!='1'){
                                    html+=' <td class="goods_weight">--</td>';
                                }else{
                                    html+=' <td class="goods_weight"><input type="text" autocomplete="off"   class="g text-right" name="goods_weight" value='+$(this).find(".goods_weight").text()+' /></td>';
                                }
                                html+=' <td  class="be_onsale_price">'+ $.trim($(this).find(".goods_unit_price").text())+'</td>';
                                html+=' <td  class="discount_price"><input type="text" autocomplete="off" name="discount_price" class="discount_price"   value="0"></td>';
                                html+=' <td  class="goods_unit_price price2"><input type="text" autocomplete="off" name="price2" class="price2" price='+ $(this).find(".goods_unit_price").text()+'  value='+ $(this).find(".goods_unit_price").text()+'></td>';
                                html+='<td class="jijia text-center">'+$(this).find(".goods_way").text()+'</td>';
                                html+='<td class="goods_id" hidden ="hidden"><input id ="pid" type="text" value='+$(this).find(".goods_id").text()+'></td>';
                                //  html+='<td hidden ="hidden">'+$(this).find("td:nth-child(11)").text()+'</td>';
                                if($(this).find(".product_type").text()!='1'){
                                    html+='<td class="is_cut">/</td>';
                                    html+='<td class="cut_weight text-right">/</td>';
                                }else{
                                    html+='<td class="is_cut"><select name="is_cut"><option value="1">是</option><option selected="selected" value="0">否</option></select></td>';
                                    html+='<td class="cut_weight text-right">0.00<input type="hidden" product_code="'+$(this).find(".goods_code").text()+'" name="cut_weight" value="0.00"></td>';
                                }
                                html+='<td class="text-center">'
                                html+='<a href="javascript:void(0);" name="{$v.id}"  class="del" role="button" data-toggle="modal">删除</i></a>';
                                html+='</td>';
                                html+='</tr>';
                                $("#last").before(html);
                            }
                        });
                        //刷新页面读取已勾选数据
                        var check=localStorage.getItem('p_id');
                        if(check!=""&&check!=null&&typeof(check)!="undefined"){
                            var checked=check.split(',');
                            for(var i in checked){
                                if(checked[i]!=null&&checked[i]!=""&&typeof(checked[i])!="undefined"){
                                    var checkbox=tr.find("input[p_id='"+checked[i]+"']");
                                    var imglength=checkbox.parent().find("img").length;
                                    if(checkbox.length>0&&imglength==0){
                                        var img= "<img p_id='"+checked[i]+"' src='/public/images/gou.png'/>";
                                        checkbox.parent().append(img);
                                        checkbox.prop('checked',false);
                                        checkbox.hide();
                                    }
                                }
                            }
                        }
                        cutweight();
                        table_order();
                        myModal2();
                        del_input();
                        count_price2();
                        count();
                        td_change();
                        del();
                    })
                    $("#add").unbind('click').click(function(){
                        $("#check").trigger("click");
                    })
                });

                $('#add-2').click(function(){
                    // name = localStorage.getItem('sell_name');
                    // mobile = localStorage.getItem('sell_mobile');
                    // uid = localStorage.getItem('sell_uid');

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
                function myModal2(){
                    $('.myModal2').each(function () {
                        $(this).click(function(){
                            $(".myModal2.on").removeClass("on");
                            $(this).addClass("on");
                            if(!this.hasClass("on")){}
                        })

                    })
                }
                //销售货品明细
                function get_sell_detail_data(){
                    var tishi = '';
                    var total_gold_weight=0;
                    var names = [];
                    var tr = $(".ta").find('table tbody tr[id="zz"]');
                    tr.each(function(){
                        var id = $(this).find(".goods_id").find('input').val();
                        if(id){
                            var jjfs = $(this).find(".jijia").text();
                            var sell_g_price = $(this).find(".sell_g_price").val();
                            var gold_type = $(this).attr('gold_type');
                            var sell_m_fee = $.trim($(this).find(".goods_gram_price").text());
                            var discount_price = $(this).find(".discount_price").find('input').val();
                            var rell_sell_price = $(this).find(".goods_unit_price").find('input').val();
                            var be_onsale_price = $(this).find(".be_onsale_price").text();
                            var is_cut = $(this).find("select[name='is_cut']").val();
                            var cut_weight = $(this).find("input[name='cut_weight']").val();
                            var goods_weight = $(this).find("input[name='goods_weight']").val();
                            //销售工费方式
                            if(jjfs =='计件'){
                                var sell_feemode=0;
                            }else {
                                var sell_feemode=$(this).attr('sell_feemode');
                            }
                            if(goods_weight){
                                total_gold_weight=total_gold_weight+parseFloat(goods_weight);
                            }
                            names.push({
                                'id': id,
                                'be_onsale_price': be_onsale_price,
                                'discount_price': discount_price,
                                'sell_price': rell_sell_price,
                                'sell_m_fee': sell_m_fee,
                                'sell_pricemode': jjfs,
                                'sell_feemode':sell_feemode,
                                'sell_g_price': sell_g_price,
                                'gold_type': gold_type,
                                'is_cut': is_cut,
                                'cut_weight': cut_weight,
                                'goods_weight': goods_weight,
                            });
                            if(!rell_sell_price){
                                tishi = '销售价不能为空';
                                $('.tishi').html(tishi);
                                return false;
                            }
                        }
                    })
                    //判断有数据但销售价为空
                    if (tishi&&names.length>0) {
                        return false;
                    }
                    var post_data={total_product_gold_weight:total_gold_weight,names:names}
                    return post_data;
                }
                function get_sell_order(status){
                    var order = {
                        status:status ,
                        client_idno: $("input[name='client_idno']").val(),
                        sn_id: $("input[name='sn_id']").val(),
                        sell_type: $("input[name='sell_type']:checked").val(),
                        buyer_id: $('#uid').val(),
                        employee_id: $('#employee_id').val(),
                        count: $('#count').html(),
                        sell_date: $('#sell_date').val(),
                        remark: $("#remark").val(),
                        extra_price: $("#extra_price").val(),
                        main_currency_id:$("input[name='currency0_id']").val(),
                        main_currency_rate:$("input[name='currency0_exchange_rate']").val(),
                        /*main_currency_id:$("select[name='shop_id']").find("option:selected").attr("currency_id"),
                        main_currency_rate:$("select[name='shop_id']").find("option:selected").attr("default_rate"),*/
                    }
                    return order;
                }
                //获取其他费用信息
                function  get_sub_data(){
                    /*change by alam 其它费用 2018/5/8 start*/
                    // 其它费用
                    var tishi = '';
                    var sub_datas = [];
                    var sub_tr = $(".ta").find('table tbody[id="sub_tbody"] tr');
                    sub_tr.each(function(){
                        var expence_id = $(this).find(".expence_id").find('select').val();
                        var sub_cost = $(this).find(".sub_price").find('input').val();
                        if(expence_id != 0 && sub_cost == '') {
                            tishi = '其它费用费用金额不能为空';
                            return false;
                        } else if(expence_id == 0 && sub_cost != '') {
                            tishi = '其它费用未选择费用类目';
                            return false;
                        } else if(expence_id != 0 && sub_cost != '') {
                            sub_datas.push({
                                'expence_id': expence_id,
                                'sub_cost': sub_cost
                            });
                        }
                    })
                    if (tishi&&sub_datas.length>0) {
                        $('.tishi').html(tishi);
                        return false;
                    }
                    return sub_datas;
                }
                function save(){
                    $("#baocun,#submit").click(function(){
                        var tishi = '';
                        var mobile = $("#mobile").val();
                        var uid = $('#uid').val();
                        var employee_id = $('#employee_id').val();
                        if(mobile.length <= 0 || (uid == '' && employee_id == '')){
                            $('.tishi').html('会员姓名不能为空');
                            return false;
                        }
                        //销售货品明细
                        var product_data=get_sell_detail_data();
                        if(product_data===false){
                            return false
                        }
                        var names=product_data.names;
                        var total_product_gold_weight=product_data.total_product_gold_weight;//货品克重
                        var sub_datas=get_sub_data();//获取其他费用信息
                        if(sub_datas===false){
                            return false
                        }
                        console.log(names.length < 1);
                        if(names.length < 1 && sub_datas.length < 1){
                            $('.tishi').html('请选择需要销售的货品或填写其它费用');
                            return false;
                        }
                        names = JSON.stringify(names);
                        sub_datas = JSON.stringify(sub_datas);
                        var saccout_record=getall_pay_type();
                        if(saccout_record===false){
                            return false
                        }
                        var order =get_sell_order($(this).data('type'));//获取销售单信息
                        var product_count = $("#total_num").text();//销售货品数量
                        var shop_id = $("select[name='shop_id']").val();
                        var recovery_products = check_recovery_data();//获取截金信息
                        var post_data = {
                            order:order,
                            count:product_count,
                            shop_id:shop_id,
                            order_detail:eval(names),
                            sub_datas:eval(sub_datas),
                            saccout_record:eval(saccout_record),
                        };
                        if(recovery_products.length>0){
                            post_data.recovery_products=recovery_products;
                        }

                        //判断以旧换新信息内容是否正确
                        //获取以旧换新信息
                        var old_product_data=check_old_product_data();
                        var total_recovery_gold_weight=old_product_data.total_recovery_gold_weight;//以旧换新克重
                        var recovery_old_products=old_product_data.product_list;
                        var recovery_wh_id=$("select[name='wh_id']").val();
                        if(old_product_data){
                            if(typeof(recovery_old_products)=='object'){
                                post_data.recovery_old_products=recovery_old_products;
                                if(total_product_gold_weight<total_recovery_gold_weight){
                                    $('.tishi').html('以旧换新，旧金金重不能大于货品金重');
                                    return false;
                                }
                            }
                        }else {
                            return false;
                        }
                        if($('input[name="sell_type"]:checked').val()==2&&!post_data.recovery_old_products){
                            $('.tishi').html('以旧换新的金料不能为空');
                            artdialog_alert('以旧换新的金料不能为空');
                            return false;
                        }
                        var pay_price=parseFloat($("#count_price").text());
                        var price=parseFloat($("#count").text());
                        if(pay_price!=price){
                            $('.tishi').html('收款总额与销售总额必须相等');
                            artdialog_alert('收款总额与销售总额必须相等');
                            return false;
                        }
                        $.ajax({
                            url: API_URL+"&m=BSell&a=add",
                            type: 'post',
                            async: false,
                            data: post_data,
                            beforeSend: function() {
                                $(this).attr("disabled",true);
                            },
                            success: function (data) {
                                if(data.status==1){
                                    $('.tishi').html('添加成功');
                                    location.href=data.url;
                                }else if(data.status==0){
                                    if(data.exist_rproduct_code){
                                        var rproduct_codes=update_rproduct_code(data.rproduct_code_tr,data.rproduct_type);
                                        $('.tishi').html(data.msg+'已自动变更为'+rproduct_codes);
                                    }else {
                                        $('.tishi').html(data.msg);
                                    }
                                    $(this).attr("disabled", false);
                                    return false;
                                }
                            },
                            error: function (data,status) {
                                $(this).attr("disabled", false);
                            }
                        })
                    });
                }
                function count_num(){
                    var num=0;
                    $(".ta").find('table tr').each(function(){
                        if($(this).attr('id')=='zz'){
                            num++;
                        }
                    });
                    $('#total_num').html(num);
                }
                save();
                count();
                table_order();
            // <!--截金回购-->
                var cut_gold_price=$("input[name='cut_gold_price']").val();
                console.log(cut_gold_price);
                function cutweight(){
                    var sele = $('select[name=is_cut]');
                    sele.unbind('change').change(function(){
                        var tr=$(this).parent().parent();
                        var product_code=$.trim(tr.find('.goods_code').text());
                        var purity=$.trim(tr.find('.goods_purity').text());
                        //purity=purity.replace('‰','');
                        var product_id=tr.find('#pid').val();
                        var recovery_name= $.trim(tr.find('.goods_name').text());
                        if($(this).val()=='0'){
                            // 截金：否
                            $(this).closest('tr').find('.cut_weight').html('0.00<input type="hidden" product_code="'+ tr.find(".goods_code").text()+'" name="cut_weight" value="0.00" >');
                            del_recovery_tr(product_code);
                        } else{
                            // 截金：是
                            $(this).closest('tr').find('.cut_weight').html('<input type="text" autocomplete="off" product_code="'+ tr.find(".goods_code").text()+'" name="cut_weight" value="0.00" >');
                            add_recovery_tr(product_code,product_id,purity,recovery_name);
                        }
                        table_order();
                        change_td();

                        var recovery_num = $('.recovery_tbody tr').length;
                        if(recovery_num > 0){
                            $('#recovery_table').show();
                        }else{
                            $('#recovery_table').hide();
                        }
                    });
                }
                function add_recovery_tr(product_code,product_id,purity,recovery_name){
                    rproduct_code_num=parseInt(rproduct_code_num)+1;
                    var str='000'+rproduct_code_num;
                    var rproduct_code=day+str.substr(str.length-4);
                    var html='<tr class="recovery_type_tr" product_code="'+product_code+'">';
                    html+='<td class="text-center"></td>'
                    html+='<td class="text-center product_code">'+product_code+'<input type="hidden" name="product_id" value="'+product_id+'" placeholder="货品id"></td>'
                    html+='<td class="text-center recovery_name"><input type="text" autocomplete="off" name="recovery_name" value="'+recovery_name+'" ></td>'
                    html+='<td class="text-right rproduct_code"><input type="text" readonly="readonly" autocomplete="off" name="rproduct_code" value="'+rproduct_code+'" ></td>'
                    html+='<td class="text-right total_weight"><input type="text" autocomplete="off" name="total_weight" value="" placeholder="总重"></td>'
                    /*html+='<td class="text-right gold_weight"><input type="text" autocomplete="off" name="gold_weight" value="" placeholder="金重"></td>'*/
                    html+='<td class="text-center recovery_price" ><input type="text" autocomplete="off" name="recovery_price" value="'+cut_gold_price+'" placeholder="截金金价"></td>'
                    html+='<td class="text-center service_fee"><input type="text" autocomplete="off" name="service_fee" value="" placeholder="服务克工费"></td>'
                    html+='<td class="text-center purity" >'+purity+'<input type="hidden" name="purity" value="'+(purity/1000)+'" placeholder="纯度"></td>'
                    /* html+='<td class="text-center attrition" ><input type="text" autocomplete="off" name="attrition" value="" placeholder="损耗"></td>'*/
                    html+='<td class="text-center cost_price" ><input type="text" autocomplete="off" name="cost_price" value="" placeholder="抵扣费用"></td>'
                    html += ' <td class="text-center td_material"><input type="text"  name="material" class="material" value=""></td>';//材质
                    html += ' <td class="text-center td_color"><input type="text"  name="color" class="color" value=""></td>';//颜色
                    html+='</tr>';

                    $('.recovery_tbody').prepend(html);
                }
                function del_recovery_tr(product_code){
                    $('.recovery_tbody').find("tr[product_code='"+product_code+"']").remove();
                    count_price2();
                }
                //统计货品条数
                function count_recovery_num(){
                    var num=0;
                    $(".recovery_tbody").find('table tr').each(function(){
                        if($(this).attr('id')=='zz'){
                            num++;
                        }
                    });
                    console.log(num);
                    return num;
                }
                //统计成本价
                function count_price(){
                    var price=0;
                    $('.recovery_type_tr').find("input[name='cost_price']").each(function(key,val){
                        if($(this).val()){
                            price = parseFloat(price) + parseFloat($(this).val());
                        }
                    })
                    return price.toFixed(2);
                }
                //计算金重
                function count_gold_weight(tr){
                    var total_weight=tr.find('input[name="total_weight"]').val();
                    var purity=tr.find('input[name="purity"]').val();
                    var weight = total_weight;//*purity;
                    return weight;
                }
                //计算成本价
                function count_cost_price(tr){
                    var cost_price=tr.find('input[name="cost_price"]');
                    var gold_weight=count_gold_weight(tr);
                    var recovery_price=tr.find('input[name="recovery_price"]').val();
                    var service_fee=tr.find('input[name="service_fee"]').val();
                    var cost_price=tr.find('input[name="cost_price"]');

                    console.log(gold_weight+"/"+recovery_price+"/"+service_fee);

                    var price=gold_weight*(recovery_price*10000-service_fee*10000)/10000;
                    cost_price.val(price.toFixed(2));
                    // count_price();
                    count_price2();
                }
                //总重，纯度，损耗率 ，回购金价，克工费 改变 则改变金重和成本价
                function change_td(){
                    $("input[name='total_weight'],input[name='recovery_price'],input[name='purity'],input[name='service_fee']").unbind('keyup').keyup(function(){
                        var tr=$(this).parent().parent();
                        count_cost_price(tr);
                    })
                }
                //保存时，检测数据是否正确,并返回数据
                function check_recovery_data(){
                    var product_list=[];
                    var tr= $(".recovery_tbody").find('tr');
                    var i=1;
                    var is_false=false;
                    tr.each(function(){
                        var recovery_name=$(this).find('input[name="recovery_name"]').val();
                        var total_weight=$(this).find('input[name="total_weight"]').val();
                        var rproduct_code = $(this).find('input[name="rproduct_code"]').val();
                        var recovery_price=$(this).find('input[name="recovery_price"]').val();
                        var service_fee=$(this).find('input[name="service_fee"]').val();
                        var purity=$(this).find('input[name="purity"]').val();
                        var cost_price=$(this).find('input[name="cost_price"]').val();
                        var product_id=$(this).find('input[name="product_id"]').val();
                        var material = $(this).find('input[name="material"]').val();
                        var color = $(this).find('input[name="color"]').val();
                        i=i+1;
                        product_list.push({
                            'recovery_name': recovery_name,
                            'total_weight': total_weight,
                            'rproduct_code': rproduct_code,
                            'recovery_price': recovery_price,
                            'service_fee': service_fee,
                            'purity': purity,
                            'cost_price': cost_price,
                            'product_id': product_id,
                            'material' : material,
                            'color' : color,
                        });
                        /*if(rproduct_code == ''){
                            is_false=true
                            $('.tishi').html('请输入第'+i+'个金料的金料编号');
                            return false;
                        }*/
                    })
                    if(product_list.length>0&&is_false){
                        return false;
                    }else {
                        return product_list;
                    }

                }
                function recovery_list(){

                }
                //选择收货仓库，自动完善收货管理员信息
                var srcfirst=$("#goods_index").attr("src");
                $("select[name='shop_id']").change(function(){
                    localStorage.removeItem('p_id');
                    var trleng=$("#tbody").find("tr").length;
                    if(trleng>1){
                        var html='<tr id="last">';
                        html+=$("#last").html();
                        html+='</tr>';
                        $("#tbody").html(html);
                    }
                    var src=srcfirst+"&shop_id="+$("select[name='shop_id']").val();
                    $("#goods_index").attr("src",src);
                    change_countprice();
                    change_needprice();
                });
            // <!--change by alam 2018/5/8 其它费用 start-->
                //添加一列其它费用
                function add_sell_sub(){
                    $("#sub_add").unbind('click').click(function () {
                        var expence_html=$("#expence_html").html();
                        var html='<tr>';
                        html+='<td class="text-center"></td>';
                        html+='<td class="text-center expence_id">'+expence_html+'</td>';
                        html+='<td class="text-center sub_price"><input type="number" autocomplete="off" class="sub_cost no_arrow" step="0.01" value="" placeholder="类目金额" style="width:220px;"></td>';
                        html+='<td class="text-center" id="sub_add" role="button" style="cursor:pointer;"><a href="javascript:void(0);">+</a></td>';
                        html+='</tr>';
                        $("#sub_add").addClass('sub_del');
                        $("#sub_add").find('a').html('删除');
                        $("#sub_add").removeAttr('id');
                        $("#sub_tbody").append(html);
                        table_order();
                        count();
                        add_sell_sub();
                        del_sell_sub();
                    });
                }
                //删除收款记录
                function del_sell_sub() {
                    $(".sub_del").unbind("click").click(function(){
                        var tr = $(this).parent("tr");
                        var price = tr.find('td').find('input').val();
                        var count = $('#count').html();
                        if(price){
                            if(count != '' && count != NaN){
                                count =(parseFloat(count) - parseFloat(price)).toFixed(2);
                            }
                        }
                        $('#total_price').val(count);
                        tr.remove();
                        table_order();
                        change_countprice();
                        change_needprice();
                        count_price2();
                    });
                }
                add_sell_sub();
            // <!--change by alam 2018/5/8 其它费用 end-->
            // <!-- 会员操作弹窗联动js -->
                function loadFrame(obj){
                    var url = obj.contentWindow.location.href;
                    if(url.indexOf(API_URL+"&m=BShop&a=client_list")!=-1){
                        $('#clientModalLabel').text('选择会员');
                    }else if(url.indexOf(API_URL+"&m=BShop&a=add_client")!=-1){
                        $('#clientModalLabel').text('添加会员');
                    }
                }
            // <!--以旧换新-->
                //销售类型切换
                $('input[name="sell_type"]').unbind('change').change(function(){
                    if($(this).val()==2){
                        $('#recovery_old_product').show();
                        $('.client_idno').show();
                    }else{
                        $('#recovery_old_product').hide();
                        $('.client_idno').hide();
                    }
                });
                //添加一行旧金
                $("#add_old_product").unbind("click").click(function() {
                    var gold_price = $("input[name='gold_price']").val();
                   var recovery_price = $("input[name='recovery_price']").val();
                    rproduct_code_num=parseInt(rproduct_code_num)+1;
                    var str='000'+rproduct_code_num;
                    var rproduct_code=day+str.substr(str.length-4);
                    var html = '';
                    html += '<tr id="old_product_tr" class="old_product_tr">';
                    html += '<td class="text-center"></td>';//序
                    html += ' <td class="text-center td_recovery_name"><input type="text" autocomplete="off" name="recovery_name" class="recovery_name" value=""></td>';
                    html += ' <td class="text-center td_rproduct_code"><input type="text" readonly="readonly" autocomplete="off" name="rproduct_code" class="rproduct_code" value="'+rproduct_code+'"></td>';
                    html += ' <td class="text-center td_total_weight"><input type="number" step="0.001" autocomplete="off" name="total_weight" class="total_weight input_init no_arrow" value="0.00"></td>';//总重
                    html += ' <td class="text-center td_purity"><input type="number" step="0.001" autocomplete="off" name="purity" class="purity no_arrow" placeholder="999.9"></td>';//纯度
                    html += ' <td class="text-center td_gold_weight"><input type="number" step="0.001"autocomplete="off" name="gold_weight" class="gold_weight input_init no_arrow" value="0.00"></td>';//金重
                    html += ' <td class="text-center td_recovery_price"><input type="number" step="0.01" autocomplete="off" name="recovery_price" class="recovery_price no_arrow" value="'+recovery_price+'"></td>';//回购金价
                    html += ' <td class="text-center td_gold_price"><input type="number" step="0.01" autocomplete="off" name="gold_price" class="gold_price no_arrow" value="'+gold_price+'"></td>';//当前金价
                    html += ' <td class="text-center td_service_fee"><input type="number" step="0.01" autocomplete="off" name="service_fee" class="service_fee input_init no_arrow" value=""></td>';//服务克工费
                    //html += ' <td class="text-center td_attrition"><input type="number" step="0.001" autocomplete="off" name="attrition" class="attrition input_init no_arrow" value="0.00"></td>';//损耗率
                    html += ' <td class="text-center td_cost_price"><input type="number" step="0.01" autocomplete="off" name="cost_price" class="cost_price input_init no_arrow" value="0.00"></td>';//成本价
                    html += ' <td class="text-center td_material"><input type="text" autocomplete="off" name="material" class="material" value=""></td>';//材质
                    html += ' <td class="text-center td_color"><input type="text"  autocomplete="off" name="color" class="color" value=""></td>';//颜色
                    html += '<td class="text-center">'
                    html += '<a href="javascript:void(0);" name="{$v.id}"  class="old_product_del" >删除</i></a>';
                    html += '</td>';
                    html += '</tr>';
                    $("#old_product_last").before(html);
                    table_order();
                    old_product_del();
                    change_old_product_td();
                    change_old_product_price();
                });
                //给删除按钮添加删除事件
                function old_product_del() {
                    $(".old_product_del").unbind("click").click(function() {
                        var tr = $(this).parent().parent();
                        tr.remove();
                        table_order();
                        count_price2();
                    });
                }
                //成本价绑定keyup事件，成本价更改，改变总价
                function change_old_product_price() {
                    $("#old_product_tbody input[name='cost_price']").unbind('keyup').keyup(function() {
                        count_price2();
                    })
                }

                //总重，纯度，损耗率 ，回购金价，克工费 改变 则改变金重和成本价
                function change_old_product_td() {
                    $("#old_product_tbody input[name='total_weight'],#old_product_tbody input[name='recovery_price'],#old_product_tbody input[name='purity'],#old_product_tbody input[name='service_fee']").unbind('keyup').keyup(function() {
                        var tr = $(this).parent().parent();
                        count_old_product_cost_price(tr);
                    })
                }
                //计算每条旧金成本价
                function count_old_product_cost_price(tr) {
                    var cost_price = tr.find('input[name="cost_price"]');
                    var gold_weight = count_old_product_weight(tr);
                    var recovery_price = tr.find('input[name="recovery_price"]').val();
                    var service_fee = tr.find('input[name="service_fee"]').val();
                    var cost_price = tr.find('input[name="cost_price"]');
                    var price = gold_weight * (recovery_price * 10000 - service_fee * 10000) / 10000;
                    cost_price.val(price.toFixed(2));
                    count_price2();
                }
                //统计所有旧金成本价 用于更改总的销售金额
                function count_old_product_price() {
                    var price = 0;
                    $('#old_product_tr td .cost_price').each(function(key, val) {
                        if ($(this).val()) {
                            price = parseFloat(price) + parseFloat($(this).val());
                        }
                    })
                    return price;
                }
                // 计算金重 [change by alam 2018/5/15 this function]
                function count_old_product_weight(tr) {
                    var total_weight = parseFloat(tr.find('input[name="total_weight"]').val());
                    var gold_weight = tr.find('input[name="gold_weight"]');
                    var purity = parseFloat(tr.find('input[name="purity"]').val());
                    purity = purity / 1000;
                    //var attrition = tr.find('input[name="attrition"]').val();
                    var weight = total_weight * purity ;
                    gold_weight.val(weight.toFixed(2));
                    return weight.toFixed(2);
                }
                // 比较旧金金重和购买的货品总重
                function compare_weight() {

                }
                //清空以旧换新信息
                function clear_old_product_info(){
                    if($('.old_product_tr').length>1){
                        var html='<tr id="last">';
                        html+=$('#old_product_last').html();
                        html+='</tr>';
                        $('#old_product_tbody').html(html);
                        $('#recovery_old_product').hide();
                    }
                }
                //保存时，检测数据是否正确,并返回数据
                function check_old_product_data() {
                    is_true = true;
                    var product_list = [];
                    var tr = $(".old_product_tr");
                    if (tr.length < 1) {
                        return true;
                    }
                    console.log(tr.length);
                    var i = 0;
                    var total_gold_weight=0;
                    tr.each(function() {
                        i++;
                        var recovery_name = $(this).find('input[name="recovery_name"]').val();
                        var rproduct_code = $(this).find('input[name="rproduct_code"]').val();
                        var product_code = $(this).find('input[name="product_code"]').val();
                        var total_weight = $(this).find('input[name="total_weight"]').val();
                        var gold_weight = $(this).find('input[name="gold_weight"]').val();
                        var recovery_price = $(this).find('input[name="recovery_price"]').val();
                        var gold_price = $(this).find('input[name="gold_price"]').val();
                        var service_fee = $(this).find('input[name="service_fee"]').val();
                        var purity = $(this).find('input[name="purity"]').val();
                        /*var attrition = $(this).find('input[name="attrition"]').val();*/
                        var cost_price = $(this).find('input[name="cost_price"]').val();
                        var type = $(this).find('select[name="type"]').val();
                        var sn_id = $(this).find('#product_id').val();
                        var material = $(this).find('input[name="material"]').val();
                        var color = $(this).find('input[name="color"]').val();
                        /*change by alam 2018/5/15 start*/
                        if (purity > 1000) {
                            is_true = false;
                            $('.tishi').html("第" + i + "行以旧换新纯度必须小于1000");
                            artdialog_alert("第" + i + "行以旧换新纯度必须小于1000");
                            return false;
                        }
                        purity = parseFloat(purity / 1000).toFixed(6);
                        /*change by alam 2018/5/15 end*/

                        if (empty(recovery_name)) {
                            is_true = false;
                            $('.tishi').html("第" + i + "行以旧换新金料名称");
                            artdialog_alert("第" + i + "行以旧换新金料名称");
                            return false;
                        }

                       /* if (empty(rproduct_code)) {
                            is_true = false;
                            $('.tishi').html("第" + i + "行以旧换新金料编号");
                            artdialog_alert("第" + i + "行以旧换新金料编号");
                            return false;
                        }*/

                        product_list.push({
                            'recovery_name' : recovery_name,
                            'rproduct_code' : rproduct_code,
                            'total_weight' : total_weight,
                            'gold_weight' : gold_weight,
                            'recovery_price' : recovery_price,
                            'gold_price' : gold_price,
                            'service_fee' : service_fee,
                            'purity' : purity,
                            'cost_price' : cost_price,
                            'type' : type,
                            'product_id' : sn_id,
                            'material' : material,
                            'color' : color
                        });
                        total_gold_weight=total_gold_weight+parseFloat(gold_weight);
                    })
                    if (is_true) {
                        var post_data = {
                            'total_recovery_gold_weight' : total_gold_weight,
                            'product_list' : eval(product_list)
                        }
                    } else {
                        var post_data = false;
                    }
                    return post_data;
                }