<script type="text/html" id="tmpl-audits-opportunity">
	<h3>{{data.name}} <span class="toggle"></span><span class="right">{{data.displayValue}}</span></h3>
	<div class="details">
		<p class="description">
			{{{data.description}}}
		</p>
		<# if ( 'uses-webp-images' == data.key || 'uses-optimized-images' == data.key ) { #>
			<div class="shortpixel_box" id="optimize_images">
				<img class="shortpixel_robot" src="{{data.publicPath}}/assets/images/shortpixel.png" alt="{{data.strings.shortpixel.title}}" />
				<h2>{{data.strings.shortpixel.title}}</h2>
				<p>{{{data.strings.shortpixel.description}}}</p>
				<p>{{{data.strings.shortpixel.signup_desc}}}</p>
				<a class="shortpixel_btn" href="https://shortpixel.com/h/af/PCFTWNN142247" target="_blank">{{data.strings.shortpixel.signup_btn}}</a>
			</div>
		<# } #>
		<table class="audits">
			<thead>
				<tr>
					<# var keys = []; #>
					<# for (i = 0; i < data.details.headings.length; i++ ) { #>
						<# if ( typeof data.details.headings[ i ].label != 'undefined' ) { #>
							<th class="{{data.details.headings[ i ].key}}">{{data.details.headings[ i ].label}}</th>
							<# keys.push( data.details.headings[ i ].key ); #>
						<# } #>
					<# } #>
				</tr>
			</thead>
			<tbody>
				<# for (i = 0; i < data.details.items.length; i++ ) { #>
					<tr>
						<# for (x = 0; x < keys.length; x++ ) {
							if ( 'url' == keys[ x ] ) {
								#>
								<td class="{{keys[ x ]}}">{{{data.details.items[ i ][keys[ x ]]}}}</td>
								<#
							} else {
								#>
								<td class="{{keys[ x ]}}">{{data.details.items[ i ][keys[ x ]]}}</td>
								<#
							}
						} #>
					</tr>
				<# } #>
			</tbody>
		</table>
	</div>
</script>