( function( $ ) {

	GPI_View_Snapshot.snapshot.summary_stats = JSON.parse( GPI_View_Snapshot.snapshot.summary_stats );
	GPI_View_Snapshot.snapshot.summary_reports = JSON.parse( GPI_View_Snapshot.snapshot.summary_reports );

	if ( GPI_View_Snapshot.compare ) {
		GPI_View_Snapshot.compare.summary_stats = JSON.parse( GPI_View_Snapshot.compare.summary_stats );
		GPI_View_Snapshot.compare.summary_reports = JSON.parse( GPI_View_Snapshot.compare.summary_reports );
	}

	$( document ).ready( function() {
		if ( GPI_View_Snapshot.comments.snapshot ) {
			$('.comment[data-selector="snapshot"]').html( '<strong>' + GPI_View_Snapshot.strings.comment + ':</strong>' + GPI_View_Snapshot.comments.snapshot );
		}
		if ( GPI_View_Snapshot.comments.compare ) {
			$('.comment[data-selector="compare"]').html( '<strong>' + GPI_View_Snapshot.strings.comment + ':</strong>' + GPI_View_Snapshot.comments.compare );
		}

		$('.pagespeed_needle').each( function( index ) {
			var selector = $(this).data('selector');
			var rotate = GPI_View_Snapshot[ selector ].summary_stats.odometer_rotation;

			$(this).css({
				'-webkit-transform'	: 'rotate(' + rotate + ')',
				'-moz-transform'	: 'rotate(' + rotate + ')',
				'-ms-transform'		: 'rotate(' + rotate + ')',
				'-o-transform'		: 'rotate(' + rotate + ')',
				'transform'			: 'rotate(' + rotate + ')'
			});
		});

		$('.score_text .score').each( function( index ) {
			var selector = $(this).data('selector');
			
			$(this).text( GPI_View_Snapshot[ selector ].summary_stats.score );
		});

		$('.largest_improvement .stats').each( function( index ) {
			var selector = $(this).data('selector');
			var areas_of_improvement = wp.template( 'areas_of_improvement' );
			var container = this;

			$.each( GPI_View_Snapshot[ selector ].summary_reports, function ( index, values ) {
				var data = {
					rule_name	: values.rule_name,
					avg_score	: Math.round( values.avg_score ),
					occurances	: values.occurances
				};

				$(container).append( areas_of_improvement( data ) );
			});
		});

		var scores = wp.template( 'scores' );
		$('.highest_scores .stats').each( function( index ) {
			var selector = $(this).data('selector');
			var container = this;

			$.each( GPI_View_Snapshot[ selector ].summary_stats.page_scores.highest, function ( index, values ) {
				var data = {
					report_url	: values.report_url,
					page_url	: values.page_url,
					score		: values.score
				};

				$(container).append( scores( data ) );
			});
		});
		$('.lowest_scores .stats').each( function( index ) {
			var selector = $(this).data('selector');
			var container = this;

			$.each( GPI_View_Snapshot[ selector ].summary_stats.page_scores.lowest, function ( index, values ) {
				var data = {
					report_url	: values.report_url,
					page_url	: values.page_url,
					score		: values.score
				};

				$(container).append( scores( data ) );
			});
		});

	});

	google.charts.load( 'current', {
		packages: ['corechart']
	} );
	google.charts.setOnLoadCallback( avgLabData );

	function avgLabData() {
		$('.avg_lab_data').each( function() {
			var selector = $( this ).data('selector'),
				data = new google.visualization.DataTable();

			data.addColumn({
				type	: 'string',
				label	: 'Category'
			});
			data.addColumn({
				type	: 'number',
				label	: GPI_View_Snapshot.strings.average_score
			});
			data.addColumn({
				type	: 'string',
				role	: 'annotation'
			})

			$.each( GPI_View_Snapshot[ selector ].summary_stats.labData, function( index, array ) {
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
				height			: '213',
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
			var chart = new google.visualization.BarChart( $(this)[0] );
			chart.draw( view, options );

			chart.setAction({
				id		: 'view_highest',
				text	: GPI_View_Snapshot.strings.best_performing,
				action	: function() {
					selection	= chart.getSelection();
					index		= view.getValue( selection[0].row, 0 );

					report_url	= GPI_View_Snapshot[ selector ].summary_stats.labData[ index ].highest.url;

					var win = window.open( report_url, '_self' );
					win.focus();
				}
			});
			chart.setAction({
				id		: 'view_lowest',
				text	: GPI_View_Snapshot.strings.worst_performing,
				action	: function() {
					selection	= chart.getSelection();
					index		= view.getValue( selection[0].row, 0 );

					report_url	= GPI_View_Snapshot[ selector ].summary_stats.labData[ index ].lowest.url;

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
		});
	}

	$( window ).resize(function() {
		if ( this.resizeTO ) clearTimeout( this.resizeTO );
		this.resizeTO = setTimeout( function() {
			$(this).trigger('gpiResizeEnd');
		}, 500);
	});

})( jQuery );