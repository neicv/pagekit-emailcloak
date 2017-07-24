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

	'routes' => [

        '/emailcloak' => [
            'name' => '@emailcloak/admin',
			//'path' => '/emailcloak',
            'controller' => 'Friendlyit\\Emailcloak\\Controller\\EmailcloakController'
        ]

    ],
	
	
	'resources' => [

		'friendlyit/emailcloak:' => ''

	],

	
	'config' => [
			'mode'    => '1'
    ],

    'menu' => [

        'emailcloak' => [
            'label'  => 'Emailcloak',
			'icon'   => 'friendlyit/emailcloak:icon.svg',
			'active' => '@emailcloak/admin',
            'url'    => '@emailcloak/admin',
        ],

        'emailcloak: settings' => [
			'label' => 'Settings',
            'parent' => 'emailcloak',
            'url' => '@emailcloak/admin/settings',
			'active' => '@emailcloak/admin/settings*',
			'access' => 'system: access settings'
        ]
    ],
	
	'settings' => '@emailcloak/admin/settings',
	 
	'events' => [

        'boot' => function ($event, $app) {
            $app->subscribe(
                new EmailCloakPlugin
            );
        },
		'view.scripts' => function ($event, $scripts) {
            $scripts->register('emailcloak-settings', 'friendlyit/emailcloak:/app/bundle/settings.js', '~extensions');
        },

    ]

];