// <!--收款方式-->
                    localStorage.removeItem('sell_name');
                    localStorage.removeItem('sell_mobile');
                    localStorage.removeItem('sell_uid');
                    //获取收款列表数据
                    function getall_pay_type(){
                        var is_true=true;
                        var datas = [];
                        var tr= $(".ta").find('table tbody tr[class="pay_type_tr"]');
                        console.log(tr.length)
                        var i=1;
                        tr.each(function(){
                            var pay_id =$(this).find(".pay_id").find('select').val();
                            var currency=$(this).find(".currency").find('select').val();
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
                        $("#count").unbind('change').change(function () {
                            change_needprice();
                        })
                        $("input[name='pay_price']").unbind('keyup').keyup(function () {
                            change_countprice();
                            change_needprice();
                        });
                    }
                    //修改还需支付的金额
                    function change_needprice(){
                        var count_price=$("#count_price").text();
                        var count=$("#count").val();
                        var need_price=count-count_price;
                        $("#need_price").text(need_price.toFixed(2));
                    }
                    //修改已经支付的金额和计算金额
                    function change_countprice(){
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

                        var con=0;
                        $("input[name='pay_price']").each(function(key,val){
                            var pay_price=$(this).val();
                            var  actual_price=0;
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
                            }else {
                                $(this).parent().parent().find(".pay_type").text("系统外支付");
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
                            html+='<td class="text-right flow_id"><input type="text" autocomplete="off" name="flow_id" value="" placeholder="流水号"></td>';
                            html+='<td class="text-right pay_price"><input type="text" autocomplete="off" name="pay_price" value="0" placeholder="收款金额"></td>';
                            html+='<td class="text-center unit"></td>';
                            html+='<td class="text-center exchange_rate"></td>';
                            html+='<td class="text-center pay_type"></td>';
                            html+='<td class="text-center actual_price">0.00</td>';
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

                    heightAuto($("#myModal2"));
                    heightAuto($("#myModal3"));
                    localStorage.removeItem('p_id');
                    $("#myModal3").on("shown.bs.modal",function () {
                        $("#goods_index2").contents().find("#mobile").focus();
                    })
                    //销售总价
                    function count_price2(){
                        var con=0;
                        $('tr td .price2').each(function(key,val){
                            if($(this).val()){
                                con = parseFloat(con) + parseFloat($(this).val());
                            }
                        })
                        $('#count').val(con.toFixed(2));
                        $('#total_price').val(con.toFixed(2));
                        change_needprice();
                    }
                    //销售价改变销售总价
                    function count(){
                        $("input[class='price2']").unbind('keyup').keyup(function(){
                            count_price2();
                        })
                    }
                    //优惠价改变销售价和销售总价
                    function discount_price(){
                        $("input[class='discount_price']").unbind('keyup').keyup(function(){
                            var tr=$(this).parent().parent();
                            var gold = parseFloat(tr.find('.gold_price').find('input').val());//金价
                            var  m_fee = parseFloat(tr.find('.goods_gram_price').find('input').val())>0?tr.find('.goods_gram_price').find('input').val():0;//工费
                            var g = tr.find('.goods_weight').text();//重量
                            //计价
                            var sell_price = tr.find('.goods_unit_price').find('input').attr("price");//销售价
                            var jijia =tr.find('.jijia').text();
                            if(jijia =='计件'){
                                if(m_fee==''){
                                    m_fee ='0';
                                }
                                var price2 =sell_price-$(this).val();
                                tr.find('.goods_unit_price').find('input').val(price2.toFixed(2));
                            }else{
                                if(m_fee==''){
                                    m_fee ='0';
                                }
                                var price2 = ((parseFloat(gold)+parseFloat(m_fee)) * (parseFloat(g))-$(this).val()).toFixed(2);
                                tr.find('.goods_unit_price').find('input').val(price2);
                            }
                            count_price2();
                        })
                    }
                    //工费改变销售总价
                    function m_frr(){
                        $("input[class='m_fee']").keyup(function(){
                            var tr=$(this).parent().parent();
                            var gold = parseFloat(tr.find('.gold_price').find('input').val());//金价
                            var  m_fee = parseFloat(tr.find('.goods_gram_price').find('input').val())>0?tr.find('.goods_gram_price').find('input').val():0;//工费
                            var g = tr.find('.goods_weight').text();//重量
                            //计价
                            var sell_price = tr.find('.goods_unit_price').find('input').attr("price");//销售价
                            var jijia =tr.find('.jijia').text();
                            if(jijia =='计件'){
                                if(m_fee==''){
                                    m_fee ='0';
                                }
                            }else{
                                if(m_fee==''){
                                    m_fee ='0';
                                }
                                var price2 = ((parseFloat(gold)+parseFloat(m_fee)) * (parseFloat(g))).toFixed(2);
                                tr.find('.goods_unit_price').find('input').val(price2);
                            }
                            count_price2();
                        })
                    }
                    function sell_g_price(){
                        $("input[class='sell_g_price']").keyup(function(){
                            var tr=$(this).parent().parent();
                            var gold = parseFloat(tr.find('.gold_price').find('input').val());//金价
                            var  m_fee = parseFloat(tr.find('.goods_gram_price').find('input').val())>0?tr.find('.goods_gram_price').find('input').val():0;//工费
                            var g = tr.find('.goods_weight').text();//重量
                            //计价
                            var sell_price = tr.find('.goods_unit_price').find('input').attr("price");//销售价
                            var jijia =tr.find('.jijia').text();
                            if(jijia =='计件'){
                                if(m_fee==''){
                                    m_fee ='0';
                                }
                            }else{
                                if(m_fee==''){
                                    m_fee ='0';
                                }
                                var price2 = ((parseFloat(gold)+parseFloat(m_fee)) * (parseFloat(g))).toFixed(2);
                                tr.find('.goods_unit_price').find('input').val(price2);
                            }
                            count_price2();
                        })
                    }
                    var tr_order=1;
                    table_order();
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
                                var count = $('#count').val();
                                if(price){
                                    if(count !='' && count != NaN){
                                        count2 =(parseFloat(count)-parseFloat(price)).toFixed(2);
                                    }
                                }
                                $('#count').val(count2);
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
                            var tr=html.find("input[class='goods_input']:checked").parent().parent();
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
    
                                    var html="";
                                    html+="<tr id='zz' class='' gold_price='"+$(this).attr('gold_price')+"' gold_type='"+$(this).attr('gold_type')+"'>";
                                    html+='<td class="goods_order text-center" >'+$(this).find(".goods_order").text()+'</td>';
                                    html+=' <td class="goods_code text-center">'+$(this).find(".goods_code").text()+'</td>';
                                    html+=' <td class="goods_name">'+$(this).find(".goods_name").text()+'</td>';
                                    html+=' <td class="goods_purity text-right">'+$(this).find(".goods_purity").text()+'</td>';
                                    html+=' <td class="goods_spec t">'+$(this).find(".goods_spec").text()+'</td>';
                                    console.log($(this).find(".goods_way").text());
                                    if($(this).find(".goods_way").text()=='计件'){
                                        html+=' <td hidden="hidden" class="price">'+$(this).find(".goods_unit_price").text()+'</td>';
                                        html += ' <td class="goods_gram_price"><input style="background-color: #eee;" readonly="readonly" type="text"  name="m_fee" class="m_fee" value=' + $(this).find(".goods_gram_price").text() + '> </td>';
                                        html+=' <td class="gold_price"><input type="text" autocomplete="off" name="sell_g_price" readonly="readonly" class="sell_g_price" value='+($(this).attr('gold_price')>0?$(this).attr('gold_price'):0)+'></td>';
                                    }else{
                                        html+=' <td hidden="hidden">'+$(this).attr('gold_price')+'</td>';
                                        html += ' <td class="goods_gram_price"><input style="background-color: #eee;"  type="text" name="m_fee" class="m_fee" readonly="readonly" value=' + $(this).find(".goods_gram_price").text() + '> </td>';
                                        html+=' <td class="gold_price"><input type="text" autocomplete="off" name="sell_g_price" class="sell_g_price" value='+($(this).attr('gold_price')>0?$(this).attr('gold_price'):0)+'></td>';
                                      }
                                    //    html+=' <td class="goods_gram_price"><input type="text" autocomplete="off" name="m_fee" class="m_fee" value='+$(this).find(".goods_gram_price").text()+'> </td>';

                                    html+=' <td class="goods_weight">'+$(this).find(".goods_weight").text()+' <input  type="hidden" class="g text-right" value='+$(this).find(".goods_weight").text()+' /></td>';
                                    html+=' <td  class="be_onsale_price text-right">'+$(this).find(".goods_unit_price").text()+'</td>';
                                    html+=' <td  class="discount_price"><input type="text" autocomplete="off" name="discount_price" class="discount_price"   value="0"></td>';
                                    html+=' <td  class="goods_unit_price price2"><input type="text" autocomplete="off" name="price2" class="price2" price='+ $(this).find(".goods_unit_price").text()+'  value='+ $(this).find(".goods_unit_price").text()+'></td>';
                                    html+='<td class="jijia text-center">'+$(this).find(".goods_way").text()+'</td>';
                                    html+='<td class="goods_id" hidden ="hidden"><input id ="pid" type="text" value='+$(this).find(".goods_id").text()+'></td>';
                                    //  html+='<td hidden ="hidden">'+$(this).find("td:nth-child(11)").text()+'</td>';
                                    html+='<td class="text-center">'
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
                            table_order();
                            myModal2();
                            del_input();
                            count_price2();
                            count();    m_frr();discount_price(); sell_g_price();   del();
                        })
                        $("#add").unbind('click').click(function(){
                            $("#check").trigger("click");
                        })
                    });

                    $('#add-2').click(function(){
                        name = localStorage.getItem('sell_name');
                        mobile = localStorage.getItem('sell_mobile');
                        uid = localStorage.getItem('sell_uid');
                        if(empty(name)){
                            name='';
                        }
                        $('#mobile').val(name);
                        $('#uid').val(uid);
                    })
                    function myModal2(){
                        $('.myModal2').each(function () {
                            $(this).click(function(){
                                $(".myModal2.on").removeClass("on");
                                $(this).addClass("on");
                                if(!this.hasClass("on")){

                                }
                            })

                        })
                    }
                    function save(){
                        $("#baocun").click(function(){
                            var saccout_record=getall_pay_type();
                            if(saccout_record==false){
                                return;
                            }
                            console.log(saccout_record);
                            var names = [];
                            var tr= $(".ta").find('table tbody tr[id="zz"]');
                            var order=new Object();
                            order.create_time=$('#create_time').val();
                            order.remark=$("#remark").val();
                            order.buyer_id=localStorage.getItem('sell_uid');
                            order.count=$('#count').val();
                            tr.each(function(){
                                var id=$(this).find(".goods_id").find('input').val();
                                if(id){
                                    var id=$(this).find(".goods_id").find('input').val();
                                    var jjfs =$(this).find(".jijia").text();
                                    var sell_g_price=$(this).find(".sell_g_price").val();
                                    var gold_type=$(this).attr('gold_type');
                                    var sell_m_fee =$(this).find(".goods_gram_price").find('input').val();
                                    var discount_price=$(this).find(".discount_price").find('input').val();
                                    var rell_sell_price =$(this).find(".goods_unit_price").find('input').val();
                                    var be_onsale_price =$(this).find(".be_onsale_price").text();
                                    if(id){
                                        names.push({
                                            'id': id,
                                            'be_onsale_price': be_onsale_price,
                                            'discount_price': discount_price,
                                            'sell_price': rell_sell_price,
                                            'sell_m_fee': sell_m_fee,
                                            'sell_pricemode': jjfs,
                                            'sell_g_price': sell_g_price,
                                            gold_type: gold_type
                                        });
                                    }
                                }
                            })
                            console.log(names);
                            if(names.length<1){
                                //  alert("请选择需要销售的货品");
                                $('.tishi').html('请选择需要销售的货品');
                                return false;
                            }
                            names=JSON.stringify(names);
                            var store=$("#mobile").val();
                            if(store<1){
                                $('.tishi').html('会员姓名不能为空');
                                return false;
                            }

                            tr.each(function(){
                                var id=$(this).find(".goods_id").find('input').val();
                                if(id){
                                    if(!$(this).find(".goods_unit_price").find('input').val()){
                                        $('.tishi').html('销售价不能为空');
                                        return false;
                                    }
                                }
                            })
                            var product_count=$("#total_num").text();
                            var shop_id=$("select[name='shop_id']").val();
                            $.ajax({
                                url: API_URL+"&m=BSell&a=add",
                                type: 'post',
                                async: false,
                                data: {store:store,order:order,count:product_count,shop_id:shop_id,order_detail:eval(names),saccout_record:eval(saccout_record)},
                                beforeSend:function(){
                                    $(".baocun").attr("disabled",true);
                                },
                                success: function (data) {
                                    if(data.status==1){
                                        $('.tishi').html('添加成功');
                                        location.href=data.url;
                                    }else if(data.status==0){
                                        $('.tishi').html(data.msg);
                                        $(".baocun").attr("disabled",false);
                                        return false;
                                    }else if(data.status==3){
                                        alert(data.msg);
                                        $(".gold_price").html(data.gold);
                                        $("#zz").attr("gold_price",data.gold);
                                        var tr= $(".ta").find('table tbody tr[id="zz"]');
                                        var count=0;
                                        tr.each(function() {
                                            var jjfs = $(this).find(".jijia").text();
                                            if (jjfs == "计重") {
                                                var sell_m_fee = parseFloat($(this).find(".goods_gram_price").find('input').val());
                                                var weight = parseFloat($(this).find(".goods_weight").find('input').val());
                                                var price = (sell_m_fee + data.gold) * weight;
                                                count += price;
                                                $(this).find(".price2").find('input').val(price);
                                            } else {
                                                var sell_m_fee = parseFloat($(this).find(".goods_gram_price").find('input').val());
                                                count += sell_m_fee;
                                            }
                                        });
                                        $("#count").val(count);
                                        var src="/index.php?g=Kunjinjubao&m=Sells&a=g_index";
                                        $("#goods_index").attr("src",src);
                                        $(".baocun").attr("disabled",false);
                                    }
                                },error: function (data,status) {
                                    $(".baocun").attr("disabled",false);
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
        var default_exchange_rate=$("select[name='shop_id']").find("option:selected").attr("default_rate");
        if(!default_exchange_rate){
            default_exchange_rate=100.00;
        }
        $(".default_rate").text(default_exchange_rate);
        var count_unit=$("select[name='shop_id']").find("option:selected").attr("unit");
        if(!count_unit){
            count_unit="元";
        }
        $(".count_unit").text(count_unit);
    })
    var a = document.getElementById("order_base").offsetWidth;
    $("#order_product").css("width",a);
