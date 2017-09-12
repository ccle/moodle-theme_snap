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
 * Layout - nav.
 * This layout is baed on a moodle site index.php file but has been adapted to show news items in a different
 * way.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use theme_snap\renderables\settings_link;
use theme_snap\renderables\bb_dashboard_link;

?>
<header id='mr-nav' class='clearfix moodle-has-zindex'>
<div class="pull-right">
<?php
    if (class_exists('local_geniusws\navigation')) {
        $bblink = new bb_dashboard_link();
        echo $OUTPUT->render($bblink);
    }

    echo html_writer::start_div("dropdown pull-left", array('id' => 'helpdropdown', 'style' => 'margin-right:8px;'));

    echo html_writer::start_tag('button', array('class' => 'needhelpbtn', 'data-toggle' => 'dropdown'
    ));
    echo get_string('help_n_feedback', 'theme_uclashared') . ' ';
    echo html_writer::span('', 'caret');
    echo html_writer::end_tag('button');

    $menuitems = custom_menu::convert_text_to_menu_nodes($CFG->custommenuitems, null);

    $helplinks = array();
    foreach ($menuitems as $item) {
        $helplinks[] = html_writer::link($item->get_url(), $item->get_text());
    }

    echo html_writer::alist($helplinks, array('class' => 'dropdown-menu'));
    echo html_writer::end_div();

    echo $OUTPUT->fixed_menu();
    echo core_renderer::search_box();
    $settingslink = new settings_link();
    echo $OUTPUT->render($settingslink);
?>
</div>

<?php

    // START UCLA MOD: CCLE-6832 - Update the CSS for the SNAP theme to match UCLA colors.
    // Always show the UCLA and CCLE logos.
//    $sitefullname = format_string($SITE->fullname);

//    $attrs = array(
//        'aria-label' => get_string('home', 'theme_snap'),
//        'id' => 'snap-home',
//        'title' => $sitefullname,
//    );

//    if (!empty($PAGE->theme->settings->logo)) {
//        $sitefullname = '<span class="sr-only">'.format_string($SITE->fullname).'</span>';
//        $attrs['class'] = 'logo';
//    }

    $navbarlogos = [
        'uclalogo' => $OUTPUT->pix_url('ucla-logo', 'theme'),
        'cclelogo' => $OUTPUT->pix_url('ccle-logo', 'theme')
    ];
    $sitefullname = $OUTPUT->render_from_template('theme_snap/navbar_logo', $navbarlogos);
    $attrs = array(
        'aria-label' => get_string('home', 'theme_snap'),
        'id' => 'snap-home'
    );
    // END UCLA MOD: CCLE-6832.

    echo html_writer::link($CFG->wwwroot, $sitefullname, $attrs);
?>
</header>
