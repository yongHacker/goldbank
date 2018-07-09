var ROOT_URL="http://"+location.host;
var API_URL=ROOT_URL+"/index.php?g=Business";
;(function () {
    var ss=document.referrer;
    var referrer=getCookie("referrer");
    //console.log(referrer);
    if(referrer==""||referrer==null||typeof(referrer)!="undefined"){
        setCookie("referrer",ss);
    }
    else if(ss!=window.location.href){
        setCookie("referrer",ss);
    }
    //全局返回处理
    $(".js-ajax-back-btn").on('click', function(e) {
        jump_refer();
    });
    //全局ajax处理
    $.ajaxSetup({
        complete: function (jqXHR) {},
        data: {
        },
        error: function (jqXHR, textStatus, errorThrown) {
            //请求失败处理
        }
    });

    if ($.browser && $.browser.msie) {
        //ie 都不缓存
        $.ajaxSetup({
            cache: false
        });
    }

    //不支持placeholder浏览器下对placeholder进行处理
    if (document.createElement('input').placeholder !== '') {
        $('[placeholder]').focus(function () {
            var input = $(this);
            if (input.val() == input.attr('placeholder')) {
                input.val('');
                input.removeClass('placeholder');
            }
        }).blur(function () {
            var input = $(this);
            if (input.val() == '' || input.val() == input.attr('placeholder')) {
                input.addClass('placeholder');
                input.val(input.attr('placeholder'));
            }
        }).blur().parents('form').submit(function () {
            $(this).find('[placeholder]').each(function () {
                var input = $(this);
                if (input.val() == input.attr('placeholder')) {
                    input.val('');
                }
            });
        });
    }

    // 所有加了dialog类名的a链接，自动弹出它的href
    if ($('a.js-dialog').length) {
        Wind.use('artDialog', 'iframeTools', function() {
            $('.js-dialog').on('click', function(e) {
                e.preventDefault();
                var $this = $(this);
                art.dialog.open($(this).prop('href'), {
                    close : function() {
                        $this.focus(); // 关闭时让触发弹窗的元素获取焦点
                        return true;
                    },
                    title : $this.prop('title')
                });
            }).attr('role', 'button');

        });
    }

    // 所有的ajax form提交,由于大多业务逻辑都是一样的，故统一处理
    var ajaxForm_list = $('form.js-ajax-form');
    if (ajaxForm_list.length) {
        Wind.use('ajaxForm', 'artDialog','validate', function () {

            var $btn;

            $('button.js-ajax-submit').on('click', function (e) {
                var btn = $(this),form = btn.parents('form.js-ajax-form');
                $btn=btn;

                if(btn.data("loading")){
                    return;
                }

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
                                cancelVal: '关闭',
                                cancel: function () {
                                    //btn.data('subcheck', false);
                                    //btn.click();
                                },
                                ok: function () {
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
            });

            ajaxForm_list.each(function(){
                $(this).validate({
                    //是否在获取焦点时验证
                    //onfocusout : false,
                    //是否在敲击键盘时验证
                    onkeyup : function( element, event ) {
                        return;

                        // Avoid revalidate the field when pressing one of the following keys
                        // Shift       => 16
                        // Ctrl        => 17
                        // Alt         => 18
                        // Caps lock   => 20
                        // End         => 35
                        // Home        => 36
                        // Left arrow  => 37
                        // Up arrow    => 38
                        // Right arrow => 39
                        // Down arrow  => 40
                        // Insert      => 45
                        // Num lock    => 144
                        // AltGr key   => 225
                        var excludedKeys = [
                            16, 17, 18, 20, 35, 36, 37,
                            38, 39, 40, 45, 144, 225
                        ];

                        if ( event.which === 9 && this.elementValue( element ) === "" || $.inArray( event.keyCode, excludedKeys ) !== -1 ) {
                            return;
                        } else if ( element.name in this.submitted || element.name in this.invalid ) {
                            this.element( element );
                        }
                    },
                    //当鼠标掉级时验证
                    onclick : false,
                    //给未通过验证的元素加效果,闪烁等
                    //highlight : false,
                    showErrors:function(errorMap, errorArr){
                        try {
                            $(errorArr[0].element).focus();
                            //alert(errorArr[0].message);
                        } catch (err) {
                        }
                    },
                    submitHandler:function(form){
                        var $form=$(form);
                        $form.ajaxSubmit({
                            url: $btn.data('action') ? $btn.data('action') : $form.attr('action'), //按钮上是否自定义提交地址(多按钮情况)
                            dataType: 'json',
                            beforeSubmit: function (arr, $form, options) {

                                $btn.data("loading",true);
                                var text = $btn.text();

                                //按钮文案、状态修改
                                $btn.text(text + '中...').prop('disabled', true).addClass('disabled');
                            },
                            success: function (data, statusText, xhr, $form) {
                                var text = $btn.text();
                                //按钮文案、状态修改
                                $btn.removeClass('disabled').prop('disabled', false).text(text.replace('中...', '')).parent().find('span').remove();
                                if (data.state === 'success') {
                                    var referer_time=data.referer_time*1000;
                                    if(!referer_time){
                                        referer_time=1000;
                                    }
                                    $('<span class="tips_success">' + data.info + '</span>').appendTo($btn.parent()).fadeIn('slow').delay(referer_time).fadeOut(function () {
                                        if (data.referer) {
                                            //返回带跳转地址
                                            window.location.href = data.referer;
                                        } else {
                                            if (data.state === 'success') {
                                                //刷新当前页
                                                reloadPage(window);
                                            }
                                        }
                                    });
                                } else if (data.state === 'fail') {
                                    var $verify_img=$form.find(".verify_img");
                                    if($verify_img.length){
                                        $verify_img.attr("src",$verify_img.attr("src")+"&refresh="+Math.random());
                                    }

                                    var $verify_input=$form.find("[name='verify']");
                                    $verify_input.val("");

                                    $('<span class="tips_error">' + data.info + '</span>').appendTo($btn.parent()).fadeIn('fast');
                                    $btn.removeProp('disabled').removeClass('disabled');
                                    if (data.referer) {
                                        //返回带跳转地址
                                        window.location.href = data.referer;
                                    }
                                }



                            },
                            error:function(xhr,e,statusText){
                                alert(statusText);
                                //刷新当前页
                                reloadPage(window);
                            },
                            complete: function(){
                                $btn.data("loading",false);
                            }
                        });
                    }
                });
            });

        });
    }

    //dialog弹窗内的关闭方法
    $('#js-dialog-close').on('click', function (e) {
        e.preventDefault();
        try{
            art.dialog.close();
        }catch(err){
            Wind.use('artDialog','iframeTools',function(){
                art.dialog.close();
            });
        };
    });

    //所有的删除操作，删除数据后刷新页面
    if ($('a.js-ajax-delete').length) {
        Wind.use('artDialog', function () {
            $('.js-ajax-delete').on('click', function (e) {
                e.preventDefault();
                var $_this = this,
                    $this = $($_this),
                    href = $this.data('href'),
                    msg = $this.data('msg');
                href = href?href:$this.attr('href');
                art.dialog({
                    title: false,
                    icon: 'question',
                    content:msg?msg: '确定要删除吗？',
                    follow: $_this,
                    close: function () {
                        $_this.focus();; //关闭时让触发弹窗的元素获取焦点
                        return true;
                    },
                    okVal:"确定",
                    ok: function () {

                        $.getJSON(href).done(function (data) {
                            if (data.state === 'success') {
                                if (data.referer) {
                                    location.href = data.referer;
                                } else {
                                    reloadPage(window);
                                }
                            } else if (data.state === 'fail') {
                                //art.dialog.alert(data.info);
                                //alert(data.info);//暂时处理方案
                                art.dialog({
                                    content: data.info,
                                    icon: 'warning',
                                    ok: function () {
                                        this.title(data.info);
                                        return true;
                                    }
                                });
                            }
                        });
                    },
                    cancelVal: '关闭',
                    cancel: true
                });
            });

        });
    }


    if ($('a.js-ajax-dialog-btn').length) {
        Wind.use('artDialog', function () {
            $('.js-ajax-dialog-btn').on('click', function (e) {
                e.preventDefault();
                var $_this = this,
                    $this = $($_this),
                    href = $this.data('href'),
                    msg = $this.data('msg');
                href = href?href:$this.attr('href');
                if(!msg){
                    msg="您确定要进行此操作吗？";
                }
                art.dialog({
                    title: false,
                    icon: 'question',
                    content: msg,
                    follow: $_this,
                    close: function () {
                        $_this.focus();; //关闭时让触发弹窗的元素获取焦点
                        return true;
                    },
                    ok: function () {

                        $.getJSON(href).done(function (data) {
                            if (data.state === 'success') {
                                if (data.referer) {
                                    location.href = data.referer;
                                } else {
                                    art.dialog({
                                        content: data.info,
                                        icon: 'succeed',
                                        ok: function () {
                                            reloadPage(window);
                                            return true;
                                        },

                                    });
                                }
                            } else if (data.state === 'fail') {
                                //art.dialog.alert(data.info);
                                art.dialog({
                                    content: data.info,
                                    icon: 'warning',
                                    ok: function () {
                                        this.title(data.info);
                                        return true;
                                    }
                                });
                            }
                        });
                    },
                    cancelVal: '关闭',
                    cancel: true
                });
            });

        });
    }

    /*复选框全选(支持多个，纵横双控全选)。
     *实例：版块编辑-权限相关（双控），验证机制-验证策略（单控）
     *说明：
     *	"js-check"的"data-xid"对应其左侧"js-check-all"的"data-checklist"；
     *	"js-check"的"data-yid"对应其上方"js-check-all"的"data-checklist"；
     *	全选框的"data-direction"代表其控制的全选方向(x或y)；
     *	"js-check-wrap"同一块全选操作区域的父标签class，多个调用考虑
     */

    if ($('.js-check-wrap').length) {
        var total_check_all = $('input.js-check-all');

        //遍历所有全选框
        $.each(total_check_all, function () {
            var check_all = $(this),
                check_items;

            //分组各纵横项
            var check_all_direction = check_all.data('direction');
            check_items = $('input.js-check[data-' + check_all_direction + 'id="' + check_all.data('checklist') + '"]');

            //点击全选框
            check_all.change(function (e) {
                var check_wrap = check_all.parents('.js-check-wrap'); //当前操作区域所有复选框的父标签（重用考虑）

                if ($(this).attr('checked')) {
                    //全选状态
                    check_items.attr('checked', true);

                    //所有项都被选中
                    if (check_wrap.find('input.js-check').length === check_wrap.find('input.js-check:checked').length) {
                        check_wrap.find(total_check_all).attr('checked', true);
                    }

                } else {
                    //非全选状态
                    check_items.removeAttr('checked');

                    check_wrap.find(total_check_all).removeAttr('checked');

                    //另一方向的全选框取消全选状态
                    var direction_invert = check_all_direction === 'x' ? 'y' : 'x';
                    check_wrap.find($('input.js-check-all[data-direction="' + direction_invert + '"]')).removeAttr('checked');
                }

            });

            //点击非全选时判断是否全部勾选
            check_items.change(function () {

                if ($(this).attr('checked')) {

                    if (check_items.filter(':checked').length === check_items.length) {
                        //已选择和未选择的复选框数相等
                        check_all.attr('checked', true);
                    }

                } else {
                    check_all.removeAttr('checked');
                }

            });


        });

    }

    //日期选择器
    var dateInput = $("input.js-date")
    if (dateInput.length) {
        Wind.use('datePicker', function () {
            dateInput.datePicker();
        });
    }

    //日期+时间选择器
    var dateTimeInput = $("input.js-datetime");
    if (dateTimeInput.length) {
        Wind.use('datePicker', function () {
            dateTimeInput.datePicker({
                time: true
            });
        });
    }

    var yearInput = $("input.js-year");
    if (yearInput.length) {
        Wind.use('datePicker', function () {
            yearInput.datePicker({
                startView: 'decade',
                minView: 'decade',
                format: 'yyyy',
                autoclose: true
            });
        });
    }

    //tab
    var tabs_nav = $('ul.js-tabs-nav');
    if (tabs_nav.length) {
        Wind.use('tabs', function () {
            tabs_nav.tabs('.js-tabs-content > div');
        });
    }
})();
function getQueryString(e) {
    var t = new RegExp("(^|&)" + e + "=([^&]*)(&|$)");
    var a = window.location.search.substr(1).match(t);
    if (a != null) return a[2];
    return ""
}
//重新刷新页面，使用location.reload()有可能导致重新提交
function reloadPage(win) {
    var location = win.location;
    location.href = location.pathname + location.search;
}

/**
 * 页面跳转
 * @param url
 */
function redirect(url) {
    location.href = url;

}

/**
 * 读取cookie
 * @param name
 * @returns
 */
function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1, c.length);
        }
        if (c.indexOf(nameEQ) == 0) {
            return c.substring(nameEQ.length, c.length);
        }
    }


    return null;
}

