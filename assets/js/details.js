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

		$('#pagespeed_lab_data_wrapper .last_checked .rightcol').text( GPI_Details.page_stats.last_modified );

		if ( '1.0' != dataVersion ) {
			var page_statistics = wp.template( 'statistics' );
			$.each( GPI_Details.page_stats.labData, function ( index, stat ) {
				var data = {
					label		: stat.title,
					value		: stat.displayValue,
					description	: stat.description
				};

				$('#pagespeed_lab_data_wrapper .stats tbody').append( page_statistics( data ) );
			});
		}

		if ( '3.0' < dataVersion ) {
			var passedAudits	= 0,
				hasScreenshots	= false;
			$.each( GPI_Details.page_reports, function ( index, audit ) {
				var	type = audit.rule_blocks.details.type,
					mode = audit.rule_blocks.score_display_mode,
					data = {
						key				: audit.rule_key,
						name			: audit.rule_name,
						description		: audit.rule_blocks.description,
						details			: audit.rule_blocks.details,
						displayValue	: audit.rule_blocks.display_value,
						type			: type,
						strings			: GPI_Details.strings,
						publicPath		: GPI_Details.public_path
					};

				var audits = wp.template( 'audits-' + type );

				if ( 'screenshot-thumbnails' == audit.rule_key ) {
					$('#screenshots').append( audits( data ) );
					hasScreenshots = true;
				} else if ( 'opportunity' == type && 0.9 > audit.rule_score ) {
					$('#opportunities').append( audits( data ) );
				} else if ( 'opportunity' != type && 0.9 > audit.rule_score ) {
					$('#diagnostics').append( audits( data ) );
				} else if ( 'informative' == mode || 'not_applicable' == mode ) {
					$('#diagnostics').append( audits( data ) );
				} else {
					$('#passed_audits').append( audits( data ) );
					passedAudits++;
				}

			});

			if ( ! $('#opportunities').children().length ) {
				$('.row.opportunities').hide();
			}

			if ( ! $('#diagnostics').children().length ) {
				$('.row.diagnostics').hide();
			}

			if ( ! $('#passed_audits').children().length ) {
				$('.row.passed-audits').hide();
			}

			$('#passed_audits_count').text( passedAudits );

			if ( ! hasScreenshots ) {
				$('.row.screenshots').hide();
			}

			$('.accordion').accordion({
				'animate'		: 50,
				'heightStyle'	: 'content',
				'collapsible'	: true,
				'active'		: false
			});
		}
	});

	google.charts.load( 'current', {
		packages: ['corechart']
	} );
	if ( 'undefined' !== typeof GPI_Details.page_stats.fieldData.FIRST_CONTENTFUL_PAINT_MS && 'undefined' !== typeof GPI_Details.page_stats.fieldData.FIRST_INPUT_DELAY_MS ) {
		google.charts.setOnLoadCallback( fieldData );
	} else {
		$('#pagespeed_field_data_wrapper .chart_data').html('<p>' + GPI_Details.strings.insufficient_field_data + '</p>');
	}

	function fieldData() {
		drawBarChart( 'FCP', 'FIRST_CONTENTFUL_PAINT_MS' );
		drawBarChart( 'FID', 'FIRST_INPUT_DELAY_MS' );
	}

	function drawBarChart( type, key ) {
		var data = [type];
		$.each( GPI_Details.page_stats.fieldData[ key ].distributions, function( index, array ) {
			var proportion			= array['proportion'] * 100,
				proportion			= +(proportion.toFixed(2)),
				prettyProportion	= proportion + '%',
				min					= array['min'] / 1000,
				max					= typeof array['max'] != 'undefined' ? array['max'] / 1000 : false,
				minmax_label		= false;

			if ( 0 == index ) {
				minmax_label = ' (< ' + max + ' s) ';
			} else if ( 1 == index ) {
				minmax_label = ' (' + min + ' s ~ ' + max + ' s) ';
			} else if ( 2 == index ) {
				minmax_label = ' (> ' + min + ' s) ';
			}

			data.push( array['proportion'] );
			data.push( prettyProportion );
			data.push( prettyProportion + ' ' + GPI_Details.strings.field_data_labels[ index ] + minmax_label + GPI_Details.strings[ type ] + ' ('+ type +')' );
		} );

		var chartdata = google.visualization.arrayToDataTable([
			[ 'category', 'Fast', { role: 'annotation' }, { type: 'string', role: 'tooltip' }, 'Average', { role: 'annotation' }, { type: 'string', role: 'tooltip' }, 'Slow', { role: 'annotation' }, { type: 'string', role: 'tooltip' } ],
			data
		]);


		var view = new google.visualization.DataView( chartdata );
		var options = {
			title			: GPI_Details.strings[ type ] + ' (' + type + ')',
			isStacked		: true,
			height			: 112.5,
			legend			: {position: 'none'},
			colors			: ['#178239','#e67700','#c7221f'],
			backgroundColor	: 'transparent',
			chartArea		: {
				top		: 30,
				left	: 0,
				width	: '100%',
			},
			vAxis			: {
				textPosition	: 'none'
			},
			hAxis			: {
				baselineColor	: 'transparent',
				textPosition	: 'none'
			}
		};
		var chart = new google.visualization.BarChart( document.getElementById( type ) );
		chart.draw( view, options );

		$(window).on('resizeEnd', function() {
			chart.draw( view, options );
		});
	}

	$( window ).resize(function() {
		if ( this.resizeTO ) clearTimeout( this.resizeTO );
		this.resizeTO = setTimeout( function() {
			$(this).trigger('resizeEnd');
		}, 500);
	});

})( jQuery );