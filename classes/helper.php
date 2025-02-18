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

namespace block_search_books;

use context_module;
use search_lexer;
use search_parser;

/**
 * Class helper
 *
 * @package    block_search_books
 * @copyright  2025 Southampton Solent University {@link https://www.solent.ac.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /** @var int Max number of results per page */
    public const BOOKMAXRESULTSPERPAGE = 3;

    /**
     * Search for books
     *
     * @param string $query
     * @param \stdClass $course
     * @param int $offset
     * @return array{results: array, total: int}
     */
    public static function search($query, $course, $offset): ?array {

        global $CFG, $USER, $DB;

        // Perform the search only in books fulfilling mod/book:read and (visible or moodle/course:viewhiddenactivities).
        $books = self::get_readable_books($course);
        $bookids = array_keys($books);

        // Transform the search query into safe SQL queries.
        $parser = new search_parser();
        $lexer = new search_lexer($parser);
        if (!$lexer->parse($query)) {
            return ['results' => [], 'total' => 0];
        }

        $parsearray = $parser->get_parsed_array();
        [$messagesearch, $msparams] =
            search_generate_SQL($parsearray, 'bc.title', 'bc.content', null, null, null, null, null, null);

        // Main query, only to allowed books and not hidden chapters.
        $selectsql = "SELECT DISTINCT bc.*";
        $fromsql   = "  FROM {book_chapters} bc, {book} b";

        [$insql, $inparams] = $DB->get_in_or_equal($bookids, SQL_PARAMS_NAMED);

        $params = array_merge(['courseid' => $course->id], $inparams, $msparams);

        $wheresql  = "  WHERE b.course = :courseid
                            AND b.id $insql
                            AND bc.bookid = b.id
                            AND bc.hidden = 0
                            AND $messagesearch ";
        $ordersql  = "  ORDER BY bc.bookid, bc.pagenum";

        // Set page limits.
        $limitfrom = $offset;
        $limitnum = 0;
        if ( $offset >= 0 ) {
            $limitnum = self::BOOKMAXRESULTSPERPAGE;
        }
        $countentries = $DB->count_records_sql("select count(*) $fromsql $wheresql", $params);

        $allentries = $DB->get_records_sql("$selectsql $fromsql $wheresql $ordersql", $params, $limitfrom, $limitnum);
        return ['results' => $allentries, 'total' => $countentries];
    }

    /**
     * return a list of book ids for the books which can be read/viewed
     *
     * @param \stdClass $course  course object
     * @return array of book ids
     */
    public static function get_readable_books($course) {
        $books = get_all_instances_in_course('book', $course);
        $bookids = [];
        foreach ($books as $book) {
            $cm = get_coursemodule_from_instance("book", $book->id, $course->id);
            $context = context_module::instance($cm->id);
            if (($cm->visible || has_capability('moodle/course:viewhiddenactivities', $context)) &&
                has_capability('mod/book:read', $context)
                ) {
                $bookids[$book->id] = $book;
            }
        }
        return $bookids;
    }
}
