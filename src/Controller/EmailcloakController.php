<?php

namespace Friendlyit\emailcloak\Controller;
use Pagekit\Application as App;
class EmailcloakController
{
    /**
     * @Access(admin=true)
     */
/*     public function indexAction()
    {
        return [
            '$view' => [
                'title' => __('Emailcloak Settings'),
                'name'  => 'friendlyit/emailcloak:views/admin/settings.php'
            ],
            '$data' => [
                'config' => App::module('friendlyit/emailcloak')->config()
			]
        ];
    } */
	
	 /**
     * @Route("/", methods="GET")
	 * @Access(admin=true)
     */
    public function indexAction()
    {
        return [
            '$view' => [
                'title' => __('Emailcloak Settings'),
                'name'  => 'friendlyit/emailcloak:views/admin/settings.php'
            ],
            '$data' => [
				'config' => App::module('friendlyit/emailcloak')->config()
            ]
        ];
    }

	
	 /**
     * Access("system: access settings")
	 * @Access(admin=true)
     */
    public function settingsAction()
    {
        return [
            '$view' => [
                'title' => __('Emailcloak Settings'),
                //'name'  => 'friendlyit/emailcloak:views/admin/settings.php'
				'name'  => 'friendlyit/emailcloak:views/admin/settings.php'
            ],
            '$data' => [
                'config' => App::module('friendlyit/emailcloak')->config()
            ]
        ];
    }
}