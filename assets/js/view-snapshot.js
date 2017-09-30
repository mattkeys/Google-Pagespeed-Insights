( function( $ ) {

	GPI_View_Snapshot.snapshot.summary_stats = JSON.parse( GPI_View_Snapshot.snapshot.summary_stats );
	GPI_View_Snapshot.snapshot.summary_reports = JSON.parse( GPI_View_Snapshot.snapshot.summary_reports );

	if ( GPI_View_Snapshot.compare ) {
		GPI_View_Snapshot.compare.summary_stats = JSON.parse( GPI_View_Snapshot.compare.summary_stats );
		GPI_View_Snapshot.compare.summary_reports = JSON.parse( GPI_View_Snapshot.compare.summary_reports );
	}


	$( document ).ready( function() {

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
					avg_impact	: values.avg_impact,
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

	google.charts.load('current', {'packages':['corechart']});
	google.charts.setOnLoadCallback(drawCharts);

	function drawCharts() {

		/***********************************************
					Create resource size bar chart
		************************************************/

		$('.sizes_chart_div').each( function() {
			var selector = $(this).data('selector');
			var sizes = new google.visualization.DataTable();
			sizes.addColumn('string', 'Resource Type');
			sizes.addColumn('number', 'High', 'highest');
			sizes.addColumn('number', 'Average', 'average');
			sizes.addColumn('number', 'Low', 'lowest');

			$.each( GPI_View_Snapshot[ selector ].summary_stats.resource_sizes, function ( index, values ) {
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

			var sizes_chart = new google.visualization.BarChart( $(this)[0] );
			sizes_chart.draw(sizes, sizes_options);

			sizes_chart.setAction({
				id		: 'view_report',
				text	: 'View Page Report',
				action	: function() {
					selection	= sizes_chart.getSelection();
					column_id	= sizes.getColumnId( selection[0].column );

					if ( 'average' == column_id ) {
						return;
					}

					index		= sizes.getValue( selection[0].row, 0 );
					report_url	= GPI_View_Snapshot[ selector ].summary_stats.resource_sizes[ index ][ column_id ].url;

					var win = window.open( report_url, '_blank' );
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
		});

	}

})( jQuery );