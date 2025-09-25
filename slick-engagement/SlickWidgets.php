<?php

declare(strict_types=1);

namespace Slickstream;

if (\defined('ABSPATH')) {
    require_once PLUGIN_DIR_PATH(__FILE__) . 'SlickFilmStripWidget.php';
    require_once PLUGIN_DIR_PATH(__FILE__) . 'SlickGridWidget.php';
    require_once PLUGIN_DIR_PATH(__FILE__) . 'SlickStoryPlayerWidget.php';
    require_once PLUGIN_DIR_PATH(__FILE__) . 'SlickStoryCarouselWidget.php';
    require_once PLUGIN_DIR_PATH(__FILE__) . 'SlickStoryExplorerWidget.php';

    function register_slick_widgets(): void
    {
        register_widget(SlickFilmStripWidget::class);
        register_widget(SlickGridWidget::class);
        register_widget(SlickStoryPlayerWidget::class);
        register_widget(SlickStoryCarouselWidget::class);
        register_widget(SlickStoryExplorerWidget::class);
    }

    \add_action('widgets_init', __NAMESPACE__ . '\\register_slick_widgets');
}
