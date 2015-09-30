module.exports = function(grunt) {
    // Project configuration.
    grunt.initConfig({
        makepot: {
            target: {
                options: {
                    domainPath: '/languages',                   // Where to save the POT file.
                    exclude: ['.idea','assets'],                      // List of files or directories to ignore.
                    mainFile: 'woocommerce-add-tab.php',                     // Main project file.
                    potFilename: 'woocommerce-add-tab.pot',                  // Name of the POT file.
                    potHeaders: {
                        poedit: true,                 // Includes common Poedit headers.
                        'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
                    },                                // Headers to add to the generated POT file.
                    type: 'wp-plugin',                // Type of project (wp-plugin or wp-theme).
                    updatePoFiles: true              // Whether to update PO files in the same directory as the POT file.
                }
            }
        },
        potomo: {
            dist: {
                files: {
                    'languages/woocommerce-add-tab-ru_RU.mo': 'languages/woocommerce-add-tab-ru_RU.po'
                }
            }
        }
    });

    grunt.loadNpmTasks( 'grunt-wp-i18n' );
    grunt.loadNpmTasks( 'grunt-potomo' );

    grunt.registerTask('default', ['makepot', 'potomo']);

};