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

use context_course;
use html_writer;
use moodle_url;
use stdClass;
use theme_snap\renderables\course_action_section_move;
use theme_snap\renderables\course_action_section_visibility;
use theme_snap\renderables\course_action_section_delete;
use theme_snap\renderables\course_action_section_highlight;
use theme_snap\renderables\course_section_navigation;

class format_ucla_renderer extends \format_ucla_renderer {

    // Need this so course_section_add_cm_control can use the trait method when
    // call_user_func_array() is used. Using parent:: did not work.
    use format_section_trait {
        course_section_add_cm_control as protected trait_course_section_add_cm_control;
    }

    /**
     * Renders HTML for the menus to add activities and resources to the current course
     *
     * Overridden to only display if in editing mode.
     *
     * @param stdClass $course
     * @param int $section relative section number (field course_sections.section)
     * @param int $sectionreturn The section to link back to
     * @param array $displayoptions additional display options, for example blocks add
     *     option 'inblock' => true, suggesting to display controls vertically
     * @return string
     */
    public function course_section_add_cm_control($course, $section, $sectionreturn = null, $displayoptions = array()) {
        global $USER;
        if ($USER->editing) {
            return $this->trait_course_section_add_cm_control($course, $section, $sectionreturn, $displayoptions);
        }
        return '';
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * Copied from base class method with following differences:
     *  - do not display section summary/edit link for section 0
     *  - display course info for section 0
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        global $PAGE, $USER, $CFG;

        $o = '';
        $sectionstyle = '';

        // We have to get the output renderer instead of using $this->output to ensure we get the non ajax version of
        // the renderer, even when via an AJAX request. The HTML returned has to be the same for all requests, even
        // ajax.
        $output = $PAGE->get_renderer('theme_snap', 'core', RENDERER_TARGET_GENERAL);

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current state-visible set-by-server';
            }
        } else if ($course->format == "topics" && $course->marker == 0) {
            $sectionstyle = ' state-visible set-by-server';
        }

        if ($this->is_section_conditional($section)) {
            $canviewhiddensections = has_capability(
                'moodle/course:viewhiddensections',
                context_course::instance($course->id)
            );
            if (!$section->uservisible || $canviewhiddensections) {
                $sectionstyle .= ' conditional';
            }
        }

        // SHAME - the tabindex is intefering with moodle js.
        // SHAME - Remove tabindex when editing menu is shown.
        $sectionarrayvars = array('id' => 'section-'.$section->section,
        'class' => 'section main clearfix'.$sectionstyle,
        'role' => 'article',
        'aria-label' => get_section_name($course, $section));
        if (!$PAGE->user_is_editing()) {
            $sectionarrayvars['tabindex'] = '-1';
        }

        $o .= html_writer::start_tag('li', $sectionarrayvars);
        $o .= html_writer::start_tag('div', array('class' => 'content'));

        // When not on a section page, we display the section titles except the general section if null.
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one.
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }

        $context = context_course::instance($course->id);

        $sectiontitle = get_section_name($course, $section);
        // Better first section title.
        // START UCLA MOD: CCLE-6985 - Improve display of modules
//        if ($sectiontitle == get_string('general') && $section->section == 0) {
//            $sectiontitle = get_string('introduction', 'theme_snap');
//        }
//
//        // Untitled topic title.
//        $testemptytitle = get_string('topic').' '.$section->section;
//        if ($sectiontitle == $testemptytitle && has_capability('moodle/course:update', $context)) {
//            $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
//            $o .= "<h2 class='sectionname'><a href='$url' title='".s(get_string('editcoursetopic', 'theme_snap'))."'>".get_string('defaulttopictitle', 'theme_snap')."</a></h2>";
//        } else {
//            $o .= $output->heading($sectiontitle, 2, 'sectionname' . $classes);
//        }
        if ($section->section == 0) {
            ob_start();
            $this->print_external_notices('0', $course);
            $this->print_section_zero_content();
            $o .= ob_get_contents();
            ob_end_clean();
        } else {
            // Untitled topic title.
            $testemptytitle = get_string('topic').' '.$section->section;
            if ($sectiontitle == $testemptytitle && has_capability('moodle/course:update', $context)) {
                $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
                $o .= "<h2 class='sectionname'><a href='$url' title='".s(get_string('editcoursetopic', 'theme_snap'))."'>".get_string('defaulttopictitle', 'theme_snap')."</a></h2>";
            } else {
                $o .= $output->heading($sectiontitle, 2, 'sectionname' . $classes);
            }
        }
        // END UCLA MOD: CCLE-6985

        // Section drop zone.
        $caneditcourse = has_capability('moodle/course:update', $context);
        if ($caneditcourse && $section->section != 0) {
            $o .= "<a class='snap-drop section-drop' data-title='".
                    s($sectiontitle)."' href='#'>_</a>";
        }

        // Section editing commands.
        $sectiontoolsarray = $this->section_edit_controls($course, $section, $sectionreturn);

        // START UCLA MOD: CCLE-6985 - Improve Editing mode on/off
        //if (has_capability('moodle/course:update', $context)) {
        if (has_capability('moodle/course:update', $context) && $USER->editing) {
        // END UCLA MOD: CCLE-6985
            if (!empty($sectiontoolsarray)) {
                $sectiontools = implode(' ', $sectiontoolsarray);
                $o .= html_writer::tag('div', $sectiontools, array(
                    'class' => 'snap-section-editing actions',
                    'role' => 'region',
                    'aria-label' => get_string('topicactions', 'theme_snap')
                ));
            }
        }

        // Current section message.
        $o .= '<span class="snap-current-tag">'.get_string('current', 'theme_snap').'</span>';

        // Draft message.
        $o .= '<div class="snap-draft-tag">'.get_string('draft', 'theme_snap').'</div>';

        // Availabiliy message.
        $conditionalicon = '<img aria-hidden="true" role="presentation" class="svg-icon" src="'.$output->pix_url('conditional', 'theme').'" />';
        $conditionalmessage = $this->section_availability_message($section,
            has_capability('moodle/course:viewhiddensections', $context));
        if ($conditionalmessage !== '') {
            $o .= '<div class="snap-conditional-tag">'.$conditionalicon.$conditionalmessage.'</div>';
        }

        // Section summary/body text.
        $o .= "<div class='summary'>";
        $summarytext = $this->format_summary_text($section);

        $canupdatecourse = has_capability('moodle/course:update', $context);

        // START UCLA MOD: CCLE-6985 - Improve display of modules
//        // Welcome message when no summary text.
//        if (empty($summarytext) && $canupdatecourse) {
//            $summarytext = "<p>".get_string('defaultsummary', 'theme_snap')."</p>";
//            if ($section->section == 0) {
//                $editorname = format_string(fullname($USER));
//                $summarytext = "<p>".get_string('defaultintrosummary', 'theme_snap', $editorname)."</p>";
//            }
//        }
        // END UCLA MOD: CCLE-6985

        $o .= $summarytext;
        // START UCLA MOD: CCLE-6985 - Improve display of modules
        //if ($canupdatecourse) {
        if ($canupdatecourse && $section->section != 0 && $USER->editing) {
        // END UCLA MOD: CCLE-6985
            $url = new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn));
            $icon = '<img aria-hidden="true" role="presentation" class="svg-icon" src="'.$this->output->pix_url('pencil', 'theme').'" /><br/>';
            $o .= '<a href="'.$url.'" class="edit-summary">'.$icon.get_string('editcoursetopic', 'theme_snap'). '</a>';
        }
        $o .= "</div>";

        return $o;
    }
    
}