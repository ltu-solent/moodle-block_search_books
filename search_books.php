<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Search books main script.
 *
 * @package    block_search_books
 * @copyright  2009 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_search_books\helper;

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/glossary/lib.php');
require_once($CFG->libdir.'/searchlib.php');

$courseid = required_param('courseid', PARAM_INT);
$query    = required_param('bsquery', PARAM_NOTAGS);
$page     = optional_param('page', 0, PARAM_INT);

$PAGE->set_pagelayout('standard');
$PAGE->set_url($FULLME);

if (!$course = $DB->get_record('course', ['id' => $courseid])) {
    throw new moodle_exception('invalidcourseid');
}

require_course_login($course);

$strbooks = get_string('modulenameplural', 'book');
$searchbooks = get_string('bookssearch', 'block_search_books');
$searchresults = get_string('searchresults', 'block_search_books');

$PAGE->navbar->add($strbooks, new moodle_url('/mod/book/index.php', ['id' => $course->id]));
$PAGE->navbar->add($searchresults);

$PAGE->set_title($searchresults);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

// Process the query.
$query = trim(strip_tags($query));

$books = helper::get_readable_books($course);
$data = new stdClass();
$data->courseid = $course->id;
$formurl = new moodle_url('/blocks/search_books/search_books.php');
$data->formurl = $formurl->out();
$data->query = $query;
$data->books = [];
foreach ($books as $book) {
    $cm = get_coursemodule_from_instance("book", $book->id, $course->id);
    $context = context_module::instance($cm->id);
    if ($cm->visible || has_capability('moodle/course:viewhiddenactivities', $context)) {
        if (has_capability('mod/book:read', $context)) {
            $bookitem = new stdClass();
            $bookitem->bookid = $book->id;
            $url = new moodle_url('/mod/book/view.php', [
                'id' => $cm->id,
            ]);
            $bookitem->url = $url->out();
            $bookitem->name = s($book->name);
            $data->books[] = $bookitem;
        }
    }
}

echo $OUTPUT->render_from_template('block_search_books/searchbox', $data);

if (empty($query)) {
    echo "<h3>Please enter a search query.</h3>";
    echo $OUTPUT->footer();
    die();
}

// Launch the SQL quey.
$start = (helper::BOOKMAXRESULTSPERPAGE * $page);
['results' => $bookresults, 'total' => $countentries] = helper::search($query, $course, $start);

// Process $bookresults, if present.
$startindex = $start;
$endindex = $start + count($bookresults);

$countresults = $countentries;

if (empty($bookresults)) {
    echo '<br />';
    echo $OUTPUT->box(get_string("norecordsfound", "block_search_books"));
    echo $OUTPUT->footer();
    die();
}

// Print results page tip.
$pagingparams = [
    'bsquery' => urlencode(stripslashes($query)),
    'courseid' => $course->id,
];
$pagingurl = new moodle_url('/blocks/search_books/search_books.php', $pagingparams);
// The trailing & is required.
$pagebar = glossary_get_paging_bar($countresults, $page, helper::BOOKMAXRESULTSPERPAGE, $pagingurl->out() . '&');

// Iterate over results.
echo html_writer::tag('p', get_string('searchsummary', 'block_search_books', [
    'min' => ($startindex + 1),
    'max' => $endindex,
    'total' => $countresults,
    'query' => s($query),
]), ['class' => 'text-right']);

echo $pagebar;

$alist = [];
foreach ($bookresults as $entry) {
    $book = $DB->get_record('book', ['id' => $entry->bookid]);
    $cm = get_coursemodule_from_instance("book", $book->id, $course->id);
    $bookurl = new moodle_url('/mod/book/view.php', [
        'id' => $cm->id,
    ]);
    $chapterurl = new moodle_url('/mod/book/view.php', [
        'id' => $cm->id,
        'chapterid' => $entry->id,
    ], ':~:text=' . $query); // Highlight query.
    $item = html_writer::link($bookurl, format_string($book->name)) .
        '&nbsp;&raquo;&nbsp;' .
        html_writer::link($chapterurl, format_string($entry->title));
    $alist[] = $item;
}
echo html_writer::alist($alist);
echo $pagebar;

echo $OUTPUT->footer();
