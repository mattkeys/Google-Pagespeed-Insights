( function( $ ) {

	if ( GPI_Summary.snapshot ) {
		GPI_Summary.summary_stats = JSON.parse( GPI_Summary.summary_stats );
		GPI_Summary.summary_reports = JSON.parse( GPI_Summary.summary_reports );
	}

	var no_results = $.isEmptyObject( GPI_Summary.summary_stats );
	var dataVersion;

	if ( ! no_results ) {
		$( document ).ready( function() {
			dataVersion = GPI_Summary.summary_stats.data_format;

			if ( '1.0' == dataVersion ) {
				$('.wrap .reportmodes').before('<div id="message" class="error"><p><strong>' + GPI_Summary.strings.old_format_notice + '</strong></p></div>');
			}

			var rotate = GPI_Summary.summary_stats.odometer_rotation;
			$('#pagespeed_needle').css({
				'-webkit-transform'	: 'rotate(' + rotate + ')',
				'-moz-transform'	: 'rotate(' + rotate + ')',
				'-ms-transform'		: 'rotate(' + rotate + ')',
				'-o-transform'		: 'rotate(' + rotate + ')',
				'transform'			: 'rotate(' + rotate + ')'
			});

			$('#score_text .score').text( GPI_Summary.summary_stats.score );

			var areas_of_improvement = wp.template( 'areas_of_improvement' );
			$.each( GPI_Summary.summary_reports, function ( index, values ) {
				var data = {
					rule_name	: values.rule_name,
					avg_impact	: values.avg_impact,
					occurances	: values.occurances
				};

				$('#largest_improvement .stats').append( areas_of_improvement( data ) );
			});

			var scores = wp.template( 'scores' );
			$.each( GPI_Summary.summary_stats.page_scores.highest, function ( index, values ) {
				var data = {
					report_url	: values.report_url,
					page_url	: values.page_url,
					score		: values.score
				};

				$('#highest_scores .stats').append( scores( data ) );
			});
			$.each( GPI_Summary.summary_stats.page_scores.lowest, function ( index, values ) {
				var data = {
					report_url	: values.report_url,
					page_url	: values.page_url,
					score		: values.score
				};

				$('#lowest_scores .stats').append( scores( data ) );
			});
		});

		google.charts.load('current', {'packages':['corechart']});
		google.charts.setOnLoadCallback(drawCharts);

		function drawCharts() {

			/***********************************************
						Create resource size bar chart
			************************************************/

			if ( '1.0' != dataVersion ) {
				var sizes = new google.visualization.DataTable();
				sizes.addColumn('string', GPI_Summary.strings.resource_type);
				sizes.addColumn('number', GPI_Summary.strings.high, 'highest');
				sizes.addColumn('number', GPI_Summary.strings.average, 'average');
				sizes.addColumn('number', GPI_Summary.strings.low, 'lowest');

				$.each( GPI_Summary.summary_stats.resource_sizes, function ( index, values ) {
					var data = [ index, Number( values.highest.value ), Number( values.average ), Number( values.lowest.value ) ];
					sizes.addRow( data );
				});

				var sizes_options = {
					legend			: 'none',
					backgroundColor	: 'transparent',
					width			: 615,
					height			: 200,
					tooltip			: { trigger: 'selection' },
					chartArea		: { top: 10, width: '80%', height: '80%' }
				};

				var sizes_chart = new google.visualization.BarChart(document.getElementById('sizes_chart_div'));
				sizes_chart.draw(sizes, sizes_options);

				sizes_chart.setAction({
					id		: 'view_report',
					text	: GPI_Summary.strings.view_page_report,
					action	: function() {
						selection	= sizes_chart.getSelection();
						column_id	= sizes.getColumnId( selection[0].column );

						if ( 'average' == column_id ) {
							return;
						}

						index		= sizes.getValue( selection[0].row, 0 );
						report_url	= GPI_Summary.summary_stats.resource_sizes[ index ][ column_id ].url;

						var win = window.open( report_url, '_self' );
						win.focus();
					},
					visible: function () {
						selection	= sizes_chart.getSelection();

						if ( selection.length < 1 ) {
							return false;
						}

						column_id = sizes.getColumnId( selection[0].column );

						if ( 'average' == column_id ) {
							return false;
						} else {
							return true;
						}
					},
				});
			}
		}
	} else {
		$( document ).ready( function() {
			$('#results').hide();
			$('#no_results').show();
		});
	}

})( jQuery );