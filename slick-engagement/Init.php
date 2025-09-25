<?php

declare(strict_types=1);

// Note: do not rename or this function or add a namespace to this file
//  This is needed for compatibility with other plugins that check for this function
function SlickEngagement_init(): void
{
    require_once PLUGIN_DIR_PATH(__FILE__) . 'PluginInit.php';
    require_once PLUGIN_DIR_PATH(__FILE__) . 'ActionsFilters.php';
    $slickActionsFilters = new \Slickstream\ActionsFilters();

    // NOTE: this file gets run each time you *activate* the plugin.
    // So in WP when you "install" the plugin, all that does it dump its files in the plugin-templates directory
    // but it does not call any of its code.
    // So here, the plugin tracks whether or not it has run its install operation, and we ensure it is run only once
    // on the first activation
    if (!$slickActionsFilters->isInstalled()) {
        $slickActionsFilters->install();
    } else {
        $slickActionsFilters->upgrade();
    }

    // Add callbacks to hooks
    $slickActionsFilters->addActionsAndFilters();
}
