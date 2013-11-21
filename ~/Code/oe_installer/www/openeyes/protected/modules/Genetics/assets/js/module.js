
/* Module-specific javascript can be placed here */

$(document).ready(function() {
	$('#search_button').click(function(e) {
		e.preventDefault();
		window.location.href = baseUrl+'/Genetics/search/index?gene-id='+$('#gene-id').val()+'&disorder-id='+$('#savedDiagnosis').val();
	});

	$('tr.clickable').click(function(e) {
		e.preventDefault();
		window.location.href = $(this).data('uri');
	});
	
	Genetics_patient_hovers();
});

function Genetics_load_pedigrees()
{
	$.ajax({
		type: 'GET',
		url: baseUrl+'/Genetics/default/pedigrees',
		success: function(html) {
			$('#pedigree_data').html(html);
		}
	});
}

function Genetics_patient_hovers()
{
	var offsetY = 28;
	var offsetX = 10;
	var tipWidth = 0;

	$('tr.hover').hover(function(e) {
		var titleText = $(this).data('hover');

		var tooltip = $('<div class="tooltip alerts"></div>').appendTo('body');

		$(this).data({
			'tipText': titleText,
			'tooltip': tooltip
		}).removeAttr('hover');

		tooltip.text(' ' + titleText);

		tipWidth = tooltip.outerWidth();
		tooltip.css('top', (e.pageY - offsetY) + 'px').css('left', (e.pageX - (tipWidth + offsetX)) + 'px').fadeIn('fast');
	},function(e){
		$(this).data('hover',$(this).data('tipText'));
		$(this).data('tooltip').remove();
	}).mousemove(function(e) {
		$(this).data('tooltip')
			.css('top', (e.pageY - offsetY) + 'px')
			.css('left', (e.pageX - (tipWidth + offsetX)) + 'px');
	});
}
