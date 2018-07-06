        //获取官方通知
        $.getJSON("http://www.thinkcmf.com/service/sms_jsonp.php?lang={$lang_set}&v={$thinkcmf_version}&callback=?",
        function(data) {
            var tpl = '<li><em class="title"></em><span class="content"></span></li>';
            var $thinkcmf_notices = $("#thinkcmf_notices");
            $thinkcmf_notices.empty();
            if (data.length > 0) {
                $.each(data, function(i, n) {
                    var $tpl = $(tpl);
                    $(".title", $tpl).html(n.title);
                    $(".content", $tpl).html(n.content);
                    $thinkcmf_notices.append($tpl);
                });
            } else {
                $thinkcmf_notices.append("<li>^_^,{:L('NO_NOTICE')}~~</li>");
            }

        });