// 设置cookie
function setCookie(name, value, days) {
    var argc = setCookie.arguments.length;
    var argv = setCookie.arguments;
    var secure = (argc > 5) ? argv[5] : false;
    var expire = new Date();
    if(days==null || days==0) days=1;
    expire.setTime(expire.getTime() + 3600000*24*days);
    document.cookie = name + "=" + escape(value) + ("; path=/") + ((secure == true) ? "; secure" : "") + ";expires="+expire.toGMTString();
}

/**
 * 打开iframe式的窗口对话框
 * @param url
 * @param title
 * @param options
 */
function open_iframe_dialog(url, title, options) {
    var params = {
        title : title,
        lock : true,
        opacity : 0,
        width : "95%",
        height:'90%'
    };
    params = options ? $.extend(params, options) : params;
    Wind.use('artDialog', 'iframeTools', function() {
        art.dialog.open(url, params);
    });
}

/**
 * 打开地图对话框
 *
 * @param url
 * @param title
 * @param options
 * @param callback
 */
function open_map_dialog(url, title, options, callback) {

    var params = {
        title : title,
        lock : true,
        opacity : 0,
        width : "95%",
        height : 400,
        ok : function() {
            if (callback) {
                var d = this.iframe.contentWindow;
                var lng = $("#lng_input", d.document).val();
                var lat = $("#lat_input", d.document).val();
                var address = {};
                address.address = $("#address_input", d.document).val();
                address.province = $("#province_input", d.document).val();
                address.city = $("#city_input", d.document).val();
                address.district = $("#district_input", d.document).val();
                callback.apply(this, [ lng, lat, address ]);
            }
        }
    };
    params = options ? $.extend(params, options) : params;
    Wind.use('artDialog', 'iframeTools', function() {
        art.dialog.open(url, params);
    });
}

