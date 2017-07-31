( function( $ ) {
	var dataVersion;

	$( document ).ready( function() {
		dataVersion = GPI_Details.page_stats.data_format;

		if ( '1.0' == dataVersion ) {
			$('.wrap .reportmodes').before('<div id="message" class="error"><p><strong>' + GPI_Details.strings.old_format_notice + '</strong></p></div>');
		}

		$('#url').text( GPI_Details.page_stats.URL );
		$('#view_url').attr( 'href', GPI_Details.page_stats.URL );
		$('#recheck_url').attr( 'href', GPI_Details.recheck_url );

		var rotate = GPI_Details.page_stats.odometer_rotation;
		$('#pagespeed_needle').css({
			'-webkit-transform'	: 'rotate(' + rotate + ')',
			'-moz-transform'	: 'rotate(' + rotate + ')',
			'-ms-transform'		: 'rotate(' + rotate + ')',
			'-o-transform'		: 'rotate(' + rotate + ')',
			'transform'			: 'rotate(' + rotate + ')'
		});

		$('#score_text .score').text( GPI_Details.page_stats.score );

		$('#pagespeed_stats_wrapper .last_checked .rightcol').text( GPI_Details.page_stats.last_modified );

		if ( '1.0' != dataVersion ) {
			var page_statistics = wp.template( 'statistics' );
			$.each( GPI_Details.page_stats.resources, function ( index, value ) {
				if ( 'resource_sizes' == index ) {
					return;
				}
				var data = {
					label : GPI_Details.strings[ index ],
					value : value
				};

				$('#pagespeed_stats_wrapper .stats').append( page_statistics( data ) );
			});
		}

		var legend = wp.template( 'legend' );
		var color_array = [ '#3366cc', '#dc3912', '#ff9900', '#109618', '#990099', '#0099c6', '#dd4477', '#66aa00', '#b82e2e', '#316395', '#994499', '#22aa99', '#aaaa11', '#6633cc', '#e67300', '#8b0707', '#651067', '#329262', '#5574a6', '#3b3eac', '#b77322', '#16d620', '#b91383', '#f4359e', '#9c5935', '#a9c413', '#2a778d', '#668d1c', '#bea413', '#0c5922', '#743411' ];
		$.each( GPI_Details.page_reports, function ( index, values ) {
			var data = {
				rule_key	: values.rule_key,
				rule_name	: values.rule_name,
				color		: color_array[ index ],
				index		: index
			};

			$('#impact_chart_legend tbody').append( legend( data ) );
		});

	});

	google.charts.load('current', {'packages':['corechart']});
	google.charts.setOnLoadCallback(drawCharts);

	function drawCharts() {

		/***********************************************
					Create resource size bar chart
		************************************************/

		if ( GPI_Details.page_stats.resources != null && '1.0' != dataVersion ) {
			var sizes = new google.visualization.DataTable();
			sizes.addColumn('string', 'Resource Type');
			sizes.addColumn('number', 'kB');

			$.each( GPI_Details.page_stats.resources.resource_sizes, function ( index, value ) {
				var data = [ index, Number( value ) ];
				sizes.addRow( data );
			});

			var sizes_options = {
				'legend'			: 'none',
				'backgroundColor'	: 'transparent',
				'chartArea'			: { top: 10, width: '75%', height: '80%' }
			};

			var sizes_chart = new google.visualization.BarChart(document.getElementById('sizes_chart_div'));
			sizes_chart.draw(sizes, sizes_options);
		}

		/***********************************************
					Create impact pie chart
		************************************************/

		var impact = new google.visualization.DataTable();
		impact.addColumn( 'string', 'Rule' );
		impact.addColumn( 'number', 'Impact' );
		$.each( GPI_Details.page_reports, function ( index, values ) {
			var data = [ values.rule_name, Number( values.rule_impact ) ];
			impact.addRow( data );
		});

		var impact_options = {
			'width'				: 463,
			'height'			: 320,
			'chartArea'			: { top: 15, width: '85%', height: '91%' },
			'legend'			: 'none',
			'tooltip'			: { trigger: 'none' },
			'backgroundColor'	:'transparent',
			'colors'			: [ '#3366cc', '#dc3912', '#ff9900', '#109618', '#990099', '#0099c6', '#dd4477', '#66aa00', '#b82e2e', '#316395', '#994499', '#22aa99', '#aaaa11', '#6633cc', '#e67300', '#8b0707', '#651067', '#329262', '#5574a6', '#3b3eac', '#b77322', '#16d620', '#b91383', '#f4359e', '#9c5935', '#a9c413', '#2a778d', '#668d1c', '#bea413', '#0c5922', '#743411' ],
			'pieSliceTextStyle'	: { color: 'white', fontSize: 14 }
		};

		var impact_chart = new google.visualization.PieChart( document.getElementById('impact_chart_div') );
		impact_chart.draw( impact, impact_options );

		if ( '1.0' != dataVersion ) {
			google.visualization.events.addListener( impact_chart, 'select', impactSelectHandler );
			google.visualization.events.addListener( impact_chart, 'onmouseover', highlightHover );
			google.visualization.events.addListener( impact_chart, 'onmouseout', clearHover );
		}

		function impactSelectHandler() {
			var selected = impact_chart.getSelection();

			if ( 'undefined' != typeof selected[0] ) {
				var ruleindex	= selected[0].row;
				var rule_object	= GPI_Details.page_reports[ ruleindex ];
					rule_object.rule_blocks.impact = rule_object.rule_impact;
					rule_object.rule_blocks.score_impact_string = GPI_Details.strings.score_impact;

				var rule_blocks = wp.template( 'rule_blocks' );
				var block_html	= rule_blocks( rule_object.rule_blocks );

				$('.impact_chart_right tr').removeClass('active');
				$('#optimize_images').hide();
				$('.impact_chart_right tr a[data-pieslice=' + ruleindex + ']').closest('tr').addClass('active');
				if ( "OptimizeImages" == $('.impact_chart_right tr a[data-pieslice=' + ruleindex + ']').data('rulekey') ) {
					$('#optimize_images').show();
				}

				$('#impact_rule_report').css('display', 'block');
				$('#impact_rule_report').html( block_html );
				clearHover();
			} else {
				$('#impact_rule_report').css( 'display', 'none' );
				$('#impact_rule_report').html('');
				$('.impact_chart_right tr').removeClass('active');
				$('#optimize_images').hide();
			}
		}

		function highlightHover( e ) {
			var current = $('.impact_chart_right tr a[data-pieslice=' + e.row + ']');

			$('.impact_chart_right tr').removeClass('hover');

			if ( ! current.closest('tr').hasClass('active') ) {
				current.closest('tr').addClass('hover');
			}
		}

		function clearHover() {
			$('.impact_chart_right tr').removeClass('hover');
		}

		$('.legend-item').on( 'click', function() {
			var tr = $( this ).closest('tr');

			if ( tr.hasClass('active') ) {
				impact_chart.setSelection( false );
				$('#optimize_images').hide();
			} else {
				impact_chart.setSelection( [ { row: $( this ).data('pieslice') } ] );
				$('#optimize_images').hide();
				if ( "OptimizeImages" == $(this).data('rulekey') ) {
					$('#optimize_images').show();
				}
			}
			
			impactSelectHandler();
		});
	}

	$(document).on({
		mouseenter: function () {
			$( this ).stop().animate({
				textIndent: '-' + ( $( this ).width() - $( this ).parent().width() ) + 'px'
			}, 1000);
		},
		mouseleave: function () {
			$( this ).stop().animate({
				textIndent: '0'
			}, 1000);		}
	}, '.impact_chart_right a');

})( jQuery );