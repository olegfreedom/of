<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'router' => array(
        'routes' => array(
            'forum' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/forum',
                    'defaults' => array(
                        'controller' => 'Forum\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '[/:controller[/:action[/:get_http_request_string]]][/]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'get_http_request_string' => '[a-zA-Z0-9][a-zA-Z0-9_\/-\\\.]*',
                            ),
                            'defaults' => array(
                                'action' => 'index',
                                '__NAMESPACE__' => 'Forum\Controller'
                            )
                        )
                    ),
                    'group-edit' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/group/[:id]/edit',
                            'constraints' => array(
                                'id'     => '[0-9]*',
                            ),
                            'defaults' => array(
                                'action' => 'edit',
                                'controller' => 'Forum\Controller\Group',
                            )
                        )
                    ),
                    'group-delete' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/group/[:id]/delete',
                            'constraints' => array(
                                'id'     => '[0-9]*',
                            ),
                            'defaults' => array(
                                'action' => 'delete',
                                'controller' => 'Forum\Controller\Group',
                            )
                        )
                    ),
                    'group-view' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/group/[:id_group]',
                            'constraints' => array(
                                'id_group'     => '[0-9]*',
                            ),
                            'defaults' => array(
                                'action' => 'list',
                                'controller' => 'Forum\Controller\Theme',
                            )
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'theme-list' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/themes',
                                    'defaults' => array(
                                        'action' => 'list',
                                        'controller' => 'Forum\Controller\Theme',
                                    )
                                )
                            ),
                            'theme-add' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/theme/add',
                                    'defaults' => array(
                                        'action' => 'add',
                                        'controller' => 'Forum\Controller\Theme',
                                    )
                                )
                            ),
                            'theme-edit' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/theme/[:id_theme]/edit',
                                    'constraints' => array(
                                        'id_theme'  => '[0-9][0-9]*',
                                    ),
                                    'defaults' => array(
                                        'action' => 'edit',
                                        'controller' => 'Forum\Controller\Theme',
                                    )
                                )
                            ),
                            'theme-delete' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/theme/[:id_theme]/delete',
                                    'constraints' => array(
                                        'id_theme'  => '[0-9][0-9]*',
                                    ),
                                    'defaults' => array(
                                        'action' => 'delete',
                                        'controller' => 'Forum\Controller\Theme',
                                    )
                                )
                            ),
                             'theme-view' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/theme/[:id_theme]/questions',
                                    'constraints' => array(
                                        'id_theme'  => '[0-9][0-9]*',
                                    ),
                                    'defaults' => array(
                                        'action' => 'list',
                                        'controller' => 'Forum\Controller\Question',
                                    )
                                )
                            ),
                        ),
                    ),
                    // --------- group ---------
                ),
                // ------ forum ---------- 
            ),
            // -----------------
        ),
    ),
    'view_manager' => array(
        'template_map' => array(
            'forum/index/index'    => __DIR__ . '/../view/forum/index/index.phtml', 
            'forum/layout'         => __DIR__ . '/../view/layout/layout.phtml',
            'forum/layout/header'  => __DIR__ . '/../view/layout/header.phtml',
            'forum/layout/footer'  => __DIR__ . '/../view/layout/footer.phtml',

        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
