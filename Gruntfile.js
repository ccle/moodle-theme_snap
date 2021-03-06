/**
 * Gruntfile for compiling theme_bootstrap .less files.
 *
 * This file configures tasks to be run by Grunt
 * http://gruntjs.com/ for the current theme.
 *
 * Requirements:
 * nodejs, npm, grunt-cli.
 *
 * Installation:
 * node and npm: instructions at http://nodejs.org/
 * grunt-cli: `[sudo] npm install -g grunt-cli`
 * node dependencies: run `npm install` in the root directory.
 *
 * Usage:
 * Default behaviour is to watch all .less files and compile
 * into compressed CSS when a change is detected to any and then
 * clear the theme's caches. Invoke either `grunt` or `grunt watch`
 * in the theme's root directory.
 *
 * To separately compile only moodle or editor .less files
 * run `grunt less:moodle` or `grunt less:editor` respectively.
 *
 * To only clear the theme caches invoke `grunt exec:decache` in
 * the theme's root directory.
 *
 * @package theme
 * @subpackage bootstrap
 * @author Joby Harding www.iamjoby.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

module.exports = function(grunt) {

    // We need to include the core Moodle grunt file too, otherwise we can't run tasks like "amd".
    require("grunt-load-gruntfile")(grunt);
    grunt.loadGruntfile("../../Gruntfile.js");

    // PHP strings for exec task.
    var moodleroot = 'dirname(dirname(__DIR__))',
        configfile = moodleroot + ' . "/config.php"',
        decachephp = '';

    decachephp += "define(\"CLI_SCRIPT\", true);";
    decachephp += "require(" + configfile  + ");";

    // The previously used theme_reset_all_caches() stopped working for us, we investigated but couldn't figure out why.
    // Using purge_all_caches() is a bit of a nuclear option, as it clears more than we should need to
    // but it gets the job done.
    decachephp += "purge_all_caches();";

    grunt.mergeConfig = grunt.config.merge;

    grunt.mergeConfig({
        less: {
            // Compile moodle styles.
            moodle: {
                options: {
                    compress: false
                },
                files: {
                    "style/moodle.css": "less/moodle.less",
                }
            },
            // Compile editor styles.
            editor: {
                options: {
                    compress: false
                },
                files: {
                    "style/editor.css": "less/editor.less"
                }
            }
        },
        csslint: {
            src: "style/moodle.css",
            options: {
                "adjoining-classes": false,
                "box-sizing": false,
                "box-model": false,
                "overqualified-elements": false,
                "bulletproof-font-face": false,
                "compatible-vendor-prefixes": false,
                "selector-max-approaching": false,
                "fallback-colors": false,
                "floats": false,
                "ids": false,
                "qualified-headings": false,
                "selector-max": false,
                "unique-headings": false,
                "gradients": false,
                "important": false,
                "font-sizes": false,
            }
        },
        lesslint: {
            src: "less/moodle.less",
            options: {
                imports: "less/**/*.less"
            }
        },
        autoprefixer: {
          options: {
            browsers: [
              'Android 2.3',
              'Android >= 4',
              'Chrome >= 20',
              'Firefox >= 24', // Firefox 24 is the latest ESR
              'Explorer >= 9',
              'iOS >= 6',
              'Opera >= 12.1',
              'Safari >= 6'
            ]
          },
          core: {
            options: {
              map: false
            },
            src: ['style/moodle.css'],
          },
        },
        exec: {
            decache: {
                cmd: "php -r '" + decachephp + "'",
                callback: function(error, stdout, stderror) {
                    // exec will output error messages
                    // just add one to confirm success.
                    if (!error) {
                        grunt.log.writeln("Moodle theme cache reset.");
                    }
                }
            }
        },
        jshint: {
            options: {
                jshintrc: true,
            },
            files: ["javascript/*"]
        },
        watch: {
            options: {
                spawn: false,
            },
            less: {
                files: ["less/**/*.less"],
                tasks: ["compile"],
            },
            amd: {
                files: ["amd/src/**/*.js"],
                tasks: ["amd","decache"],
            },
        },
        // START UCLA MOD: CCLE-6832 - Update the CSS for the SNAP theme to match UCLA colors.
        // Code to minify all css files.
        cssmin: {
            minify: {
                expand: true,
                cwd: 'style/',
                src: '*.css',
                dest: 'style/',
                ext: '.css'
            }
        }
        // END UCLA MOD: CCLE-6832.
    });

    // Load contrib tasks.
    grunt.loadNpmTasks("grunt-autoprefixer");
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-csslint');
    grunt.loadNpmTasks("grunt-contrib-less");
    grunt.loadNpmTasks("grunt-lesslint");
    grunt.loadNpmTasks("grunt-contrib-watch");
    grunt.loadNpmTasks("grunt-exec");
    grunt.loadNpmTasks('grunt-contrib-cssmin');

    // Register tasks.
    grunt.registerTask("default", ["watch"]);
    grunt.registerTask("css", ["less:moodle", "less:editor", "autoprefixer"]);
    grunt.registerTask("compile", ["less:moodle", "less:editor", "autoprefixer", "decache"]);
    grunt.registerTask("decache", ["exec:decache"]);
};
