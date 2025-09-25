<?php

declare(strict_types=1);

namespace Slickstream;

//Added for backwards compatibility; do not remove/rename
function PluginInit(): void
{
    require_once PLUGIN_DIR_PATH(__FILE__) . 'Init.php';
    \SlickEngagement_init();
}
