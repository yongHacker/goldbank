/**
 * Created by Administrator on 2018/4/20.
 */
/*var icons = {"icon_list":[{"icon":"article"},{"icon":"access"},{"icon":"access-manage"},{"icon":"good"},
        {"icon":"merchant-manage"}, {"icon":"client-manage"},{"icon": "msg"}, {"icon":"log"},
        {"icon":"account"}, {"icon":"menu"}, {"icon":"admin"}, {"icon":"setting"},{"icon":"inventory"},
        {"icon":"trend"},{"icon":"store"},{"icon":"home"},{"icon":"merchant"},{"icon":"merchant-to-check"},
    {"icon":"checked"},{"icon":"accredit"},{"icon":"accredit-to-check"},{"icon":"effective-merchant"}]};
$("#icon").tmpl(icons).appendTo('.cont');
var size=icons.icon_list.length;
$(".svg-num").html(size);*/
var icons = {icon:["article","access","access-manage","good","merchant-manage", "client-manage", "msg", "log",
        "account", "menu", "admin", "setting","inventory","trend","store","home","merchant","merchant-to-check",
        "checked","accredit","accredit-to-check","effective-merchant","depot","buy-back","finance","purchase","retail"]};
var size=icons.icon.length;
$(".svg-num").html(size);
$("#icon").tmpl(icons).appendTo('.cont');