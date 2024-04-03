<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.
/**
 * Adhoc task for questions generation.
 *
 * @package     local_aiquestions
 * @category    admin
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_aiquestions\task;
defined('MOODLE_INTERNAL') || die();
/**
 * The question generator adhoc task.
 *
 * @package     local_aiquestions
 * @category    admin
 */
class questions extends \core\task\adhoc_task {
    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute() {
        global $DB, $CFG;
        require_once(__DIR__ . '/../../locallib.php');
        // Read numoftries from settings.
        $numoftries = get_config('local_aiquestions', 'numoftries');
        // Get the data from the task.
        $data = $this->get_custom_data();
        $courseid = $data->courseid;
        $userid = $data->userid;
        $uniqid = $data->uniqid;
        $numofquestions = $data->numofquestions;
        $userfile = $data->userfile;

        // Create the DB entry.
        $dbrecord = new \stdClass();
        $dbrecord->course = $courseid;
        $dbrecord->numoftries = $numoftries;
        $dbrecord->userid = $userid;
        $dbrecord->timecreated = time();
        $dbrecord->timemodified = time();
        $dbrecord->tries = 0;
        $dbrecord->uniqid = $uniqid;
        $dbrecord->gift = '';
        $dbrecord->success = '';
        $inserted = $DB->insert_record('local_aiquestions', $dbrecord);
        $created = false;
        $i = 1;
        $error = ''; // Error message.
        $update = new \stdClass();
       
        while (!$created && $i < $numoftries) {
            // First update DB on tries.
            $update->id = $inserted;
            $update->tries = $i;
            $update->datemodified = time();
            $DB->update_record('local_aiquestions', $update);
            // Get questions from ChatPDF API.
            $questions = \local_aiquestions_get_questions($courseid, $numofquestions, $userfile);
            // Print error message of ChatPDF API (if there are).           
            if(property_exists($questions,"error")){
                $error .= $questions["error"];
                // Print error message to cron/adhoc output.
                echo "[local_aiquestions] Error : " . $error . "\n";           
            }
            
            // Check gift format.
            if (\local_aiquestions_check_gift($questions->text)) {
                // Create the questions, return an array of objetcs of the created questions.
                $created = \local_aiquestions_create_questions($courseid, $questions->text, $numofquestions, $userid,$createquestionsby);
                if($created){
                // Insert success creation info to DB.
                $update->id = $inserted;
                $update->gift = $questions->text;
                $update->tries = $i;
                $update->success = 1;
                $update->datemodified = time();
                $DB->update_record('local_aiquestions', $update);
                }
            }
            $i++;
        }
        // If questions were not created.
        if (!$created) {
            // Insert error info to DB.
            $update = new \stdClass();
            $update->id = $inserted;
            $update->tries = $i;
            $update->timemodified = time();
            $update->success = 0;
            $DB->update_record('local_aiquestions', $update);
        }
        // Print error message.
        // It will be shown on cron/adhoc output (file/whatever).
        if ($error != '') {
            echo '[local_aiquestions adhoc_task]' . $error;
        }
    }
}
