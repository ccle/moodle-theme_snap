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
 * Snap TOC renderer.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\output;

use theme_snap\renderables\course_toc;

defined('MOODLE_INTERNAL') || die();

class toc_renderer extends \core_renderer {

    /**
     * @return bool|string
     * @throws \moodle_exception
     */
    public function course_toc() {
        global $COURSE, $CFG;
        $coursetoc = new course_toc();
        $coursetoc->syllabusurl = new \moodle_url('/local/ucla_syllabus/index.php', ['id' => $COURSE->id]);
        
        // Add link for syllabus (if needed).
        include_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');
        if (class_exists('ucla_syllabus_manager')) {
            $uclasyllabusmanager = new \ucla_syllabus_manager($COURSE);
            $syllabusname = $uclasyllabusmanager->get_syllabus_name();
            if (!empty($syllabusname)) {
                // Syllabus exists, so add to section links.
                $coursetoc->syllabus = $syllabusname;
            }
        }
        return $this->render_from_template('theme_snap/course_toc', $coursetoc);
    }

    /**
     * get course image
     *
     * @return bool|\moodle_url
     */
    public function get_course_image() {
        global $COURSE;

        return \theme_snap\local::course_coverimage_url($COURSE->id);
    }
}