/**
 * 打开文件上传对话框
 * @param dialog_title 对话框标题
 * @param callback 回调方法，参数有（当前dialog对象，选择的文件数组，你设置的extra_params）
 * @param extra_params 额外参数，object
 * @param multi 是否可以多选
 * @param filetype 文件类型，image,video,audio,file
 * @param app  应用名，对于 CMF 的应用名
 */
function open_upload_dialog(dialog_title,callback,extra_params,multi,filetype,app){
    multi = multi?1:0;
    filetype = filetype?filetype:'image';
    app = app?app:GV.APP;
    var params = '&multi='+multi+'&filetype='+filetype+'&app='+app ;
    Wind.use("artDialog","iframeTools",function(){
        art.dialog.open(GV.ROOT+'index.php?g=business&m=asset&a=plupload'  + params, {
            title: dialog_title,
            id: new Date().getTime(),
            width: '650px',
            height: '420px',
            lock: true,
            fixed: true,
            background:"#CCCCCC",
            opacity:0,
            ok: function() {
                if (typeof callback =='function') {
                    var iframewindow = this.iframe.contentWindow;
                    var files=iframewindow.get_selected_files();
                    if(files){
                        callback.apply(this, [this, files,extra_params]);
                    }else{
                        return false;
                    }

                }
            },
            cancel: true
        });
    });
}

