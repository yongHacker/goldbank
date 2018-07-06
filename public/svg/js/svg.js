var SVG=function(){
    $("svg.svg").each(function (){
        // alert();
        var id=$(this).attr("data-id");
        var fill=$(this).attr("data-fill");
        $(this).html('<use xlink:href="/public/svg/img/all.svg#'+id+'"></use>');
    })
};
SVG();