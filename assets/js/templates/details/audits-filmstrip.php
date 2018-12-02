<script type="text/html" id="tmpl-audits-filmstrip">
	<p class="description">{{{data.description}}}</p>
	<div class="details">
		<# for (i = 0; i < data.details.items.length; i++ ) { #>
			<div><img src="data:image/png;base64, {{data.details.items[ i ].data}}" title="{{data.details.items[ i ].timing}} ms" alt="{{data.details.items[ i ].timing}} ms" /></div>
		<# } #>
	</div>
</script>