function upload_one(dialog_title,input_selector,filetype,extra_params,app){
    open_upload_dialog(dialog_title,function(dialog,files){
        $(input_selector).val(files[0].filepath);
    },extra_params,0,filetype,app);
}

function upload_one_image(dialog_title,input_selector,extra_params,app){
    open_upload_dialog(dialog_title,function(dialog,files){
        $(input_selector).val(files[0].filepath);
        $(input_selector+'-preview').attr('src',files[0].preview_url);
        $(input_selector+'-name').val(files[0].name);
        $(input_selector+'-cancel').css("display","inline-block");
    },extra_params,0,'image',app);
}

/**
 * 多图上传
 * @param dialog_title 上传对话框标题
 * @param container_selector 图片容器
 * @param item_tpl_wrapper_id 单个图片html模板容器id
 */
function upload_multi_image(dialog_title,container_selector,item_tpl_wrapper_id,extra_params,app){
    open_upload_dialog(dialog_title,function(dialog,files){
        var tpl=$('#'+item_tpl_wrapper_id).html();
        var html='';
        $.each(files,function(i,item){
            var itemtpl= tpl;
            itemtpl=itemtpl.replace(/\{id\}/g,item.id);
            itemtpl=itemtpl.replace(/\{url\}/g,item.url);
            itemtpl=itemtpl.replace(/\{preview_url\}/g,item.preview_url);
            itemtpl=itemtpl.replace(/\{filepath\}/g,item.filepath);
            itemtpl=itemtpl.replace(/\{name\}/g,item.name);
            html+=itemtpl;
        });
        $(container_selector).append(html);

    },extra_params,1,'image',app);
}

