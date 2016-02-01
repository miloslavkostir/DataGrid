/**
 * Base JS for DataGrid
 * dependent on jQuery, jQuery UI - Datepicker and Autocomplete
 * @authors		Jakub Holub, Miloslav Koštíř
 * @copyright	Copyright (c) 2012 Jakub Holub
 * @license     New BSD Licence
 */
 
function init(){
	
	// Close flash messages
	$(".grid-flash-hide").on("click", function(){
		$(this).parent().parent().fadeOut(300);
	});


	// Select all checkboxes
	function updateCheckboxes(selectAll,checkboxes){
		var checked = true;
		checkboxes.each(function(key, value){
			if($(this).is(":not(:checked)")){
				checked = false;	
			}
		});
		
		selectAll.prop("checked", checked);
	}
	
	$(".grid-select-all").each(function(key, item){
		var selectAll = $(this);
		var checkboxes =  selectAll.parents("thead").siblings("tbody").children("tr:not(tr.grid-subgrid-row)").find("td input:checkbox.grid-action-checkbox");
		
		selectAll.on("click", function(){
			if(selectAll.is(":checked")){
				$(checkboxes).prop("checked", true);
			}else{
				$(checkboxes).prop("checked", false);
			}
		});
		
		updateCheckboxes(selectAll, checkboxes);
		
		checkboxes.each(function(index, value){
			$(this).on("click", function(){
				updateCheckboxes(selectAll, checkboxes);  
			});	
		});
	});
	
	
	// Autocomplete
	$(".grid-autocomplete").on('keydown.autocomplete', function(){
		var gridName = $(this).data("gridname");
		var column = $(this).data("column");
		var link = $(this).data("link");
		$(this).autocomplete({
			source: function(request, response) {
				$.ajax({
					url: link,
					data: gridName+'-term='+request.term+'&'+gridName+'-column='+column,
					dataType: "json",
					method: "post",
					success: function(data) {
						response(data.payload);
					}
				});
			},
			delay: 100,
			open: function() { $('.ui-menu').width($(this).width()) }
		});
	});


	// PerPage control
	$(".grid-changeperpage").on("change", function(){
		$(this).siblings(".grid-perpagesubmit").click();
	});

	function hidePerPageSubmit()
	{
		$(".grid-perpagesubmit").hide();
	}
	hidePerPageSubmit();


	// DatePicker
	function setDatepicker()
	{
		if ( ! $.datepicker ) return;

		$.datepicker.regional['cs'] = {
			closeText: 'Zavřít',
			prevText: '&#x3c;Dříve',
			nextText: 'Později&#x3e;',
			currentText: 'Nyní',
			monthNames: ['leden','únor','březen','duben','květen','červen',
				'červenec','srpen','září','říjen','listopad','prosinec'],
			monthNamesShort: ['led','úno','bře','dub','kvě','čer',
				'čvc','srp','zář','říj','lis','pro'],
			dayNames: ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'],
			dayNamesShort: ['ne', 'po', 'út', 'st', 'čt', 'pá', 'so'],
			dayNamesMin: ['ne','po','út','st','čt','pá','so'],
			weekHeader: 'Týd',
			dateFormat: 'yy-mm-dd',
			constrainInput: false,
			firstDay: 1,
			isRTL: false,
			showMonthAfterYear: false,
			yearSuffix: ''};
		$.datepicker.setDefaults($.datepicker.regional['cs']);

		$(".grid-datepicker").each(function(){
			if(($(this).val() != "")){
				var date = $.datepicker.formatDate('yy-mm-dd', new Date($(this).val()));
			}
			$(this).datepicker();
			$(this).datepicker({ constrainInput: false});
		});
	}
	setDatepicker();


	// Editable controlling (ENTER + doubleclick)
	$("input.grid-editable").on("keypress", function(e) {
		if (e.keyCode == '13') {
			e.preventDefault();
			$("input[type=submit].grid-editable").click();
		}
	});

	$("table.grid tbody tr:not(.grid-subgrid-row) td.grid-data-cell").on("dblclick", function(e) {
		$(this).parent().find("a.grid-editable:first").click();
	});
}   
 

$(function(){
	init(); 
	
	$(this).ajaxStop(function(){
		init();
	});	    

});
