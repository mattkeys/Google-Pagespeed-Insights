<script type="text/html" id="tmpl-rule_blocks">
	<h3>{{data.score_impact_string}}: {{data.impact}}</h3>

	<# for (i = 0; i < data.length; i++ ) { #>
		<h3>{{{data[ i ].header}}}</h3>
		<ul>
			<# for (x = 0; x < data[ i ].urls.length; x++ ) { #>
				<li>{{{data[ i ].urls[x]}}}</li>
			<# } #>
		</ul>
	<# } #>
</script>