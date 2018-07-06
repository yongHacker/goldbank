// debugger;
            var return_id = $("input[name='return_info_id']").val();

            var payments = $("input[name='payment']").val();

            var _default_exchange_rate = $("input[name='currency0_exchange_rate']").val();
            //初始化其它费用操作附加函数
            expence_postfix_function = ['reflush_table'];
            //初始化退款方式
            init_payment();
            //初始化删除退款方式
            del_pay_type();
            //添加一行退款方式
            add_pay_type();
            //币种选取
            change_exchange_rate();
            // 初始化退款明细
            stat_finance();

            if (return_id != '') {
                // 排序
                table_order();
                // 初始化iframe的url
                update_iframe_url();
                // 刷新iframe中的勾选
                refresh_detail_ids();
                // 还原旧的退款明细 - 退款方式
                rollback_pay_type();
            }
