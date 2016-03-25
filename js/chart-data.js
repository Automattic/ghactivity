// Add width and height values to each chart
jQuery(document).ready(function() {
	jQuery('#chart-area').attr({
		width: chart_options.width,
		height: chart_options.height
	});
});

// Set our doughnut chart
var doughnutID = 'chart-area-' + chart_options.doughnut_id;
var doughnutData = chart_options.doughtnut_data;
window.onload = function() {
	var ctx = document.getElementById(doughnutID).getContext("2d");
	window.myDoughnut = new Chart(ctx).Doughnut(doughnutData, {
		responsive: true
	});
};

// Create a legend to display along the charts, using the same data.
jQuery(document).ready(function() {
	jQuery.each(doughnutData, function(key, val) {
		var $li = jQuery( '<li>' + val.label + ': ' + val.value + '<span class="chart-color" style="background-color:' + val.color + ';"></span></li>' );
		jQuery("#ghactivity_admin_report").append($li);
	});
});
