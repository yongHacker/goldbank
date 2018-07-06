    function checknode_all(obj) {
        var all_menuid = $("input[name='all_menuid']");
        var chk = $("#menus-table").find("input[type='checkbox']");
        if (all_menuid.prop("checked")) {
            chk.prop("checked", true);
        } else {
            chk.prop("checked", false);
        }
    }
    function checknode(obj) {
        var chk = $("input[type='checkbox']");
        var count = chk.length;
        var num = chk.index(obj);
        var level_top = level_bottom = chk.eq(num).attr('level');
        for (var i = num; i >= 0; i--) {
            var le = chk.eq(i).attr('level');
            if (le <level_top) {
                chk.eq(i).prop("checked", true);
                var level_top = level_top - 1;
            }
        }
        for (var j = num + 1; j < count; j++) {
            var le = chk.eq(j).attr('level');
            if (chk.eq(num).prop("checked")) {
                if (le > level_bottom){
                    chk.eq(j).prop("checked", true);
                }
                else if (le == level_bottom){
                    break;
                }
            } else {
                if (le >level_bottom){
                    chk.eq(j).prop("checked", false);
                }else if(le == level_bottom){
                    break;
                }
            }
        }
    }
    $('button.js-ajax-submit').bind('click', function (e) {
        e.preventDefault();
        var btn = $(this),
                form = btn.parents('form.js-ajax-form');
        //批量操作 判断选项
        if (btn.data('subcheck')) {
            btn.parent().find('span').remove();
            if (form.find('input.js-check:checked').length) {
                var msg = btn.data('msg');
                if (msg) {
                    art.dialog({
                        id: 'warning',
                        icon: 'warning',
                        content: btn.data('msg'),
                        cancelVal: "{:L('CLOSE')}",
                        cancel: function () {
                            btn.data('subcheck', false);
                            btn.click();
                        }
                    });
                } else {
                    btn.data('subcheck', false);
                    btn.click();
                }

            } else {
                $('<span class="tips_error">请至少选择一项</span>').appendTo(btn.parent()).fadeIn('fast');
            }
            return false;
        }

        //ie处理placeholder提交问题
        if ($.browser && $.browser.msie) {
            form.find('[placeholder]').each(function () {
                var input = $(this);
                if (input.val() == input.attr('placeholder')) {
                    input.val('');
                }
            });
        }

        form.ajaxSubmit({
            url: btn.data('action') ? btn.data('action') : form.attr('action'),
            //按钮上是否自定义提交地址(多按钮情况)
            dataType: 'json',
            beforeSubmit: function (arr, $form, options) {
                var text = btn.text();

                //按钮文案、状态修改
                btn.text(text + '中...').attr('disabled', true).addClass('disabled');
            },
            success: function (data, statusText, xhr, $form) {
                var text = btn.text();

                //按钮文案、状态修改
                btn.removeClass('disabled').text(text.replace('中...', '')).parent().find('span').remove();

                if (data.state === 'success') {
                    $('<span class="tips_success">' + data.info + '</span>').appendTo(btn.parent()).fadeIn('slow').delay(1000).fadeOut(function () {
                        if (data.referer) {
                            //返回带跳转地址
                            if (window.parent.art) {
                                //iframe弹出页
                                window.parent.location.href = data.referer;
                            } else {
                                window.location.href = data.referer;
                            }
                        } else {
                            if (window.parent.art) {
                                reloadPage(window.parent);
                            } else {
                                //刷新当前页
                                reloadPage(window);
                            }
                        }
                    });
                } else if (data.state === 'fail') {
                    $('<span class="tips_error">' + data.info + '</span>').appendTo(btn.parent()).fadeIn('fast');
                    btn.removeProp('disabled').removeClass('disabled');
                }
            }
        });
    });
