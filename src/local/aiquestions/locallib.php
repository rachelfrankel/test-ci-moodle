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
 * Plugin administration pages are defined here.
 *
 * @package     local_aiquestions
 * @category    admin
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Save the upload file in moodle.
 *
 * @param int $courseid course id
 * @param $userfile $story text of the story
 * @return string the new path of the upload file
 */

function upload_file($courseid, $userfile){
    global $CFG;

    require_once($CFG->dirroot . '/local/aiquestions/classes/story_form.php');
    
    $context = context_course::instance($courseid);
    $itemid = $userfile;
    file_save_draft_area_files($itemid, $context->id, 'local_aiquestions', 'attachment', $itemid);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_aiquestions', 'attachment', $itemid);
    $filepath = '';

    foreach ($files as $file) {
        if ($file->get_filename() === '.') {
            continue;
        }
        $filepath .= $CFG->dataroot . '/temp/filestorage/' . $file->get_filename();
        $file->copy_content_to($filepath);
    }
    return $filepath;
}
/**
 * Get questions from the API.
 *
 * @param int $courseid course id
 * @param string $story text of the story
 * @param int $numofquestions number of questions to generate
 * @param bool $idiot 1 if ChatGPT is an idiot, 0 if not
 * @return object questions of generated questions
 */
function local_aiquestions_get_questions($courseid, $numofquestions, $userfile, $idiot = 1) {
    $filepath = upload_file($courseid, $userfile);
    if(!(basename($filepath))){
        return false;
    }
    $info = new SplFileInfo($filepath);
    $ext = $info->getExtension();
    if($ext=="docx"){
        global $CFG;
        require_once( __DIR__ . '/convert_word_file_to_pdf.php');
        $outputPath = dirname($filepath) . '/' . basename($filepath,'docx') . 'pdf';
        if(convert_word_to_pdf($filepath,$outputPath)){
            $filepath = $outputPath;
        }
        else{
            return false;
        }
    }

    $language = get_config('local_aiquestions', 'language');
    $savelang = current_language();
    force_current_language('en');
    $languages = get_string_manager()->get_list_of_languages();
    $language = $languages[$language];
    force_current_language($savelang);

    $key = get_config('local_aiquestions', 'key');
    $url = 'https://api.chatpdf.com/v1/sources/add-file';
    $cfile = new \CURLFile($filepath);
    $headers = array(
        "x-api-key:  $key"
    );
    $post = array('file' => $cfile);
   
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if(isset($result['sourceId'])){
        $sourceId = $result['sourceId'];
    }
    else{
        return false;
    }
    
    $explanation = " Please write $numofquestions multiple choice question at different difficulty levels in $language language";    
    $explanation .= " in GIFT format about the file, ";
    $explanation .= " GIFT format use equal sign for right answer and tilde sign for wrong answer at the beginning of answers.";
    $explanation .= " For example: '::Question title { =right answer ~wrong answer ~wrong answer ~wrong answer }' ";
    $explanation .= " In addition, one line before each question, add a tag in gift format.";   
    $explanation .= " with two slashes and then in square brackets the name of the tag";
    $explanation .= " Depending on the difficulty level of the question, that is, the number after the adpq indicates the level of difficulty of the question.";
    $explanation .= " For example on an easy question:// [tag:adpq_10]";
    $explanation .= " About a medium question :// [tag:adpq_20]";
    $explanation .= " And on a hard question :// [tag:adpq_30]";
    $explanation .= " Please have a blank line between questions. ";
    $explanation .= " Please do not write any sentence before the questions and answers";
    $explanation .= " I need your answer to be only multiple choice tagged questions in gift format at different difficulty levels!"; 
    $explanation .= " It is extremely important that the questions will be in $language language ,";
    $explanation .= " and pay attention that a question that is tagged with a high number will be a question at a really difficult level to understand";
    if ($idiot == 1) {
        $explanation .= " Write the questions in the right format! ";
        $explanation .= " Do not forget any equal or tilde sign !";
    }
    
    $url = 'https://api.chatpdf.com/v1/chats/message';
    $data = [
        'sourceId' => $sourceId,
        'messages' => [
            [
                'role' => 'user',
                'content' => $explanation,
            ],
        ],
    ];
    $jsonData = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $key,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $questions = new stdClass(); // The questions object.
    $contentFile = file_get_contents($filepath);
    if (isset($result["content"])) {
        $questions->text = $result["content"];
        $questions->prompt = $contentFile;
    } else {
        $questions = $result;
    }
    return $questions;
}
/**
 * Create questions from data got from ChatGPT output.
 *
 * @param int $courseid course id
 * @param string $gift questions in GIFT format
 * @param int $numofquestions number of questions to generate
 * @param int $userid user id
 * @return array of objects of created questions
 */
function local_aiquestions_create_questions($courseid, $gift, $numofquestions, $userid,$createquestionsby) {
    global $CFG, $USER, $DB;
    
    require_once($CFG->libdir . '/questionlib.php');
    require_once($CFG->dirroot . '/question/format.php');
    require_once($CFG->dirroot . '/question/format/gift/format.php');

    $qformat = new \qformat_gift();

    $coursecontext = \context_course::instance($courseid);

    // Use existing questions category for quiz or create the defaults.
    $contexts = new core_question\local\bank\question_edit_contexts($coursecontext);

    if (!$category = $DB->get_record('question_categories', ['contextid' => $coursecontext->id, 'sortorder' => 999])) {
        $category = question_make_default_categories($contexts->all());
    }

    $giftfile = $CFG->dataroot . '/temp/filestorage/questiontag.txt';
    $fp = fopen($giftfile,"wb");
    fwrite($fp,$gift);
    fclose($fp);
    $qformat->setCategory($category);
    $qformat->setContexts($contexts->having_one_edit_tab_cap('import'));
    $qformat->setCourse($COURSE);
    $qformat->setFilename($giftfile);
    $qformat->setRealfilename(basename($giftfile));
    return $qformat->importprocess();
}
/**
 * Escape json.
 *
 * @param string $value json to escape
 * @return string result escaped json
 */
function local_aiquestions_escape_json($value) {
    $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
    $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
    $result = str_replace($escapers, $replacements, $value);
    return $result;
}
/**
 * Check if the gift format is valid.
 *
 * @param string $gift questions in GIFT format
 * @return bool true if valid, false if not
 */
function local_aiquestions_check_gift($gift) {
    $questions = explode("\n\n", $gift);
    foreach ($questions as $question) {       
        $qa = str_replace("\n", "", $question);
        if(!(str_starts_with($qa, '// [tag:adpq_'))){
            return false;  
        }
        if (!(preg_match('/[א-ת]/', $qa))){
            return false;        
        }
        preg_match('/::(.*)\{/', $qa, $matches);
        if (isset($matches[1])) {
            $qlength = strlen($matches[1]);
        } else {
            return false;
            // Error : Question title not found.
        }
        if ($qlength < 10) {
            return false;
            // Error : Question length too short.
        }
        preg_match('/\{(.*)\}/', $qa, $matches);
        if (isset($matches[1])) {
            $wrongs = substr_count($matches[1], "~");
            $right = substr_count($matches[1], "=");
        } else {
            return false;
            // Error : Answers not found.
        }
        if ($wrongs != 3 || $right != 1) {
            return false;
            // Error : There is no single right answers or no 3 wrong answers.
        }
    }
    return true;
}
