<script type="text/html" id="tmpl-audits-table">
	<h3>{{data.name}} <span class="toggle"></span><span class="right">{{data.displayValue}}</span></h3>
	<div class="details">
		<p class="description">
			{{{data.description}}}
		</p>
		<table class="audits">
			<thead>
				<tr>
					<# var keys = []; #>
					<# for (i = 0; i < data.details.headings.length; i++ ) { #>
						<# if ( typeof data.details.headings[ i ].text != 'undefined' ) { #>
							<th class="{{data.details.headings[ i ].key}}">{{data.details.headings[ i ].text}}</th>
							<# keys.push( data.details.headings[ i ].key ); #>
						<# } #>
					<# } #>
				</tr>
			</thead>
			<tbody>
				<# for (i = 0; i < data.details.items.length; i++ ) { #>
					<tr>
						<# for (x = 0; x < keys.length; x++ ) {
							if ( 'object' == typeof data.details.items[ i ][keys[ x ]] ) {
								if ( 'url' == keys[ x ] ) {
									#>
									<td class="{{keys[ x ]}}">{{{data.details.items[ i ][keys[ x ]].value}}}</td>
									<#
								} else {
									#>
									<td class="{{keys[ x ]}}">{{data.details.items[ i ][keys[ x ]].value}}</td>
									<#
								}
							} else {
								if ( 'url' == keys[ x ] ) {
									#>
									<td class="{{keys[ x ]}}">{{{data.details.items[ i ][keys[ x ]]}}}</td>
									<#
								} else {
									#>
									<td class="{{keys[ x ]}}">{{data.details.items[ i ][keys[ x ]]}}</td>
									<#
								}
							}
						} #>
					</tr>
				<# } #>
			</tbody>
		</table>
	</div>
</script>