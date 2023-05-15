<?php

use GeneMarks\Jelly;

load([
    'GeneMarks\\Jelly' => 'Jelly.php'
], __DIR__);

Kirby::plugin('genemarks/jelly', [
	'routes' => [
		[
			'pattern' => '(:all)/jellyfin-watched',
			'action' => function() {
                $jelly = new Jelly;
				return response::json($jelly->getWatchedItems());
			},
            'method' => 'GET|POST'
		]
	],
]);