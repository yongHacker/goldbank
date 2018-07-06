// <!--选择客户，完善客户信息-->
        heightAuto($("#myModal2"));
        heightAuto($("#myModal3"));

        $("#myModal3").on("shown.bs.modal", function() {
            $("#goods_index2").contents().find("#mobile").focus();
        });

        $('#add-2').click(function() {
            var ckbox = $("#goods_index2").contents().find('.check_box:checked');

            if (ckbox != undefined) {
                var realname = ckbox.attr("realname");
                var user_nicename = ckbox.attr("user_nicename");
                var mobile = ckbox.attr("mobile");
                var uid = ckbox.attr("uid");
                var id_no = ckbox.attr("id_no");
                var employee_id = ckbox.attr("employee_id");

                if (!realname) {
                    realname = "";
                    $('#real_name').removeAttr("readonly");
                } else {
                    $('#real_name').attr("readonly", "readonly");
                }
                if (id_no < 1) {
                    id_no = "";
                    $('#id_no').removeAttr("readonly");
                } else {
                    $('#id_no').attr("readonly", "readonly");
                }
                $('#mobile').val(user_nicename + '(' + mobile + ')');
                $('#uid').val(uid);
                $('#real_name').val(realname);
                $('#id_no').val(id_no);
                $('#employee_id').val(employee_id);
            }
        })
    // <!--添加、删除回购货品-->
        var remove_recovery_product_id = [];
        var product_td = "";

        function myModal2(object) {
            $('#myModal2').modal('show');
            product_td = $(object).parent();
        }

        var product_html = '<td class="search_input" width="25%" style="border: none">'
            + '<input type="text" autocomplete="off" id="product_code" name="product_code" class="form-control" onclick="myModal2(this)">'
            + '<input hidden="hidden" id="product_id" name="product_id"  class="form-control" value="">'
            + '<a href="#myModal2" class="myModal3 leave" data-toggle="modal" name="" role="button"><span><i class="fa fa-search normal"></i></span></a>'
            + '</td>';

        var gold_price = $("input[name='gold_price']").val();
        var recovery_price = $("input[name='recovery_price']").val();
        $("#add_product").unbind("click").click(function() {
            var html = '';
            html += '<tr id="zz" data-type="add">';
            html += '<td class="text-center"></td>';//序
            html += ' <td class="text-center td_recovery_name"><input type="text" autocomplete="off" name="recovery_name" class="recovery_name" value=""></td>';
            /*html += ' <td class="text-center td_rproduct_code"><input type="text" autocomplete="off" name="rproduct_code" class="rproduct_code" value=""></td>';*/
            html += ' <td class="text-center td_total_weight"><input type="number" step="0.001" autocomplete="off" name="total_weight" class="total_weight input_init no_arrow" value="0.00"></td>';//总重
            html += ' <td class="text-center td_purity"><input type="number" step="0.001" autocomplete="off" name="purity no_arrow" class="purity no_arrow" value="0.000"></td>';//纯度
            html += ' <td class="text-center td_gold_weight"><input type="number" step="0.001" autocomplete="off" name="gold_weight" class="gold_weight input_init no_arrow" value="0.00"></td>';//金重
            html += ' <td class="text-center td_recovery_price"><input type="number" step="0.001" autocomplete="off" name="recovery_price" class="recovery_price no_arrow" value="'+recovery_price+'"></td>';//回购金价
            html += ' <td class="text-center td_gold_price"><input type="number" step="0.001" autocomplete="off" name="gold_price" class="gold_price no_arrow" value="'+gold_price+'"></td>';//当前金价
            html += ' <td class="text-center td_service_fee"><input type="number" step="0.001" autocomplete="off" name="service_fee" class="service_fee input_init no_arrow" value="0.00"></td>';//服务克工费
            html += ' <td class="text-center td_attrition"><input type="number" step="0.001" autocomplete="off" name="attrition" class="attrition input_init no_arrow" value="0.00"></td>';//损耗率
            html += ' <td class="text-center td_cost_price"><input type="number" step="0.001" autocomplete="off" name="cost_price" class="cost_price input_init no_arrow" value="0.00"></td>';//成本价
            html += '<td class="text-center">'
            html += '<a href="javascript:void(0);" name="{$v.id}"  class="del" role="button" data-toggle="modal">删除</i></a>';
            html += '</td>';
            html += '</tr>';
            $("#last").before(html);
            table_order();
            change_type();
            change_price();
            change_td();
            del();
        });
        //给删除按钮添加删除事件
        function del() {
            $(".del").unbind("click").click(function() {
                var tr = $(this).parent().parent();
                //获取需要删除的数据
                if (tr.find('input[name="recovery_product_id"]').length>0) {
                    remove_recovery_product_id.push(tr.find('input[name="recovery_product_id"]').val())
                }
                tr.remove();
                table_order();
            });
        }
        //类型绑定change事件，类型更改，改变货品框
        function change_type() {
            $("select[name='type']").unbind("change").change(function() {
                var tr = $(this).parent().parent();
                if ($(this).val() == 1) {
                    tr.find(".td_sn_id").html(product_html);
                } else {
                    tr.find(".td_sn_id").html("--");
                }
            })
        }
        //成本价绑定keyup事件，成本价更改，改变总价
        function change_price() {
            $("input[name='cost_price']").unbind('keyup').keyup(function() {
                count_price();
            })
        }

        //总重，纯度，损耗率 ，回购金价，克工费 改变 则改变金重和成本价
        function change_td() {
            $("input[name='total_weight'],input[name='recovery_price'],input[name='attrition'],input[name='purity'],input[name='service_fee']").unbind('keyup').keyup(function() {
                var tr = $(this).parent().parent();
                count_cost_price(tr);
            })
        }
        //选取货品
        $('#goods_index').load(function() {
            $("#check").unbind('click').click(function() {
                var html = $('#goods_index').contents();
                var product = html.find("input[name='product_id']:checked");
                var product_code = product.attr("pcode");
                var product_id = product.attr("pid");
                product_td.find("#product_code").val(product_code);
                product_td.find("#product_id").val(product_id);
            })
            $("#add").unbind('click').click(function() {
                $("#check").trigger("click");
            })
        });
    // <!--排序统计-->
        init();
        function init() {
            table_order();
            change_type();
            change_price();
            change_td();
            del();
        }
        function table_order() {
            var len = $(".ta").find('table tr').length;
            for (var i = 1; i < len; i++) {
                $(".ta").find('table tr:eq(' + i + ') td:first').text(i);
            }
            count_num();
        }
        //统计货品条数
        function count_num() {
            var num = 0;
            $(".ta").find('table tr').each(function() {
                if ($(this).attr('id') == 'zz') {
                    num++;
                }
            });
            $('#total_num').html(num);
        }
        //统计成本价
        function count_price() {
            var price = 0;
            $('tr td .cost_price').each(function(key, val) {
                if ($(this).val()) {
                    price = parseFloat(price) + parseFloat($(this).val());
                }
            })
            $('#count').val(price.toFixed(2));
        }
        // 计算金重 [change by alam 2018/5/15 this function]
        function count_gold_weight(tr) {
            var total_weight = parseFloat(tr.find('input[name="total_weight"]').val());
            var gold_weight = tr.find('input[name="gold_weight"]');
            var purity = parseFloat(tr.find('input[name="purity"]').val());
            purity = purity / 1000;
            var attrition = tr.find('input[name="attrition"]').val();
            var weight = total_weight * purity * ((1000000 - attrition * 1000000) / 1000000);
            gold_weight.val(weight.toFixed(2));
            return weight.toFixed(2);
        }
        //计算成本价
        function count_cost_price(tr) {
            var cost_price = tr.find('input[name="cost_price"]');
            var gold_weight = count_gold_weight(tr);
            var recovery_price = tr.find('input[name="recovery_price"]').val();
            var service_fee = tr.find('input[name="service_fee"]').val();
            var cost_price = tr.find('input[name="cost_price"]');
            var price = gold_weight * (recovery_price * 10000 - service_fee * 10000) / 10000;
            cost_price.val(price.toFixed(2));
            count_price();
        }
        //点击保存
        save();
        //保存时，检测数据是否正确,并返回数据
        function check_data() {
            is_true = true;
            var mobile = $.trim($("#mobile").val());
            if (empty(mobile)) {
                artdialog_alert("请选择会员");
                return;
            }
            var real_name = $.trim($("#real_name").val());
            if (empty(real_name)) {
                artdialog_alert("请填写真实姓名");
                return;
            }
            var id_no = $.trim($("#id_no").val());
            if (empty(id_no)) {
                artdialog_alert("请填写身份证号");
                return;
            }
            var order = new Object();
            order.remove_recovery_product_id = remove_recovery_product_id ? remove_recovery_product_id.join(",") : '';
            order.recovery_id = $('input[name="recovery_id"]').val();//
            order.recovery_time = $('#create_time').val();//开单时间
            order.num = $('#total_num').text();//开单时间
            order.remark = $.trim($("#remark").val());//备注
            order.buyer_id = $("#uid").val();//客户id
            order.employee_id = $("#employee_id").val();//员工id
            order.name = real_name;//真实姓名
            order.id_no = id_no;//身份证号
            order.shop_id = $("#shop").val();//门店id
            order.price = $('#count').val();//总价
            var product_list = [];
            var tr = $(".ta").find('table tbody tr[id="zz"]');
            if (tr.length < 1) {
                artdialog_alert("请添加回购货品数据");
                return false;
            }
            var i = 0;
            tr.each(function() {
                i++;
                var recovery_name = $(this).find('input[name="recovery_name"]').val();
                var rproduct_code = $(this).find('input[name="rproduct_code"]').val();
                var recovery_product_id = $(this).find('input[name="recovery_product_id"]').val();
                if (recovery_product_id) {
                    //用于货品编号检测的方法，如果是已经添加的则不用判断
                    var is_old = recovery_product_id;
                }
                var total_weight = $(this).find('input[name="total_weight"]').val();
                var gold_weight = $(this).find('input[name="gold_weight"]').val();
                var recovery_price = $(this).find('input[name="recovery_price"]').val();
                var gold_price = $(this).find('input[name="gold_price"]').val();
                var service_fee = $(this).find('input[name="service_fee"]').val();
                var purity = $(this).find('input[name="purity"]').val();
                var attrition = $(this).find('input[name="attrition"]').val();
                var cost_price = $(this).find('input[name="cost_price"]').val();
                var type = $(this).find('select[name="type"]').val();
                var sn_id = $(this).find('#product_id').val();
                /*change by alam 2018/5/15 start*/
                if (purity > 1000) {
                    is_true = false;
                    artdialog_alert("纯度必须小于1000");
                    return false;
                }
                purity = parseFloat(purity / 1000).toFixed(6);
                /*change by alam 2018/5/15 end*/
                if (attrition > 1) {
                    is_true = false;
                    artdialog_alert("损耗率必须小于1");
                    return false;
                }
                if (empty(sn_id)) {
                    sn_id = ""
                }
                //截金回购为选择货品关联

                if (type == 1 && empty(sn_id)) {
                    is_true = false;
                    artdialog_alert("第" + i + "行请选择货品关联");
                    return false;
                }

                if (empty(recovery_name)) {
                    is_true = false;
                    artdialog_alert("第" + i + "行金料名称");
                    return false;
                }

                /*if (empty(rproduct_code)) {
                    is_true = false;
                    artdialog_alert("第" + i + "行金料编号");
                    return false;
                }*/

                product_list.push({
                    'recovery_product_id' : recovery_product_id,
                    'is_old' : is_old,
                    'recovery_name' : recovery_name,
                    'rproduct_code' : rproduct_code,
                    'total_weight' : total_weight,
                    'gold_weight' : gold_weight,
                    'recovery_price' : recovery_price,
                    'gold_price' : gold_price,
                    'service_fee' : service_fee,
                    'purity' : purity,
                    'attrition' : attrition,
                    'cost_price' : cost_price,
                    'type' : type,
                    'product_id' : sn_id
                });
            })
            if (is_true) {
                var post_data = {
                    'order' : order,
                    'product_list' : eval(product_list)
                };
            } else {
                var post_data = false;
            }
            return post_data;
        }
        function save() {
            $("#baocun,#submit").click(function() {
                var post_data = check_data();//检测数据是否正确,并返回数据
                var url = API_URL+"&m=BRecovery&a=edit";
                if (post_data) {
                    var status = $(this).data("type");
                    post_data['order']['status'] = status;
                    ajax_post(url, post_data);
                }
            })
        }
        function ajax_post(url, post_data) {
            $.ajax({
                url : url,
                type : 'post',
                async : false,
                data : post_data,
                beforeSend : function() {
                    $(this).attr("disabled", true);
                },
                success : function(data) {
                    if (data.status == 1) {
                        $('.tishi').html('添加成功');
                        location.href = data.url;
                    } else if (data.status == 0) {
                        $('.tishi').html(data.msg);
                        $(this).attr("disabled", false);
                        return false;
                    }
                },
                error : function(data, status) {
                    $(this).attr("disabled", false);
                }
            })
        }
    // <!--改变门店则改变客户列表-->
        var client_list_src = $("#goods_index2").attr("src");
        $('#shop').change(function() {
            var src = client_list_src + "&shop_id=" + $("#shop").val();
            $("#goods_index2").attr("src", src);
        })
    // <!-- 会员操作弹窗联动js -->
        function loadFrame(obj) {
            var url = obj.contentWindow.location.href;
            if (url.indexOf(API_URL+"&m=BRecovery&a=client_list") != -1) {
                $('#clientModalLabel').text('选择会员');
            } else if (url.indexOf(API_URL+"&m=BRecovery&a=add_client") != -1) {
                $('#clientModalLabel').text('添加会员');
            }
        }
