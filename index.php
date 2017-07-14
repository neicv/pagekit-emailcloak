<?php
use Pagekit\Application;
use Friendlyit\Emailcloak\Plugin\EmailCloakPlugin;

return [

    'name' => 'friendlyit/emailcloak',

    'type' => 'extension',

    'autoload' => [

		'Friendlyit\\Emailcloak\\' => 'src'

    ],
	'main' => function (Application $app) {
        // bootstrap code
    },

	'nodes' => [

        /* 'emailcloak' => [
			'name' => '@emailcloak',
            'label' => 'Emailcloak',
            'controller' => 'Friendlyit\\Emailcloak\\Controller\\SiteController',
            'protected' => true
        ] */
    ],

	'routes' => [

        '@emailcloak' => [
            //'name' => '@emailcloak',
			'path' => '/emailcloak',
            'controller' => 'Friendlyit\\Emailcloak\\Controller\\EmailcloakController'
        ]

    ],
	
	
	'resources' => [

		'friendlyit/emailcloak:' => ''
		//'views:friendlyit/emailcloak' => 'views'

	],

	
	'config' => [
			'mode'    => 'PLG_CONTENT_EMAILCLOAK_LINKABLE'
    ],

    'menu' => [

        'emailcloak' => [
            'label'  => 'Emailcloak',
            'icon'   => 'friendlyit/emailcloak:icon.svg',
            'url'    => '@emailcloak',
        ],

        'emailcloak: settings' => [
			'label' => 'Settings',
            'parent' => 'emailcloak',
            'url' => '@emailcloak/settings',
			'active' => '@emailcloak/settings*',
            'access' => 'system: manage settings'
        ]
    ],

    'permissions' => [

		'emailcloak: manage settings' => [
            'title' => 'Manage settings'
        ]

    ],
	
	//'settings' => '@emailcloak/admin/settings',
	 
	'events' => [

        'boot' => function ($event, $app) {
            $app->subscribe(
                new EmailCloakPlugin
            );
        }
/* 		'view.scripts' => function ($event, $scripts) use ($app) {
            $scripts->register('uikit-search', 'app/assets/uikit/js/components/search.min.js', 'uikit');
            $scripts->register('uikit-autocomplete', 'app/assets/uikit/js/components/autocomplete.min.js', 'uikit');
        } */

    ]

];