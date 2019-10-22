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

defined('MOODLE_INTERNAL') || die();

class block_search_books extends block_base {
    function init() {
        $this->title = get_string('pluginname','block_search_books');
    }

    function has_config() {return false;}

    function applicable_formats() {
// SSU_AMEND START - BOOK SEARCH
        //return (array('site-index' => true, 'course-view-weeks' => true, 'course-view-topics' => true));
        return (array('site-index' => true, 'course-view-weeks' => true, 'course-view-topics' => true, 'course-view-nonumbers' => true, 'onetopic' => true));
// SSU_AMEND END
    }

    function get_content() {
        global $CFG, $USER, $COURSE, $DB, $OUTPUT;
// SSU_AMEND START - BOOK SEARCH
		$this->page->requires->js_call_amd('block_search_books/checkbox', 'init');
// SSU_AMEND END

        if ($this->content !== NULL) {
            return $this->content;
        }

        if ($COURSE->id == $this->page->course->id) {
            $course = $COURSE;
        } else {
            $course = $DB->get_record('course', array('id' => $this->page->course->id));
        }

        // Course not found, we won't do anything in the block
        if (empty($course)) {
            return '';
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        $searchbooks = get_string('bookssearch', 'block_search_books');

// SSU_AMEND START - BOOK SEARCH
		$books = get_all_instances_in_course('book', $course);

	// $this->content->text  = '<div class="searchform">';
	// $this->content->text .= '<form action="' . $CFG->wwwroot . '/blocks/search_books/search_books.php" style="display:inline">';
	// $this->content->text .= '<fieldset class="invisiblefieldset">';
	// $this->content->text .= '<input name="courseid" type="hidden" value="' . $course->id . '" />';
	// $this->content->text .= '<input name="page" type="hidden" value="0" />';
	// $this->content->text .= '<label class="accesshide" for="searchbooksquery">' . $searchbooks . '</label>';
	// $this->content->text .= '<input id="searchbooksquery" name="bsquery" size="20" maxlength="255" value="" />';
	// $this->content->text .= '<br /><input type="submit" name="submit" value="' . $searchbooks . '"/>';
	// $this->content->text .= '</fieldset></form></div>';

   // return $this->content;

	  if(count($books) > 0){
// SSU_AMEND END

        $this->content->text  = '<div class="searchform">';
        $this->content->text .= '<form action="' . $CFG->wwwroot . '/blocks/search_books/search_books.php" method="post" style="display:inline">';
        $this->content->text .= '<fieldset class="invisiblefieldset">';
        $this->content->text .= '<input name="courseid" type="hidden" value="' . $course->id . '" />';
        $this->content->text .= '<input name="page" type="hidden" value="0" />';

// SSU_AMEND START - BOOK SEARCH
    		$this->content->text .= '<div style="text-align:left">
                                        <h3><a id="toggle" href="#">Advanced search...</a></h3>';
    		$this->content->text .= '<div id="checkholder">';
    		$this->content->text .= '<p id="intro">Select individual books to narrow your search results:</p>';

    		foreach ($books as $book) {
    			$cm = get_coursemodule_from_instance("book", $book->id, $course->id);
    			$context = context_module::instance($cm->id);
    			if ($cm->visible || has_capability('moodle/course:viewhiddenactivities', $context)) {
    				if (has_capability('mod/book:read', $context)) {
    					$bookids[] = $book->id;
    					$this->content->text .= '<label><input type="checkbox" class="checkbox1" name="check_book[]" value="'. $book->id . '"/><a href="'.$CFG->wwwroot.'/mod/book/view.php?id='.$cm->id.'" target="_blank">' . $book->name . '</a></label><br />';
    				}
    			}
    		}

        $this->content->text .= '<label><input type="checkbox" name="check" id="check">Select/unselect all</label><br />';
        $this->content->text .= '</div>';
        $this->content->text .= '</div>';

        $this->content->text .= '<label class="accesshide" for="searchbooksquery">' . $searchbooks . '</label>';
        $this->content->text .= $OUTPUT->image_icon('icon', get_string('pluginname', 'book'), 'book') . '<input type="text" id="searchbooksquery" name="bsquery" size="20" maxlength="255" value="" />';
        $this->content->text .= '<br /><input type="submit" name="submit" value="' . $searchbooks . '"/>';
        $this->content->text .= '</fieldset></form></div>';

        return $this->content;

      }else{
          $this->content->text .= '<p id="intro">There are no books in this course</p>';
      }
// SSU_AMEND END

    }
}
