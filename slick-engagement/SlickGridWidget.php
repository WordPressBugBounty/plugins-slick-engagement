<?php

declare(strict_types=1);

namespace Slickstream;

/**
 * @extends \WP_Widget<array<string, mixed>>
 */
class SlickGridWidget extends \WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'slick_grid_widget',
            __('Slick Grid Widget', 'text_domain'),
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
            'id' => '',
        ];
        $instance = wp_parse_args((array)$instance, $defaults);
        $title = (string)($instance['title'] ?? '');
        $id = (string)($instance['id'] ?? '');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Widget Title', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('id')); ?>"><?php _e('Slickstream ID', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('id')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('id')); ?>" type="text"
                   value="<?php echo esc_attr($id); ?>" />
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
        $instance['id'] = (string)wp_strip_all_tags($new_instance['id'] ?? '');
        return [
            'title' => (string)$instance['title'],
            'id' => (string)$instance['id'],
        ];
    }

    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed> $instance
     * @return void
     */
    public function widget($args, $instance): void
    {
        $title = (string)apply_filters('widget_title', $instance['title']);
        $id = (string)$instance['id'];
        echo (string)($args['before_widget'] ?? '');
        if ($title) {
            echo (string)($args['before_title'] ?? '') . esc_html((string)$title) . (string)($args['after_title'] ?? '');
        }
        if (!empty($id)) {
            echo '<div class="slick-content-grid" data-config="' . esc_attr(trim((string)$id)) . '"></div>' . "\n";
        } else {
            echo "<div class=\"slick-content-grid\"></div>\n";
        }
        echo (string)($args['after_widget'] ?? '');
    }
}
