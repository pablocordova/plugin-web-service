<?php


//  Defined the web services functions to install

$functions = array(
            'local_wsbc_test_ws' => array(
                                    'classname' => 'local_wsbc_external',
                                    'methodname' => 'test_ws',
                                    'classpath' => 'local/wsbc/externallib.php',
                                    'description' => 'Para testear la comunicación con el web service',
                                    'type' => 'read'
                                    ),
            'local_wsbc_get_data_courses' => array(
                                    'classname' => 'local_wsbc_external',
                                    'methodname' => 'get_data_courses',
                                    'classpath' => 'local/wsbc/externallib.php',
                                    'description' => 'Informacion de todos los cursos que le corresponden a un especifico profesor, caso tenga rol gestor, obtendra todos los cursos',
                                    'type' => 'read'
                                    ),
            'local_wsbc_get_data_alumns' => array(
                                    'classname' => 'local_wsbc_external',
                                    'methodname' => 'get_data_alumns',
                                    'classpath' => 'local/wsbc/externallib.php',
                                    'description' => 'Informacion de todos quiz dados o no dados por los alumnos de un especifico curso, caso tenga rol gestor, se enviara los campos opcionales',
                                    'type' => 'read'
                                    ),
            'local_wsbc_get_detail_grade' => array(
                                    'classname' => 'local_wsbc_external',
                                    'methodname' => 'get_detail_grade',
                                    'classpath' => 'local/wsbc/externallib.php',
                                    'description' => 'Informacion sobre los intentos que se ha dado de un espcifico quiz',
                                    'type' => 'read'
                                    )
            );


//  Defined the services to install as pre-build services.

$services = array(
            'Atypax banco comercio webservice' => array(
                                                    'functions' => array(
                                                                    'local_wsbc_test_ws',
                                                                    'local_wsbc_get_data_courses',
                                                                    'local_wsbc_get_data_alumns',
                                                                    'local_wsbc_get_detail_grade'
                                                                    ),
                                                    'restrictedusers' => 0,
                                                    'enabled' => 1,
                                                    'shortname' => 'wsbcname'

                                                )
            );