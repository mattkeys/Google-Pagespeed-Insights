<script type="text/html" id="tmpl-statistics">
	<tr title="{{data.description}}">
		<td class="leftcol">{{data.label}}</td>
		<td class="rightcol">
			<# if ( data.value ) { #>
				{{data.value}}
			<# } else { #>
				0
			<# } #>
		</td>
	</tr>
</script>