	$(".open").click(function (e) {
		e.preventDefault();
		if ($(this).hasClass("fa-toggle-off")) {
			$(this).removeClass("fa-toggle-off");
			$(this).addClass("fa-toggle-on");
			$(this).parent("td").find("input.val").val("1");
		}
		else {
			$(this).removeClass("fa-toggle-on");
			$(this).addClass("fa-toggle-off");
			$(this).parent("td").find("input.val").val("0")
		}
	});
