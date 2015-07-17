<?php
// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
		'path' => __FILE__,
		'name' => 'Xxxpedia',
		'author' => 'Xxxpedia',
		'version' => '0.0.1',
		'url' => 'no url',
		'descriptionmsg' => 'xxxpedia-desc',
);
/* Setup */
$dir = __DIR__ . '/';
// $wgMessagesDirs['Xxxpedia'] = __DIR__ . '/i18n';
// $wgExtensionMessagesFiles['Xxxpedia'] = $dir . 'Xxxpedia.i18n.php';
// $wgExtensionMessagesFiles['XxxpediaAlias'] = $dir . 'Xxxpedia.alias.php';
// API
// $wgAutoloadClasses['ApiXxxpedia'] = $dir . 'api/ApiXxxpedia.php';
// $wgAPIModules['Xxxpedia'] = 'ApiXxxpedia';
// Schema
$wgAutoloadClasses['XxxpediaHooks'] = $dir . 'XxxpediaHooks.php';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'XxxpediaHooks::loadExtensionSchemaUpdates';
$wgHooks['ParserTestTables'][] = 'XxxpediaHooks::parserTestTables';
// Special page
$wgAutoloadClasses['SpecialXxxpedia'] = $dir . 'SpecialXxxpedia.php';
$wgSpecialPages['Xxxpedia'] = 'SpecialXxxpedia';