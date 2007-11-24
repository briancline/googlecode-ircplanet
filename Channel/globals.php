<?php

	define( 'SERVICE_NAME',           'Channel Service' );
	define( 'SERVICE_VERSION_MAJOR',  1 );
	define( 'SERVICE_VERSION_MINOR',  5 );
	define( 'SERVICE_VERSION_REV',    0 );
	
	define( 'SERVICE_DIR',          dirname(__FILE__) );
	define( 'SERVICE_CONFIG_FILE',  SERVICE_DIR .'/cs.ini' );
	define( 'SERVICE_HANDLER_DIR',  SERVICE_DIR .'/p10/' );
	define( 'CMD_HANDLER_DIR',      SERVICE_DIR .'/commands/' );
	define( 'SERVICE_TIMER_DIR',    SERVICE_DIR .'/timers/' );
	
	include( 'config.inc.php' );
	
?>
