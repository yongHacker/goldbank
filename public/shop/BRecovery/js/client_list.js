    $('.check_tr').click(function() {
        var checkbox = $(this).find('input[type=radio]');
        if (!checkbox.is(':checked')) {
            checkbox.prop('checked', true);
        }
    });

    function keyLogin(event) {
        if (event.keyCode == 13 || event.whick == 13) //回车键的键值为13
            document.getElementById("from").click(); //调用登录按钮的登录事件
    }
