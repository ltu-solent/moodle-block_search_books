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
 * Search books block main file.
 *
 * This block enables searching within all the books in a given course.
 *
 * @package    block_search_books
 * @copyright  2009 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_search_books\helper;

/**
 * Search books block
 */
class block_search_books extends block_base {
    /**
     * Initialise the block
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_search_books');
    }

    /**
     * And local config settings for the teacher?
     *
     * @return bool
     */
    public function has_config(): bool {
        return false;
    }

    /**
     * List of formats the block appears on
     *
     * @return array
     */
    public function applicable_formats(): array {
        // SSU_AMEND_START: Include our course format.
        return [
            'site-index' => true,
            'course-view-weeks' => true,
            'course-view-topics' => true,
            'course-view-nonumbers' => true,
            'course-view-onetopic' => true,
        ];
        // SSU_AMEND_END.
    }

    /**
     * Get block content.
     *
     * @return stdClass|null
     */
    public function get_content(): ?stdClass {
        global $DB, $COURSE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        if ($COURSE->id == $this->page->course->id) {
            $course = $COURSE;
        } else {
            $course = $DB->get_record('course', ['id' => $this->page->course->id]);
        }

        // Course not found, we won't do anything in the block.
        if (empty($course)) {
            return null;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        $books = helper::get_readable_books($course);

        if (count($books) == 0) {
            $this->content->text .= '<p id="intro">There are no books in this course</p>';
            return $this->content;
        }
        $data = new stdClass();
        $data->courseid = $course->id;
        $formurl = new moodle_url('/blocks/search_books/search_books.php');
        $data->formurl = $formurl->out();
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

        $this->content->text = $OUTPUT->render_from_template('block_search_books/searchbox', $data);

        return $this->content;
    }
}
