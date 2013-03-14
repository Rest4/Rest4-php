<h2>{i18n.§.title} {uriNodes.3}</h2>
<p>{i18n.§.description}</p>%§.syntax%
<h3>{i18n.§.summary_title}</h3>
<p>
	<strong>{i18n.§.summary_name}</strong> {§.syntax.name}<br />
	<strong>{i18n.§.summary_description}</strong> {§.syntax.description}<br />
	<strong>{i18n.§.summary_usage}</strong> {§.syntax.usage}
</p>
<h3>{i18n.§.syntax_title}</h3>%§.syntax.methods%
<ul>@§.syntax.methods@
	<li>
		<strong>@§.syntax.methods:n@:</strong><br />
		<strong>{i18n.§.syntax_outputMimes}</strong> @§.syntax.methods:outputMimes@%@§.syntax.methods:queryParams%<br />
		<strong>{i18n.§.syntax_queryParams}</strong>
		<ul>@§.syntax.methods.@§.syntax.methods:n@.queryParams@
			<li>
				<strong>@§.syntax.methods.@§.syntax.methods:n@.queryParams:name@</strong>%@§.syntax.methods.@§.syntax.methods:n@.queryParams:value%,
				 default: @§.syntax.methods.@§.syntax.methods:n@.queryParams:value@%/@§.syntax.methods.@§.syntax.methods:n@.queryParams:value%%@§.syntax.methods.@§.syntax.methods:n@.queryParams:type%,
				 type: @§.syntax.methods.@§.syntax.methods:n@.queryParams:type@%/@§.syntax.methods.@§.syntax.methods:n@.queryParams:type%%@§.syntax.methods.@§.syntax.methods:n@.queryParams:filter%,
				 filter: @§.syntax.methods.@§.syntax.methods:n@.queryParams:filter@%/@§.syntax.methods.@§.syntax.methods:n@.queryParams:filter%.
			</li>@/§.syntax.methods.@§.syntax.methods:n@.queryParams@
		</ul>%/@§.syntax.methods:queryParams%
	</li>@/§.syntax.methods@
</ul>%/§.syntax.methods%%/§.syntax%%!§.syntax%
<p>{i18n.§.syntax_none}</p>%/!§.syntax%
<h3>{i18n.§.source_title}</h3>
<pre>{§.source}</pre>
<p><a href="/mpfs/xcms/php/class.Rest{uriNodes.3}Driver.php?download=Rest{uriNodes.3}Driver" title="{i18n.§.source_download_link_tx}">{i18n.§.source_download_link}</a></p>