/**
 * 查看图片对话框
 * @param img 图片地址
 */
function image_preview_dialog(img) {
    Wind.use("artDialog",function(){
        art.dialog({
            title: '图片查看',
            fixed: true,
            width:"420px",
            height: '420px',
            id:"image_preview_"+img,
            lock: true,
            background:"#CCCCCC",
            opacity:0,
            content: '<img src="' + img + '" />'
        });
    });
}
/**
 *
 * @param msg
 * @param icon succeed  error warning
 */
function artdialog_alert(msg,icon){
    if(!icon){
        icon='error';
    }
    Wind.use("artDialog", function() {
        art.dialog({
            id : new Date().getTime(),
            icon : icon,
            fixed : true,
            lock : true,
            background : "#CCCCCC",
            opacity : 0,
            content : msg,
            ok : function() {
                return true;
            }
        });
    });

}

function open_iframe_layer(url,title,options){

    var params = {
        type: 2,
        title: title,
        shadeClose: true,
        skin: 'layui-layer-nobg',
        shade: [0.5, '#000000'],
        area: ['90%', '90%'],
        content:url
    };
    params = options ? $.extend(params, options) : params;

    Wind.css('layer');

    Wind.use("layer", function() {
        layer.open(params);
    });

}

function jump_refer(){
    self.location=$.cookie("referrer");
}
function empty(str){
    if(str!=null&&str!='null'&&str!=''&&typeof(str)!='undefined'){
        return false;
    }
    return true;
}
function error_appear(str){
    var tips=$('.tips_error');
    if(!tips.length){
        $('.form-actions').append('<span class="tips_error" style="color:red;">'+str+'</span>');
    }else{
        tips.html(str);
    }
}
//返回字符串中大小写字母和数字的总数
function getLength(str){
    if(/[a-zA-Z0-9\.\:]/ig.test(str)){
        return str.match(/[a-zA-Z0-9\.\:]/ig).length;
    }
    return 0;
}
//返回字符串中汉字的个数
function getcLength(str){
    if(/[\u4E00-\u9FA5]/g.test(str)){
        return str.match(/[\u4E00-\u9FA5]/g).length;
    }
    return 0;
}
//统一将中文括号转换称英文括号
function changebrackets(str){
    str=str.replace('（','(');
    str=str.replace('）',')');
    return str;
}
/*var reg = /[\(]/g,reg2 = /[\)]/g;
 var str = "(ddd)";
 str = str.replace(reg,"（").replace(reg2,"）");
 console.log(str)*/
