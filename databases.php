<?php

	/**
	 * List databases in a server
	 * @param $webdbServerID The ID of the current server
	 *
	 * $Id: databases.php,v 1.1 2003/01/18 06:38:36 chriskl Exp $
	 */

	// Include application functions
	include_once('conf/config.inc.php');

	$misc->printHeader($strDatabases);
?>

<h1><?php echo $appName ?></h1>

<p><?php echo $appIntro ?></p>

<?php
	$misc->printFooter();
?>