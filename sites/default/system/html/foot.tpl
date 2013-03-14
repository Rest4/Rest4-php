@document.scripts@
<script type="@document.scripts:type@" src="@document.scripts:url@"></script>@/document.scripts@%site.gatrack%
	<script type="text/javascript">
		// <![CDATA[ 
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', '{site.gatrack}']);
		_gaq.push(['_trackPageview']);
		(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
		//]]>
	</script>%/site.gatrack%
</div></body>
</html>