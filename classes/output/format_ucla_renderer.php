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

//            if ($PAGE->user_is_editing()) {
//                $o .= $this->get_jit_links($section->section);
//            }

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
//        global $PAGE;
//        echo "snap format ucla print multiple\n";
//        $context = \context_course::instance($course->id);
//        // Title with completion help icon.
//        $completioninfo = new \completion_info($course);
//        echo $completioninfo->display_help_icon();
//        echo $this->output->heading($this->page_title(), 2, 'accesshide');
//
//        // Copy activity clipboard..
//        echo $this->course_activity_clipboard($course);
//
//        // Now the list of sections..
//        echo $this->start_section_list();
//
//        // Section 0, aka "Site info".
//        $thissection = '0';
//        // Do not display section summary/header info for section 0.
//        echo $this->section_header($thissection, $course, false);
//
//        echo $this->courserenderer->course_section_cm_list($course, $thissection);
//
//        if ($PAGE->user_is_editing()) {
//            //$output = $this->courserenderer->course_section_add_cm_control($course, 0);
//            $output = $this->course_section_add_cm_control($course, 0);
//            echo $output; // If $return argument in print_section_add_menus() set to false.
//        }
//        echo $this->section_footer();
//
//        $canviewhidden = has_capability('moodle/course:viewhiddensections', $context);
//        for ($section = 1; $section <= $course->numsections; $section++) {
//            // People who cannot view hidden sections are not allowed to see sections titles with no content.
//            $nocontent = empty($sections[$section]->sequence) && empty($sections[$section]->summary);
//            if (empty($nocontent) || $canviewhidden) {
//                if (!empty($sections[$section])) {
//                    $thissection = $sections[$section];
//                } else {
//                    // This will create a course section if it doesn't exist.
//                    $thissection = get_fast_modinfo($course->id)->get_section_info($section);
//
//                    // The returned section is only a bare database object rather than
//                    // a section_info object - we will need at least the uservisible
//                    // field in it.
//                    $thissection->uservisible = true;
//                    $thissection->availableinfo = null;
//                    $thissection->showavailability = 0;
//                }
//                // Show the section if the user is permitted to access it, OR if it's not available
//                // but showavailability is turned on (and there is some available info text).
//                $showsection = $thissection->uservisible ||
//                        ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo));
//                if (!$showsection) {
//
//                    unset($sections[$section]);
//                    continue;
//                }
//
//                // Always show section content, even if editing is off.
//                echo $this->section_header($thissection, $course, false);
//                if ($thissection->uservisible) {
//                    echo $this->courserenderer->course_section_cm_list($course, $thissection);
//
//                    if ($PAGE->user_is_editing()) {
//                        $output = $this->courserenderer->course_section_add_cm_control($course, $section);
//                        echo $output;
//                    }
//                }
//                echo $this->section_footer();
//            }
//            unset($sections[$section]);
//        }
//
//        if ($PAGE->user_is_editing() && !empty($sections)) {
//            // Print stealth sections if present.
//            $modinfo = get_fast_modinfo($course);
//            foreach ($sections as $section => $thissection) {
//                if (empty($modinfo->sections[$section])) {
//                    continue;
//                }
//                echo $this->stealth_section_header($section);
//                print_section($course, $thissection, $mods, $modnamesused);
//                echo $this->stealth_section_footer();
//            }
//
//            echo $this->end_section_list();
//
//        } else {
//            echo $this->end_section_list();
//        }
        
        //echo "traits print multiple\n";
        global $PAGE;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = \context_course::instance($course->id);

        // Copy activity clipboard..
        echo $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        echo $this->start_section_list();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {

            if ($section > $course->numsections) {
                // Activities inside this section are 'orphaned', this section will be printed as 'stealth' below.
                continue;
            }

            $canviewhidden = has_capability('moodle/course:viewhiddensections', \context_course::instance($course->id));

            // Student check.
            if (!$canviewhidden) {
                $conditional = $this->is_section_conditional($thissection);
                // HIDDEN SECTION - If nothing in show hidden sections, and course section is not visible - don't print.
                if (!$conditional && $course->hiddensections && !$thissection->visible) {
                    continue;
                }
                // CONDITIONAL SECTIONS - If its not visible to the user and we have no info why - don't print.
                if ($conditional && !$thissection->uservisible && !$thissection->availableinfo) {
                    continue;
                }
                // If hidden sections are collapsed - print a fake li.
                if (!$conditional && !$course->hiddensections && !$thissection->visible) {
                    echo $this->section_hidden($section);
                    continue;
                }
            }

            // Output course section.
            echo $this->new_course_section($course, $thissection, $modinfo);
        }

        if ($PAGE->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $course->numsections or empty($modinfo->sections[$section])) {
                    // This is not stealth section or it is empty.
                    continue;
                }
                echo $this->stealth_section_header($section);
                // Don't print add resources/activities of 'stealth' sections.
                echo $this->stealth_section_footer();
            }
        }
        echo $this->end_section_list();

    }
    
    public function new_course_section($course, $section, $modinfo) {
        global $PAGE;

        $output = $this->section_header($section, $course, false, 0);

        // GThomas 21st Dec 2015 - Only output assets inside section if the section is user visible.
        // Otherwise you can see them, click on them and it takes you to an error page complaining that they
        // are restricted!
        if ($section->uservisible) {
            $output .= $this->courserenderer->course_section_cm_list($course, $section, 0);
            echo 'class: ' . get_class($this->courserenderer) . "\n";
            // SLamour Aug 2015 - make add asset visible without turning editing on
            // N.B. this function handles the can edit permissions.
            $output .= $this->course_section_add_cm_control($course, $section->section, 0);
        }
        if (!$PAGE->user_is_editing()) {
            $output .= $this->render(new \theme_snap\renderables\course_section_navigation($course, $modinfo->get_section_info_all(), $section->section));
        }
        $output .= $this->section_footer();
        return $output;
    }

}