// Add width and height values to each chart
jQuery(document).ready(function() {
	jQuery('#chart-area').attr({
		width: chart_options.width,
		height: chart_options.height
	});
});

// Set our doughnut chart
var doughnutData = chart_options.doughtnut_data;
window.onload = function() {
	var ctx = document.getElementById("chart-area").getContext("2d");
	window.myDoughnut = new Chart(ctx).Doughnut(doughnutData, {
		responsive: true
	});
};

// Create a legend to display along the charts, using the same data.
jQuery(document).ready(function() {
	jQuery.each(doughnutData, function(key, val) {
		var $li = jQuery( "<li>" + val.label + ": " + val.value + "</li>" );
		jQuery("#ghactivity_admin_report").append($li);
	});
});
