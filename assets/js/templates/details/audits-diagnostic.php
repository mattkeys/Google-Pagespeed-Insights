<script type="text/html" id="tmpl-audits-diagnostic">
	<h3>{{data.name}} <span class="toggle"></span><span class="right">{{data.displayValue}}</span></h3>
	<div class="details">
		<p class="description">
			{{{data.description}}}
		</p>
		<# for (i = 0; i < data.details.items.length; i++ ) { #>
			<table class="audits">
				<tbody>
					<# for ( var key in data.details.items[ i ] ) {
						#>
						<tr>
							<td>{{key}}</td>
							<td>{{data.details.items[ i ][ key ]}}</td>
						</tr>
						<#
					} #>
				</tbody>
			</table>
		<# } #>
	</div>
</script>