<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return array(
   'db' => array(
        'driver'    => 'Pdo_Mysql',
        'port'      => '3306',
        'charset'   => 'UTF8',
        'driver_options' =>  array(
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8";'
        )
   ), 
   'site' => array(
       'name' => 'Донбасс-Карпаты'
   ),
   'service_manager' => array(
      'factories' => array(
          
      ),
      'abstract_factories' => array(
         'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
         'Zend\Log\LoggerAbstractServiceFactory',
      ),
      'aliases' => array(
         'translator' => 'MvcTranslator',
      ),
   ),
   'translator' => array(
       'locale' => 'ru_RU',
       'translation_file_patterns' => array(
           array(
               'type'     => 'gettext',
               'base_dir' => BASE_PATH.'/module/default/language',
               'pattern'  => '%s.mo',
           ),
       ),
   ),
   'view_manager' => array(
       'display_not_found_reason' => true,
       'display_exceptions'       => true,
       'doctype'                  => 'HTML5',
       'not_found_template'       => 'error/404',
       'exception_template'       => 'error/index',
       'base_path' => ($_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'],
       'template_map' => array(
            'layout/layout'       => BASE_PATH.'/module/default/view/layout/layout.phtml',
            'error/404'           => BASE_PATH.'/module/default/view/error/404.phtml',
            'error/index'         => BASE_PATH.'/module/default/view/error/index.phtml',
       ),
       'strategies' => array(
           'ViewJsonStrategy',
       ),
   ),
);
