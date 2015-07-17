<?php
session_start();

require_once dirname( __FILE__ ) . '/includes/PHPVersionCheck.php';

require __DIR__ . '/includes/WebStart.php';

// require_once "$IP/extensions/Scribunto/Scribunto.php";
// $wgScribuntoUseGeSHi = true;
$wgScribuntoDefaultEngine = 'luaremotebinary';
$wgScribuntoEngineConf['luastandalone']['errorFile'] = "/var/log/mediawiki/lua.log";
$wgScribuntoLuaRemoteDebug = false;

$url = $_SERVER['REQUEST_URI'];
if ( !preg_match( '!^https?://!', $url ) ) {
	$url = 'http://unused' . $url;
}
wfSuppressWarnings();
$a = parse_url( $url );

$pairs = explode('&', $a['query']);

//Analyze retrieved strings and look for the ones of interest:
$count = 0;
$msg = null;

foreach ($pairs as $pair) {
	$keyVal = explode('=',$pair);
	$key = &$keyVal[0];
	
	if ($key == 'msg') { 
		$msg = urldecode($keyVal[1]);
		break;
	}
	++$count;
}

if ($msg !== null) {
	$engine = Scribunto::newDefaultEngine();
	
	if ($wgScribuntoLuaRemoteDebug)
		$msgToLua = serialize($msg);
	else 
		$msgToLua = $msg;
	$msgFromLua = $engine->getInterpreter()->dispatch($msgToLua);
	
	echo $msgFromLua;
}
else {
	$ret = array();
	$ret['op'] = 'error';
	$ret['nvalues'] = 1;
	$ret['values'] = array();
	$ret['values']['msg'] = "Invalid request";
	echo serialize($ret);
}