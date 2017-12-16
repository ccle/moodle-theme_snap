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
 * Snap ucla format renderer.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_snap\output;

defined('MOODLE_INTERNAL') || die();

class format_ucla_renderer extends \format_ucla_renderer {

    use format_section_trait;

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * Copied from base class method with following differences:
     *  - do not display section summary/edit link for section 0
     *  - always display section title
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn=0) {
        global $PAGE;

        $o = '';
        $currenttext = '';
        $sectionstyle = '';

        if (!empty($section)) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if (course_get_format($course)->is_section_current($section) ) {
                $sectionstyle = ' current';
            }
        }

        if (!empty($section)) {
            $o .= \html_writer::start_tag('li', array('id' => 'section-'.$section->section,
                'class' => 'section main clearfix'.$sectionstyle));
        } else {
            $o .= \html_writer::start_tag('li', array('id' => 'section-0',
                'class' => 'section main clearfix'.$sectionstyle));
        }
        
        if (!empty($section)) {
            // Print any external notices.
            $this->print_external_notices($section->section, $course);
        }

        // For site info, instead of printing section title/summary, just
        // print site info releated stuff instead.
        if (empty($section)) {
            $o .= \html_writer::start_tag('div', array('class' => 'content'));
            ob_start();
            $this->print_external_notices('0', $course);
            $this->print_section_zero_content();
            $o .= ob_get_contents();
            ob_end_clean();
        } else {
            $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
            $o .= \html_writer::tag('div', $leftcontent, array('class' => 'left side'));

            $o .= \html_writer::start_tag('div', array('class' => 'content'));

            // Start section header with section links!
            $o .= \html_writer::start_tag('div', array('class' => 'sectionheader'));
            $o .= $this->output->heading($this->section_title($section, $course), 3, 'sectionname');

            $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
            $o .= \html_writer::tag('div', $rightcontent, array('class' => 'right side',
                    'style' => 'position: relative; top: -40px;'));
            $o .= \html_writer::end_tag('div');
            // End section header.

            $o .= \html_writer::start_tag('div', array('class' => 'summary'));
            $o .= $this->format_summary_text($section);

            $o .= \html_writer::end_tag('div');

            $context = \context_course::instance($course->id);
            $o .= $this->section_availability_message($section,
                    has_capability('moodle/course:viewhiddensections', $context));
        }
        
        return $o;
    }
    
    /**
     * Output the html for a multiple section page.
     *
     * Copied from base class method with following differences:
     *  - print section 0 related stuff
     *  - always show section content, even if editing is off
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param array $mods used for print_section()
     * @param array $modnames used for print_section()
     * @param array $modnamesused used for print_section()
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $context = \context_course::instance($course->id);
        // Title with completion help icon.
        $completioninfo = new \completion_info($course);
        echo $completioninfo->display_help_icon();
        echo $this->output->heading($this->page_title(), 2, 'accesshide');

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course);

        // Now the list of sections..
        echo $this->start_section_list();

        // Section 0, aka "Site info".
        $thissection = '0';
        // Do not display section summary/header info for section 0.
        echo $this->section_header($thissection, $course, false);

        echo $this->courserenderer->course_section_cm_list($course, $thissection);

        if ($PAGE->user_is_editing()) {
            $output = $this->course_section_add_cm_control($course, 0);
            echo $output; // If $return argument in print_section_add_menus() set to false.
        }
        echo $this->section_footer();

        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context);
        for ($section = 1; $section <= $course->numsections; $section++) {
            // People who cannot view hidden sections are not allowed to see sections titles with no content.
            $nocontent = empty($sections[$section]->sequence) && empty($sections[$section]->summary);
            if (empty($nocontent) || $canviewhidden) {
                if (!empty($sections[$section])) {
                    $thissection = $sections[$section];
                } else {
                    // This will create a course section if it doesn't exist.
                    $thissection = get_fast_modinfo($course->id)->get_section_info($section);

                    // The returned section is only a bare database object rather than
                    // a section_info object - we will need at least the uservisible
                    // field in it.
                    $thissection->uservisible = true;
                    $thissection->availableinfo = null;
                    $thissection->showavailability = 0;
                }
                // Show the section if the user is permitted to access it, OR if it's not available
                // but showavailability is turned on (and there is some available info text).
                $showsection = $thissection->uservisible ||
                        ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo));
                if (!$showsection) {

                    unset($sections[$section]);
                    continue;
                }

                // Always show section content, even if editing is off.
                echo $this->section_header($thissection, $course, false);
                if ($thissection->uservisible) {
                    echo $this->courserenderer->course_section_cm_list($course, $thissection);

                    if ($PAGE->user_is_editing()) {
                        $output = $this->course_section_add_cm_control($course, $section);
                        echo $output;
                    }
                }
                echo $this->section_footer();
            }
            unset($sections[$section]);
        }

        if ($PAGE->user_is_editing() && !empty($sections)) {
            // Print stealth sections if present.
            $modinfo = get_fast_modinfo($course);
            foreach ($sections as $section => $thissection) {
                if (empty($modinfo->sections[$section])) {
                    continue;
                }
                echo $this->stealth_section_header($section);
                print_section($course, $thissection, $mods, $modnamesused);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

        } else {
            echo $this->end_section_list();
        }

    }

}