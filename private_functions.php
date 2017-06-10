<?php

require_once($CFG->dirroot.'/config.php');

class PrivateFunctions {

    /**
     *  To get Gestor data
     *  @param $userid number: id user
     *  @return array: id of Gestor
     */

    function indentifyGestor($userid) {

        global $DB;

        $sql = ' SELECT r.id
                    FROM {role} as r
                    JOIN {role_assignments} as ra ON ra.roleid = r.id
                    WHERE ra.userid = ? and r.shortname = "gestor"
                ';
        $params = array($userid);
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Check if a User is justified or not to make the quiz
     * It is calculated with his license time interval and his quiz time interval
     * If the user has more than "$days" days without license to make the quiz, then the user not is justified
     * else if the user is with license and have less than "$days" days to make the quiz, then is justified.
     *
     * @param $quiz_open bigint(10), time quiz is opened
     * @param $quiz_close bigint(10), time quiz is closed
     * @param $license_open bigint(10), time license is opened
     * @param $license_close bigint(10), time license is closed
     * @return boolean, true if is justified and false if not is justified
     */

    function calculateIfJustified($quiz_open, $quiz_close, $license_open, $license_close, $days) {

        //TODO: find more pro way to do this, for now I have a rustic way (sentences if-else)

        // Case 1 and 5 by default are $justified = false

        $justified = false;


        /**
         * Analize in all quizes of all courses
         * Case time start of license is minor/equal time start of quiz
         * and time end of license is major/equal time end of quiz
         *
         *      open*...........*close    <--- time quiz
         *    open*...............*close  <--- time license
         *
         * Then the colaborator is justified else other cases:
         * Here we have 5 cases in wich can be justified or not.
         *
         *
         * CASE 1
         *                      open*..........*close    <--- time quiz
         *  open*........*close                          <--- time license
         *
         *
         * CASE 2
         *      open*..........*close    <--- time quiz
         *  open*........*close          <--- time license
         *
         *
         * CASE 3
         *      open*....................*close    <--- time quiz
         *              open*....*close            <--- time license
         *
         * CASE 4
         *      open*............*close            <--- time quiz
         *              open*..........*close      <--- time license 
         *
         * CASE 5
         *  open*..........*close                            <--- time quiz
         *                         open*........*close       <--- time license
         *
         */

        if ($license_open <= $quiz_open AND $license_close >= $quiz_close) {
            $justified = true;
        }
        else {
            // case 1 and 2
            if ($quiz_open >= $license_open) {
                // case 2
                if ($quiz_open <= $license_close) {
                    $justified = $this->isInsideDaysJustified($quiz_close, $license_close, $days);  
                }  
            }
            // case 3,4 and 5
            else {
                // case 3
                // This case 3 for $days = 1 is unnecessary
                // But I write it if someday: $days != 1
                if ($quiz_close >= $license_close) {

                    $difference = ($license_open - $quiz_open) + ($quiz_close - $license_close);
                    $total_days = date('z', $difference) + 2;

                    if ($total_days <= $days) {
                         $justified = true;
                    }
                }
                // case 4
                elseif ($quiz_close >= $license_open)  {
                    $justified = $this->isInsideDaysJustified($license_open, $quiz_open, $days);  
                }
            }
        }
        return $justified;
    }

    /**
     *  Calculate if the difference of dates is inside the days specified
     *
     *  @param $a datetime: the major date
     *  @param $b datetime: the minor date
     *  @param $days number: amoung of days to consider
     *  @return boolean: True, is the intervale of time is inside of days. False, otherwise
     */

    function isInsideDaysJustified($a, $b, $days) {
        $difference = $a - $b;
        // calculate how many days have to make the quiz
        $days_difference = date('z', $difference) + 1;
        if ($days_difference <= $days) {
             return true;
        }
        return false;
    }
}