<?php

declare(strict_types=1);

namespace Slickstream;

/**
 * @extends \WP_Widget<array<string, mixed>>
 */
class SlickStoryCarouselWidget extends \WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'slick_story_carousel_widget',
            __('Slick Story Carousel Widget', 'text_domain'),
            ['customize_selective_refresh' => true]
        );
    }

    /**
     * @param array<string, mixed> $instance
     * @return void
     */
    public function form($instance): void
    {
        $defaults = [
            'title' => '',
        ];
        $instance = wp_parse_args((array)$instance, $defaults);
        $title = (string)($instance['title'] ?? '');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Widget Title', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>" />
        </p>
        <?php
    }

    /**
     * @param array<string, mixed> $new_instance
     * @param array<string, mixed> $old_instance
     * @return array<string, string>
     */
    public function update($new_instance, $old_instance): array
    {
        $instance = $old_instance;
        $instance['title'] = (string)wp_strip_all_tags($new_instance['title'] ?? '');
        return [
            'title' => (string)$instance['title'],
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed> $instance
     * @return void
     */
    public function widget($args, $instance): void
    {
        $title = '';
        if (isset($instance['title']) && is_scalar($instance['title'])) {
            $title = (string)apply_filters('widget_title', $instance['title']);
        }
        echo (string)($args['before_widget'] ?? '');
        if ($title) {
            echo (string)($args['before_title'] ?? '') . esc_html((string)$title) . (string)($args['after_title'] ?? '');
        }
        echo "<style>.slick-story-carousel {min-height: 324px;} @media (max-width: 600px) {.slick-story-carousel {min-height: 224px;}}</style>\n";
        echo "<div class=\"slick-story-carousel\"></div>\n";
        echo (string)($args['after_widget'] ?? '');
    }
}
