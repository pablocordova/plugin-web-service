<?php

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/config.php');
require_once('private_functions.php');


class local_wsbc_external extends external_api {

    /**
     *  To test the web service
     *  @param testmessage TEXT, message to return adding some text
     *  @return the same message arrived but attached some text
     */

    public static function test_ws_parameters() {
        //  Receive the variable "testmessage in url"
        return new external_function_parameters(
                array(
                    'testmessage' => new external_value(PARAM_TEXT, 'Mensaje para retornarlo')
                    )
        );
    }

    public static function test_ws($testmessage) {

        global $USER;

        $message_test = 'Message from server: ';
        //  Validate type variable
        $params = self::validate_parameters(self::test_ws_parameters(), array('testmessage' => $testmessage));

        return $message_test.$params['testmessage'].' OK';
    }

    public static function test_ws_returns() {
        //  As only there is one variable, i return it.
        return new external_value(PARAM_TEXT, 'Retorna el mensaje enviado con concatenada con la palabra OK');
    }


    /**
     *  Data about courses
     *  @input case teacher : nothing
     *  @input case gestor : nothing
     *  @return case teacher: all courses of teacher 
     *  @return case gestor: equal of previous return but of all courses without restricction by teacher
     */
    public static function get_data_courses_parameters() {
        return new external_function_parameters(
            array(
                'sessionid' => new external_value(PARAM_TEXT, 'Session id del usuario')
                )
            );
    }

    public static function get_data_courses($sessionid) {
        global $DB;
        $paramaters = self::validate_parameters(self::get_data_courses_parameters(), array('sessionid' => $sessionid));
        $session_data = $DB->get_record('sessions', array('sid' => $paramaters['sessionid']));

        // if session data not exits return nothing
        if (!$session_data) {
            return array();
        }

        // GESTOR
        // Now we going to know if this user has gestor or teacher rol.
        $pf = new PrivateFunctions();
        $isGestor = $pf->indentifyGestor($session_data->userid);

        $array_courses_teacher = array();

        if($isGestor) {

            $sql = ' SELECT c.id, c.fullname, c.visible, cc.name
                        FROM {course_categories} as cc
                        JOIN {course} as c ON c.category = cc.id
                        ORDER BY c.id DESC';

            $data_courses = $DB->get_records_sql($sql);

        } else {

            // TEACHER
            // Now suppose that is teacher with edition permission : rol 3
            $sql = ' SELECT c.id, c.fullname, c.visible, cc.name
                        FROM {course_categories} as cc
                        JOIN {course} as c ON c.category = cc.id
                        JOIN {context} as co ON co.instanceid = c.id
                        JOIN {role_assignments} as ra ON ra.contextid = co.id
                        WHERE ra.userid = ? and ra.roleid = 3
                        ORDER BY c.id DESC';

            $params = array($session_data->userid);
            $data_courses = $DB->get_records_sql($sql, $params);

        }   

        // Data to send
        foreach ($data_courses as $data) {

            $course_data = new stdClass();
            $course_data->id = $data->id;
            $course_data->fullname = $data->fullname;
            $course_data->category = $data->name;
            $course_data->visible = ($data->visible == '1' ? 'Si' : 'No'); 

            $array_courses_teacher[] = $course_data;
        }
        
        return $array_courses_teacher;
    }

