( function( $ ) {

	// if ( GPI_Summary.snapshot ) {
	// 	GPI_Summary.summary_stats = JSON.parse( GPI_Summary.summary_stats );
	// 	GPI_Summary.summary_reports = JSON.parse( GPI_Summary.summary_reports );
	// }

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
					avg_score	: Math.round( values.avg_score ),
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
	} else {
		$( document ).ready( function() {
			$('#results').hide();
			$('#no_results').show();
		});
	}

	google.charts.load( 'current', {
		packages: ['corechart']
	} );
	google.charts.setOnLoadCallback( avgLabData );

	function avgLabData() {
		var data = new google.visualization.DataTable();

		data.addColumn({
			type	: 'string',
			label	: 'Category'
		});
		data.addColumn({
			type	: 'number',
			label	: GPI_Summary.strings.average_score
		});
		data.addColumn({
			type	: 'string',
			role	: 'annotation'
		})

		$.each( GPI_Summary.summary_stats.labData, function( index, array ) {
			var score		= array['average'] * 100,
				score		= +(score.toFixed(2)),
				prettyScore	= score + '%',
				row = [ index, { v: array['average'], f: prettyScore }, prettyScore ];

			data.addRow( row );
		} );

		var view = new google.visualization.DataView( data );

		view.setColumns( [0, 1, 2, {
			calc	: function ( dt, row ) {
				if ( ( dt.getValue( row, 1 ) >= 0 ) && ( dt.getValue( row, 1 ) <= 0.49 ) ) {
					return '#c7221f';
				} else if ( ( dt.getValue( row, 1 ) > 0.49 ) && ( dt.getValue( row, 1 ) <= 0.90 ) ) {
					return '#e67700';
				} else {
					return '#178239';
				}
			},
			type	: 'string',
			role	: 'style'
		}]);

		var options = {
			legend			: { position: 'none' },
			colors			: ['#178239','#e67700','#c7221f'],
			backgroundColor	: 'white',
			height			: '223',
			tooltip			: { trigger: 'selection' },
			annotations		: { alwaysOutside: false },
			chartArea		: {
				left	: 150,
				top		: 0,
				width	: '100%',
				height	: '100%'
			},
			vAxis			: {
				textPosition: 'out',
				textStyle	: {
					fontSize	: 11
				}
			},
			hAxis			: {
				format			: 'percent',
				minValue		: 0,
				maxValue		: 1,
				ticks			: [0, .1, .2, .3, .4, .5, .6, .7, .8, .9, 1],
				textPosition	: 'none'
			}
		};
		var chart = new google.visualization.BarChart( document.getElementById('avg_lab_data') );
		chart.draw( view, options );

		chart.setAction({
			id		: 'view_highest',
			text	: GPI_Summary.strings.best_performing,
			action	: function() {
				selection	= chart.getSelection();
				index		= view.getValue( selection[0].row, 0 );

				report_url	= GPI_Summary.summary_stats.labData[ index ].highest.url;

				var win = window.open( report_url, '_self' );
				win.focus();
			}
		});
		chart.setAction({
			id		: 'view_lowest',
			text	: GPI_Summary.strings.worst_performing,
			action	: function() {
				selection	= chart.getSelection();
				index		= view.getValue( selection[0].row, 0 );

				report_url	= GPI_Summary.summary_stats.labData[ index ].lowest.url;

				var win = window.open( report_url, '_self' );
				win.focus();
			}
		});

		google.visualization.events.addListener( chart, 'select', annotationSelectHandler );

		lastSelection = false;
		function annotationSelectHandler() {
			if ( typeof chart.getSelection()[0] != 'undefined' ) {
				var selectedItem = chart.getSelection()[0];

				if ( 2 == selectedItem.column ) {
					if ( lastSelection && lastSelection.row == selectedItem.row ) {
						chart.setSelection([]);
						lastSelection = false;
					} else {
						chart.setSelection([{row:selectedItem.row, column:1}]);
						lastSelection = selectedItem;
					}
				} else {
					lastSelection = selectedItem;
				}
			} else {
				lastSelection = false;
			}
		}

		$(window).on('gpiResizeEnd', function() {
			chart.draw( view, options );
		});
	}

	$( window ).resize(function() {
		if ( this.resizeTO ) clearTimeout( this.resizeTO );
		this.resizeTO = setTimeout( function() {
			$(this).trigger('gpiResizeEnd');
		}, 500);
	});

})( jQuery );