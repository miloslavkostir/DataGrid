$('.grid a.grid-ajax:not(.grid-confirm)').live('click', function (event) {
	event.preventDefault();
	$.get(this.href);
});

$('.grid a.grid-confirm:not(.grid-ajax)').live('click', function (event) {
	var answer = confirm($(this).data("grid-confirm"));
	return answer;
});

$('.grid a.grid-confirm.grid-ajax').live('click', function (event) {
	event.preventDefault();
	var answer = confirm($(this).data("grid-confirm"));
	if (answer) {
		$.get(this.href);
	}
});
