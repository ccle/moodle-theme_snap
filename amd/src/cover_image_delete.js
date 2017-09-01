/**
 * This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   theme_snap
 * @copyright Copyright (c) 2015 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/log', 'core/ajax', 'core/notification', 'theme_snap/ajax_notification'],
    function($, log, ajax, notification, ajaxNotify) {

        /**
         * Main function
         * @param {string} courseshortname
         */
        var deleteCoverImage = function(courseshortname) {

            // Take a backup of what the current background image url is (if any).
            $('#page-header').data('servercoverfile', $('#page-header').css('background-image'));

            alert(); 
            
            var file, filedata;
            $('#removecoverimage').click(function(e) {
                e.preventDefault();
                $(this).removeClass('state-visible');
                $('label[for="snap-cover-delete"]').addClass('state-visible');
            });

            /**
             * First state - image selection button visible.
             */
            var state1 = function() {
                $('label[for="snap-cover-delete"] .loadingstat').remove();
                $('#snap-changecoverimageconfirmation-delete').removeClass('state-visible');
                $('label[for="snap-cover-delete"]').addClass('state-visible');
                $('#snap-coverfiles-delete').val('');
            };

            /**
             * Second state - confirm / cancel buttons visible.
             */
            var state2 = function() {
                $('#snap-changecoverimageconfirmation-delete').removeClass('disabled');
                $('label[for="snap-cover-delete"]').removeClass('state-visible');
                $('label[for="snap-coverfiles"]').removeClass('state-visible');
                $('#snap-changecoverimageconfirmation-delete').addClass('state-visible');
                $('body').removeClass('cover-image-change');
            };

            $('#snap-cover-delete').on('click', function(e) {
                $('body').addClass('cover-image-change');

                $('label[for="snap-coverfiles"]').append(
                    '<span class="loadingstat spinner-three-quarters">' +
                    M.util.get_string('loading', 'theme_snap') +
                    '</span>'
                );
                
                var filedata = e.target.result;
                $('#page-header').css('background-image', 'url(' + filedata + ')');
                
                state2();
            });
            $('#snap-changecoverimageconfirmation-delete .ok').click(function(){

                if ($(this).parent().hasClass('disabled')) {
                    return;
                }

                $('#snap-changecoverimageconfirmation-delete .ok').append(
                    '<span class="loadingstat spinner-three-quarters">' +
                    M.util.get_string('loading', 'theme_snap') +
                    '</span>'
                );
                $('#snap-changecoverimageconfirmation-delete').addClass('disabled');

                ajax.call([
                    {
                        methodname: 'theme_snap_cover_image',
                        args: {imagefilename: '', imagedata:'', courseshortname:courseshortname},
                        done: function(response) {
                            state1();
                            $('#snap-changecoverimageconfirmation-delete .ok .loadingstat').remove();
                        },
                        fail: function(response) {
                            state1();
                            $('#snap-changecoverimageconfirmation-delete .ok .loadingstat').remove();
                            ajaxNotify.ifErrorShowBestMsg(response);
                        }
                    }
                ], true, true);

            });
            $('#snap-changecoverimageconfirmation-delete .cancel').click(function(){

                if ($(this).parent().hasClass('disabled')) {
                    return;
                }

                $('#page-header').css('background-image', $('#page-header').data('servercoverfile'));
                state1();
            });
            $('#snap-coverimagecontrol').addClass('snap-js-enabled');
        };
        return deleteCoverImage;
    }
);