    public static function get_data_courses_returns() {

        return new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'Id del curso'),
                        'fullname' => new external_value(PARAM_TEXT, 'Fullname del curso'),
                        'category' => new external_value(PARAM_TEXT, 'Categoria al cual pertenece el curso'),
                        'visible' => new external_value(PARAM_TEXT, 'Visibilidad del curso Si/No')
                    )
                )
            );  
    }

    /**
     *  Data about alumns
     *  @input courseid: course id
     *  @return : all data about information of alumns
     */

    public static function get_data_alumns_parameters() {
        return new external_function_parameters(
                array(
                    'sessionid' => new external_value(PARAM_TEXT, 'Session id del usuario'),
                    'courseid' => new external_value(PARAM_TEXT, 'Id del curso')
                    )
            );
    }

    public static function get_data_alumns($sessionid, $courseid) {
        global $DB;

        // how many days as maximun have to make the exam
        $days = 1;

        $parameters = self::validate_parameters(self::get_data_alumns_parameters(), array('courseid' => $courseid, 'sessionid' => $sessionid));
        $session_data = $DB->get_record('sessions', array('sid' => $parameters['sessionid']));

        // if session data not exits return nothing
        if (!$session_data) {
            return array();
        }

        // GESTOR
        // Now we going to know if this user has gestor or teacher rol.
        $pf = new PrivateFunctions();
        $isGestor = $pf->indentifyGestor($session_data->userid);

        // All the process

        $sql = ' SELECT u.id, u.username, u.firstname, u.lastname
                    FROM {course} as c
                    JOIN {context} as ct ON ct.instanceid = c.id
                    JOIN {role_assignments} as ra ON ra.contextid = ct.id
                    JOIN {role} as r ON r.id = ra.roleid
                    JOIN {user} as u ON u.id = ra.userid
                    WHERE r.id = 5 AND c.id = ?';

        $params = array($parameters['courseid']);
        $data = $DB->get_records_sql($sql, $params);

        $array_students = array();

        $aaa = 0;
        foreach ($data as $students) {
            $aaa++;
            // To grades
            $accumulative_grade = 0;
            $count_grades = 0;
            
            //Creating a class to save all students
            $a = new stdClass();

            $a->userid = $students->id;
            $a->dni = $students->username;
            // $a->firstname = $students->firstname;
            // $a->lastname = $students->lastname;
            // Data from json
            $sql = ' SELECT bc.division, bc.departamento, bc.centrocostos, bc.centrocosto, bc.empleado, bc.nombrecompleto, bc.cargoemp, bc.descripciontipolicencia
                        FROM {banco_comercio} as bc
                        WHERE bc.dni = ?
                        ORDER BY bc.id DESC LIMIT 1';

            //$licenses_user = $DB->get_records('banco_comercio', array('DNI' => $students->username));
            $data_user = $DB->get_record_sql($sql, array('dni' => $students->username));
            $a->division = '-';
            $a->departamento = '-';
            $a->centrocostos = '-';
            $a->centrocosto = '-';
            $a->empleado = '-';
            $a->nombrecompleto = '-';
            $a->cargoemp = '-';
            $a->descripciontipolicencia = '-';

            if ($data_user) {
                $a->division = $data_user->division;
                $a->departamento = $data_user->departamento;
                $a->centrocostos = $data_user->centrocostos;
                $a->centrocosto = $data_user->centrocosto;
                $a->empleado = $data_user->empleado;
                $a->nombrecompleto = $data_user->nombrecompleto;
                $a->cargoemp = $data_user->cargoemp;
                $a->descripciontipolicencia = $data_user->descripciontipolicencia;
            }
            

            // saving all rest data about quizes
            $array_quizes = array();
            $index_array_quizes = 0;
            // I'm going to get the sequences, it is necessary to send the data ordered
            $sections_course = $DB->get_records('course_sections', array('course' => $parameters['courseid']));
            foreach ($sections_course as $section) {
                $sequence_list = explode(',', $section->sequence);
                // For each module
                $quiz_name_to_period = '-';
                $index_quiz = 0;
                foreach ($sequence_list as $course_module_id) {


                    // I get ONLY information of modules that are quizes
                    $sql = ' SELECT q.id, q.name, q.timeopen, q.timeclose, cm.availability
                                FROM {course_modules} as cm
                                JOIN {quiz} as q ON q.id = cm.instance
                                WHERE cm.module = 16 AND cm.id = ?';

                    $quiz = $DB->get_record_sql($sql, array($course_module_id));

                    // Case module not is a quiz, discard it
                    if(!$quiz) {
                        break;
                    }



                    // I tried to get grades of quiz, otherwise by default must be -
                    $grade_quiz = $DB->get_record('quiz_grades', array('quiz' => $quiz->id, 'userid' => $students->id));

                    $grade = '-';
                    if($grade_quiz) {
                        $accumulative_grade += $grade_quiz->grade;
                        $count_grades++;
                        $grade = round($grade_quiz->grade, 2);
                    }

                    // Generating data of a quiz
                    $quiz_data = new stdClass();

                    $quiz_data->name_quiz = $quiz->name;

                    $array_periods = array();

                    $period_data = new stdClass();
                    $period_data->quizid = $quiz->id;
                    $period_data->grade = $grade;

                    // Now when is gestor I will return the state
                    
                    if ($isGestor) {
                        // We going to define  the state , it depends of the state of quiz
                        // If the user has grade, then it meaning that either is Aprobado or Desaprobado
                        if($grade_quiz) { 
                            $grade_to_pass = $DB->get_record('grade_items', array('courseid' => $parameters['courseid'], 'iteminstance' => $quiz->id));
                            if ($grade >= $grade_to_pass->gradepass) {
                                $period_data->state = 'Aprobado';
                                $period_data->dateattempt = '-';
                                $period_data->detailattempt = '-';
                            }
                            else {
                                $period_data->state = 'Desaprobado';
                                $period_data->dateattempt = '-';
                                $period_data->detailattempt = '-';
                            }

                        }
                        else {
                            // If the user dont have a grade is because not is his periode or he didn't make the quiz:

                            // 1. Case not is his period, we can know this with group.
                            // Check if the user belongs to a group that need to make the quiz

                            // Get the group of the quiz
                            $groupid = -1;
                            $availability = json_decode($quiz->availability);
                            $array_availibility = $availability->c;
                            foreach ($array_availibility as $value) {
                                if ($value->type == 'group') {
                                    $groupid = (int)$value->id;
                                }
                            }
                            // Now we going to check if the user is inside the group
                            $user_in_group = $DB->get_record('groups_members', array('groupid' => $groupid, 'userid' => $students->id));

                            // case the user doesn't have in the group
                            if (!$user_in_group) {
                                $period_data->state = '-';
                                $period_data->dateattempt = '-';
                                $period_data->detailattempt = '-';
                            }
                            else {
                                // case the user is in the group, I'm going to check the json data
                                // 2. Case the user is in the json data that contains permissions of users.
                                //      here we have "Justificado" and "No Justificado"
                                //          Justificado: When the user have less equal than x days to make the examn, for this case x = 1
                                //          No Justificado: else of Justificado

                                // Get all licenses current user
                                // I will use sql statement to add order by asc
                                $sql = ' SELECT bc.desde, bc.hasta, bc.descripciontipolicencia
                                            FROM {banco_comercio} as bc
                                            WHERE bc.dni = ?
                                            ORDER BY bc.id ASC';

                                //$licenses_user = $DB->get_records('banco_comercio', array('DNI' => $students->username));
                                $licenses_user = $DB->get_records_sql($sql, array('DNI' => $students->username));

                                if ($licenses_user) {
                                    $period_data->state = 'No Justificado';
                                    foreach ($licenses_user as $license) {
                                        $license_open = strtotime($license->desde);
                                        $license_close = strtotime($license->hasta);
                                        $isJustified = $pf->calculateIfJustified($quiz->timeopen, $quiz->timeclose, $license_open, $license_close, $days);
                                        if ($isJustified) {
                                            $period_data->state = 'Justificado';
                                            $period_data->dateattempt = $license->desde.'-'.$license->hasta;
                                            $period_data->detailattempt = $license->descripciontipolicencia;
                                            break;
                                        }
                                    }
                                }
                                else {
                                    // The last case in when other options not are found then only I will write "-" (strange case, but possible)
                                    $period_data->state = '-';
                                    $period_data->dateattempt = '-';
                                    $period_data->detailattempt = '-';
                                }

                            }
                            
                        }

                    }

                    $array_periods[] = $period_data;

                    $quiz_data->periods = $array_periods;

                    // Case the current quiz have the same name to the previous quiz, the take it as a period
                    if ($quiz_name_to_period == $quiz->name) {
                        $array_quizes[$index_array_quizes - 1]->periods[] = $period_data;
                    }else {
                        // If no is a period then take it as another quiz
                        $array_quizes[] = $quiz_data;
                        $index_array_quizes++;
                        $index_quiz = $index;
                    }

                    $quiz_name_to_period = $quiz->name;

                }
            }

            // code to get the grade's average
            $a->totalgrade = 0;
            if ($count_grades > 0) {
                $a->totalgrade = round($accumulative_grade/$count_grades, 2);
            }

            $a->quiz = $array_quizes;
            $array_students[] = $a; 


        }

        return $array_students;
    }

    public static function get_data_alumns_returns() {
        //return new external_value(PARAM_TEXT);

        return new external_multiple_structure(
            new external_single_structure(
                    array(
                        'userid' => new external_value(PARAM_INT, 'Id del alumno'),
                        'dni' => new external_value(PARAM_TEXT, 'Username/dni del alumno'),
                        'division' => new external_value(PARAM_TEXT, 'Division donde pertenece el alumno'),
                        'departamento' => new external_value(PARAM_TEXT, 'Departamento donde pertenece el alumno'),
                        'centrocostos' => new external_value(PARAM_TEXT, 'Centro de costos'),
                        'centrocosto' => new external_value(PARAM_TEXT, 'centro de costo/ Local name'),
                        'empleado' => new external_value(PARAM_TEXT, 'Numero de empleado'),
                        'nombrecompleto' => new external_value(PARAM_TEXT, 'Nombre completo del alumno'),
                        'cargoemp' => new external_value(PARAM_TEXT, 'Cargo del alumno'),
                        'descripciontipolicencia' => new external_value(PARAM_TEXT, 'Descripcion de la licencia'),
                        'totalgrade' => new external_value(PARAM_TEXT, 'Nota promedio de todos los quizes'),
                        'quiz' => new external_multiple_structure(
                                        new external_single_structure(
                                                array(
                                                    'name_quiz' => new external_value(PARAM_TEXT, 'Nombre del quiz'),
                                                    'periods' => new external_multiple_structure(
                                                                        new external_single_structure(
                                                                                array(
                                                                                    'quizid' => new external_value(PARAM_INT, 'Id del quiz'),
                                                                                    'grade' => new external_value(PARAM_TEXT, 'Nota del quiz'),
                                                                                    'state' => new external_value(PARAM_TEXT, 'Estado del quiz Aprobado/Desaprobado/Justificado/No Justificado/-', VALUE_OPTIONAL),
                                                                                    'dateattempt' => new external_value(PARAM_TEXT, 'Fecha de la licencia', VALUE_OPTIONAL),
                                                                                    'detailattempt' => new external_value(PARAM_TEXT, 'Detalle de la licencia', VALUE_OPTIONAL)
                                                                                    )
                                                                            )
                                                        )
                                                )
                                        )
                        )
                    )
            )
        );

    }

    /**
     *  Get detail of a specific grade
     *  @input quizid
     *  @input userid
     *  @return : Intents(amount of intents with its dates), permission dates, state, details 
     */
    public static function get_detail_grade_parameters() {
        return new external_function_parameters(
                array(
                    'sessionid' => new external_value(PARAM_TEXT, 'Session id del usuario'),
                    'userid' => new external_value(PARAM_TEXT, 'Id del alumno'),
                    'quizid' => new external_value(PARAM_TEXT, 'Id del quiz')
                    )
            );
    } 

    public static function get_detail_grade($sessionid, $userid, $quizid) {
        global $DB;

        $parameters = self::validate_parameters(self::get_detail_grade_parameters(), array('sessionid' => $sessionid, 'userid' => $userid, 'quizid' => $quizid));
        $session_data = $DB->get_record('sessions', array('sid' => $parameters['sessionid']));

        // Discriminate only gesto
        $pf = new PrivateFunctions();
        $isGestor = $pf->indentifyGestor($session_data->userid);

        // if session data not exits return nothing
        if (!$session_data || !$isGestor) {
            return array();
        }

        // Is necessary make the query and return it in order ASCENDENT, because in that order will be the attempts.
        $sql = ' SELECT ggh.id, ggh.finalgrade, ggh.timemodified
                    FROM {grade_items} as gi
                    JOIN {grade_grades_history} as ggh ON ggh.itemid = gi.id
                    WHERE gi.iteminstance = ? AND ggh.userid = ? AND ggh.finalgrade IS NOT NULL
                    ORDER BY ggh.id ASC';

        $params = array($parameters['quizid'], $parameters['userid']);

        $attempts = $DB->get_records_sql($sql, $params);

        $array_attempts = array();

        // data to send the attempts
        foreach ($attempts as $attempt) {

            $data_attempt = new stdClass();

            $data_attempt->grade = $attempt->finalgrade;
            $data_attempt->date = date('F j, Y, g:i a', $attempt->timemodified);

            $array_attempts[] = $data_attempt;
        }

        return $array_attempts;

    }

    public static function get_detail_grade_returns() {
        //return new external_value(PARAM_TEXT);

        return new external_multiple_structure(
            new external_single_structure(
                    array(
                        'grade' => new external_value(PARAM_TEXT, 'Nota del intento'),
                        'date' => new external_value(PARAM_TEXT, 'Fecha del intento')
                    )
            )
        );

    }

    

}