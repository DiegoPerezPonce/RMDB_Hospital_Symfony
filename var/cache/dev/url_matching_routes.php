<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/nurse/create' => [[['_route' => 'nurse_create', '_controller' => 'App\\Controller\\NurseController::create'], null, ['POST' => 0], null, false, false, null]],
        '/nurse/index' => [[['_route' => 'nurse_getAll', '_controller' => 'App\\Controller\\NurseController::getAll'], null, ['GET' => 0], null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/_error/(\\d+)(?:\\.([^/]++))?(*:35)'
                .'|/nurse/(?'
                    .'|name/([^/]++)(*:65)'
                    .'|id/([^/]++)(*:83)'
                    .'|([^/]++)(?'
                        .'|(*:101)'
                    .')'
                    .'|login(*:115)'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        35 => [[['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null]],
        65 => [[['_route' => 'nurse_find_by_name', '_controller' => 'App\\Controller\\NurseController::findByName'], ['name'], ['GET' => 0], null, false, true, null]],
        83 => [[['_route' => 'nurse_find_by_id', '_controller' => 'App\\Controller\\NurseController::findById'], ['id'], ['GET' => 0], null, false, true, null]],
        101 => [
            [['_route' => 'nurse_update', '_controller' => 'App\\Controller\\NurseController::update'], ['id'], ['PUT' => 0], null, false, true, null],
            [['_route' => 'nurse_delete', '_controller' => 'App\\Controller\\NurseController::delete'], ['id'], ['DELETE' => 0], null, false, true, null],
        ],
        115 => [
            [['_route' => 'login', '_controller' => 'App\\Controller\\NurseController::login'], [], ['POST' => 0], null, false, false, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
