<?php
// Send mails when there are bugs (use carefully)
define('DEBUG_MAIL', '');
// Print bugs to visitors (use for dev only)
define('DEBUG_PRINT', true);
// Print errors when any request fails (even if it's an internal request)
define('DEBUG_RESOURCES', true);
// Let's go for the Rest world !
require 'rest4.php';