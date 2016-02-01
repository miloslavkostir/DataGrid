/**
 * JS for multiselect filter in DataGrid
 * dependent on jQuery
 * @authors		Miloslav Koštíř
 * @copyright	Copyright (c) 2016 Miloslav Koštíř
 * @license     New BSD Licence
 */

// TODO this file can be class

function createSelected(select, labelSelected)
{
	var text = "";
	var pocet = 0;
	select.children(':not(:first):selected').each(function(){
		text += $(this).text() + '\n';
		pocet++;
	});
		
	if (pocet > 0) {
		select.children(':first').text( labelSelected + ' ' + pocet ).attr('title', text);
	}
}


function init(){

    // config
    var color = '#aad9f2',
		heightOpen = '150px',
		heightClose = '30px',
		label = 'CTRL + click',
		title = 'For more items use CTRL',
		labelSelected = 'Selected';
    
    var multiple = $('th.grid-filter-form select[multiple], th.grid-filter-form select[multiple=multiple]');
	var option = $('th.grid-filter-form select[multiple] option, th.grid-filter-form select[multiple=multiple] option')
	var maxHeight = parseInt($('table.grid tbody').css('height'));
	
	multiple.parent().css({'height': heightClose, 'position':'relative'});
	multiple.css({'height': heightClose, 'position': 'absolute', 'top': 0});
	option.css({'cursor': 'copy'});		
		
	multiple.scrollTop(0);	
	
	
	
	multiple.each(function(){
		var width = $(this).parent().parent().css('width');
		var stop;		
	
		
			
		$(this).on('mouseover', function(){
			stop = true;
			
			$(this).css({'height': heightOpen, 'max-height':(maxHeight + 28)+'px', 'width': 'auto', 'min-width': width, 'z-index': 1000});
		});
		
		
		$(this).on('mouseout', function(){
			stop = false;
			
		
			$(this).css({'height': '28px', 'width': width, 'z-index': 0});
			
			var s = $(this);
			setTimeout(function(){
				if (!stop) {    
					s.scrollTop(0);
				}
			}, 100);
		});
	
		$(this).css({'width': width});
		$(this).children(':first').css({'height' : '25px'}).text(label).attr('title', title);				
		$(this).children(':first').on('click', function(){
			$(this).text(label)
				.attr('title', title)
				.removeAttr('selected');
		});		
		
		$(this).children(':not(:first)').each(function(){
			$(this).css('overflow-wrap', 'normal').attr('title', $(this).text());
		});
		
		createSelected($(this), labelSelected);
	});
	
	option.on('mouseover', function(){
		$(this).css('background-color', color);
	});
	option.on('mouseout', function(){
		$(this).css('background-color', 'white');
	});
	
	
	
	multiple.on('change', function(){
		createSelected($(this), labelSelected);
	});  

}

$(function(){
	init(); 
	
	$(this).ajaxStop(function(){
		init();
	});	    

});
