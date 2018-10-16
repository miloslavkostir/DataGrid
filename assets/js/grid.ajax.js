/**
 * AJAX JS for DataGrid
 * dependent on jQuery
 * @authors		Jakub Holub, Miloslav Koštíř
 * @copyright	Copyright (c) 2012 Jakub Holub
 * @license     New BSD Licence
 */
var GridAjax = {
	
	init: function(){ 
		
		// Setup
		$.ajaxSetup({
			success: function(data){
				if(data.redirect){
					$.get(data.redirect);
				}
				if(data.snippets){
					for (var snippet in data.snippets){
						$("#"+snippet).html(data.snippets[snippet]);
					}
				}
			}
		});
	
		// AJAX without confirmation
		$('body').on('click', '.grid a.grid-ajax:not(.grid-confirm)', function (event) {
			event.preventDefault();
			$.get(this.href);
		});
		
		// Confirmation without AJAX	
		$('body').on('click', '.grid a.grid-confirm:not(.grid-ajax)', function (event) {
			var answer = confirm($(this).data("grid-confirm"));
			return answer;
		});
		
		// AJAX with confirmation	
		$('body').on('click', '.grid a.grid-confirm.grid-ajax', function (event) {
			event.preventDefault();
			var answer = confirm($(this).data("grid-confirm"));
			if (answer) {
				$.get(this.href);
			} 
		}); 

		// Form AJAX
		$('body').on('click', ".grid-gridForm *[type=submit]", function(){
			$(this).addClass("grid-gridForm-clickedSubmit");
		});
	
		$('body').on("submit", ".grid-gridForm", function(event){
			var button = $(".grid-gridForm-clickedSubmit");
			$(button).removeClass("grid-gridForm-clickedSubmit");
			if($(button).data("select")){
				var selectName = $(button).data("select");
				var option = $("select[name=\""+selectName+"\"] option:selected");
				if($(option).data("grid-confirm")){
					var answer = confirm($(option).data("grid-confirm"));
					if(answer){
						if($(option).hasClass("grid-ajax")){
							event.preventDefault();
							$.post(this.action, $(this).serialize()+"&"+$(button).attr("name")+"="+$(button).val());
						}
					}else{
						return false;
					}
				}else{
					if($(option).hasClass("grid-ajax")){
						event.preventDefault();
						$.post(this.action, $(this).serialize()+"&"+$(button).attr("name")+"="+$(button).val());
					}
				}
				$("td input:checkbox.grid-action-checkbox").attr('checked', false);
				
			}else{
				event.preventDefault();
				$.post(this.action, $(this).serialize()+"&"+$(button).attr("name")+"="+$(button).val());
			}
		});
	
	}
}

$(function(){	
	GridAjax.init();
}); 
