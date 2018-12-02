<script type="text/html" id="tmpl-audits-criticalrequestchain">
	<h3>{{data.name}} <span class="right">{{data.displayValue}}</span></h3>
	<div class="details">
		<p class="description">
			{{{data.description}}}
		</p>
		<div class="crc">
			<ul>
				<#
					for ( var chain in data.details.chains ) {
						outputChain( data.details.chains[ chain ], false );
					}

					function outputChain( chain, subchain ) {
						if ( ! subchain ) {
							#>
							<li>
								<span class="url">{{chain.request.url}}</span>
								<span class="size">{{chain.request.transferSize}}</span>
								<#
									if ( 'undefined' != typeof chain.children ) {
										outputChain( chain.children, true );
									}
								#>
							</li>
							<#
						} else {
							for ( var newChain in chain ) {
								#>
								<ul>
									<li>
										<span class="url">{{chain[ newChain ].request.url}}</span>
										<span class="size">{{chain[ newChain ].request.transferSize}}</span>
										<#
											if ( 'undefined' != typeof chain[ newChain ].children ) {
												outputChain( chain[ newChain ].children, true );
											}
										#>
									</li>
								</ul>
								<#
							}
						}
					}
				#>
			</ul>
		</div>
	</div>
</script>