<!DOCTYPE html>
<html lang="{document.lang}"%!server.debug% manifest="/app/{document.i18n}/application.manifest"%/!server.debug%>
	<head>
		<title>{server.name} : {i18n.title}</title>
		<link rel="stylesheet" type="text/css" href="/mpfs/public/css/desktop.css?mode=append" />
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link rel="icon" type="image/png" href="/mpfs/public/images/favicon.png" />
		<link rel="apple-touch-icon" href="/mpfs/public/images/favicon.png"/>
		<link rel="apple-touch-startup-image" href="/mpfs/public/images/favicon.png" />
		<link rel="bugreport" type="application/json" href="{server.location}bug.json" />
		<meta name="viewport" content="user-scalable=no, width=device-width"/>
		<meta name="i18n" content="{document.i18n}"/>
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="apple-mobile-web-app-status-bar-style" content="black" />
	</head>
	<body>
		<div class="application loading desktop"
		  data-app-database="{database.database}">
			<div class="loadingbox">
				<h1>{i18n.loading}</h1>
			</div>
		</div>
		<script type="text/javascript" src="/mpfs/public/javascript/mootools-core-1.4.5%!server.debug%-min%/!server.debug%.js"></script>
		<script type="text/javascript" src="/mpfs/public/javascript/RestRequest.js"></script>
		<!--<script type="text/javascript" src="/mpfs/public/javascript/RestQueue.js"></script>-->
		<script type="text/javascript" src="/mpfs/public/javascript/Element.js"></script>
		<!--<script type="text/javascript" src="/mpfs/public/javascript/Application.js"></script>-->
		<script type="text/javascript" src="/mpfs/public/javascript/WebApplication.js?mode=append"></script>
		<script type="text/javascript" src="/mpfs/public/javascript/profiles/UsersProfile.js"></script>
		<script type="text/javascript" src="/mpfs/public/javascript/profiles/AdministratorsProfile.js"></script>
		<script type="text/javascript" src="/mpfs/public/javascript/widgets/WebWindow.js"></script>
		<script type="text/javascript" src="/mpfs/public/javascript/widgets/AlertWindow.js"></script>
		<script type="text/javascript" src="/mpfs/public/javascript/widgets/ConfirmWindow.js"></script>
		<script type="text/javascript" src="/mpfs/public/javascript/widgets/FormWindow.js"></script>
		<script type="text/javascript" src="/mpfs/public/javascript/widgets/PromptWindow.js"></script>
		<script type="text/javascript" src="/mpfs/public/javascript/widgets/DbWindow.js"></script>
		<script type="text/javascript" src="/mpfs/public/javascript/widgets/DbEntryFormWindow.js"></script>
		<script type="text/javascript" src="/mpfs/public/javascript/widgets/PromptUserFileWindow.js"></script>
		<script type="text/javascript" src="/mpfs/public/javascript/widgets/BrowseWindow.js"></script>%server.debug%@widgetsScripts.files@
		<script type="text/javascript" src="/mpfs/public/javascript/widgets/@widgetsScripts.files:name@"></script>@/widgetsScripts.files@
		%/server.debug%
	</body>

</html>
