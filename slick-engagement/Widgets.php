<?php

declare(strict_types=1);

namespace Slickstream;

if (\defined('ABSPATH')) {
    require_once __DIR__ . '/SlickFilmStripWidget.php';
    require_once __DIR__ . '/SlickGridWidget.php';
    require_once __DIR__ . '/SlickStoryPlayerWidget.php';
    require_once __DIR__ . '/SlickStoryCarouselWidget.php';
    require_once __DIR__ . '/SlickStoryExplorerWidget.php';

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