//返回字符串中括号的总数
function getbLength(str){
    if(/[\(\)]/g.test(str)){
        return str.match(/[\(\)]/g).length;
    }
    return 0;
}
//模态框的高度自适应   -2017.3.10  wuhy
function heightAuto(Modal) {
    var H=Modal.outerHeight();
    Modal.find(".modal-dialog").css("height",H);
    Modal.find(".modal-content").css("height",H);
    Modal.find(".modal-body").css({"height":H-80,"max-height":H-80});
    Modal.find(".modal-body iframe").css("height",H-90);
}

//商品分类下拉框数据获取 --2017.3.21
function productVariety(data,$_post){
    console.log(data);
    console.log($_post);
    var json=data;
    json = eval(json);
    if (json) {
        var str='';
        $.each(json, function (i, obj) {
            if(obj.id==$_post) {
                str += '<option value="' + obj.id + '" checked>' + obj.html + "└─ " + obj.zclm_name + '</option>';
            }
            else{
                str += '<option value="' + obj.id + '">' + obj.html + "└─ " + obj.zclm_name + '</option>';
            }
        });
        $('#product_variety').html(str);

    }

}

//验证手机号码 type 1-大陆 2-香港 3-澳门
function is_mobile(str, type) {
    var res = false;
    if (type == 1) {
        var reg = /^1[234578]\d{9}$/;
        if (reg.test(str)) {
            res = true;
        }
    } else if (type == 2) {
        var reg = /^[569]\d{7}$/;
        if (reg.test(str)) {
            res = true;
        }
    } else if (type == 3) {
        var reg = /^[6]\d{7}$/;
        if (reg.test(str)) {
            res = true;
        }
    }
    return res;
}
function getLocalDate(nS) {
    nS=nS+60*60*8;
    var newDate = new Date();
    newDate.setTime(nS * 1000);
    var res=newDate.toJSON();
    res=res.split('T');
    return res[0];
}
function getUnixTime(ns){
    var time=Date.parse(new Date(ns));
    time=time/1000;
    time=time-60*60*8;
    return time;
}
/**
 * 导出excel
 * @param form_class 查询数据的form表单class
 * @param export_csv 导出数据的按钮的class
 */
function export_excel(export_class,form_class) {
    if(!export_class)export_class="export";
    if(!form_class)form_class="search_row";
    console.log(export_class + "&" + form_class);
    var param = $("."+form_class).serialize();
    var url = $("."+export_class).attr("url");
    console.log(url + "&" + param);
    //artdialog_alert("正在导出文件，请耐心等待");
    location.href = url + "&" + param;
}
//针对firefox的刷新后input还记录值的情况
$("input[type='text']").attr("autocomplete","off");
//获取大写的字母(商品规格使用，编码形成格式)
function GetLetters(i){
    var letters=["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W",
        "X","Y","Z","0","1","2","3","4","5","6","7","8","9"];
    return letters[i];
}
//关于开单页面input输入框输入的问题
function  taInputClear(){
    $(document).on("focus",".input_init",function(){
        var old_v=$(this).val();
        if(old_v == "0.00"){
            $(this).val("");

        }
    });
    $(document).on("blur",".input_init",function(){
        var old_v=$(this).val();
        if(old_v == ""){
            $(this).val("0.00");

        }

    })
}
taInputClear();


