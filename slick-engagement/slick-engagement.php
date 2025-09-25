<?php

namespace Slickstream;

/*
 * Plugin Name:       Slickstream Search and Engagement
 * Plugin URI:        https://slickstream.com/
 * Description:       For use with Slickstream's cloud service and widgets to increase visitor engagement
 * Version:           3.0.1
 * Requires at least: 5.2.0
 * Requires PHP:      7.4.0
 * Tested up to:      6.8.2
 * Author:            Slickstream
 * Author URI:        https://slickstream.com
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       slick-engagement
 */

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 *
 * @return void
 */
function SlickstreamPluginInit(): void
{
    $minimumRequiredPhpVersion = '7.4.0';

    if (version_compare((string) phpversion(), $minimumRequiredPhpVersion) < 0) {
        add_action(
            'admin_notices',
            function () use ($minimumRequiredPhpVersion) {
                echo '<div class="updated fade">' .
                (string)__('Error: plugin "Slickstream Engagement" requires a newer version of PHP to run properly.', 'slick-engagement') .
                '<br/>' . (string)__('Minimum version of PHP required: ', 'slick-engagement') . '<strong>' . $minimumRequiredPhpVersion . '</strong>' .
                '<br/>' .  (string)__('Your server\'s PHP version: ', 'slick-engagement') . '<strong>' . (string)phpversion() . '</strong>' .
                '</div>';
            }
        );
        return;
    }

    require_once PLUGIN_DIR_PATH(__FILE__) . 'Init.php';
    \SlickEngagement_init();
}

add_action('plugins_loaded', __NAMESPACE__ . '\\SlickstreamPluginInit');
