<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Brute Force Protection',
	'description' => 'Protects TYPO3´s frontend authentication (e.g. fe_login) against brute force attacks.',
	'category' => 'services',
	'author' => 'Kevin Schu, Andre Wuttig',
	'author_email' => 'kevin.schu@aoemedia.de, wuttig@portrino.de',
	'author_company' => 'AOE media GmbH, portrino GmbH',
	'shy' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'version' => '6.2.0',
	'constraints' => array(
		'depends' => array(
            'extbase' => '6.2',
            'fluid' => '6.2',
            'typo3' => '6.2',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);
