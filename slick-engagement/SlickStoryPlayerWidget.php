<?php

declare(strict_types=1);

namespace Slickstream;

/**
 * @extends \WP_Widget<array<string, mixed>>
 */
class SlickStoryPlayerWidget extends \WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'slick_story_player_widget',
            __('Slick Story Player Widget', 'text_domain'),
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
            'webstoryurl' => '',
        ];
        if (!is_array($instance)) {
            $instance = [];
        }
        $instance = array_merge($defaults, $instance);

        $title = '';
        if (isset($instance['title']) && is_scalar($instance['title'])) {
            $title = (string)$instance['title'];
        }

        $webStoryUrl = '';
        if (isset($instance['webstoryurl']) && is_scalar($instance['webstoryurl'])) {
            $webStoryUrl = (string)$instance['webstoryurl'];
        }
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Widget Title', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('webstoryurl')); ?>"><?php _e('Web Story URL', 'text_domain'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('webstoryurl')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('webstoryurl')); ?>" type="text"
                   value="<?php echo esc_attr($webStoryUrl); ?>" />
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
        $instance = is_array($old_instance) ? $old_instance : [];
        $title = '';
        if (isset($new_instance['title']) && is_scalar($new_instance['title'])) {
            $title = wp_strip_all_tags((string)$new_instance['title']);
        }
        $webStoryUrl = '';
        if (isset($new_instance['webstoryurl']) && is_scalar($new_instance['webstoryurl'])) {
            $webStoryUrl = wp_strip_all_tags((string)$new_instance['webstoryurl']);
        }
        $instance['title'] = $title;
        $instance['webstoryurl'] = $webStoryUrl;
        return [
            'title' => $instance['title'],
            'webstoryurl' => $instance['webstoryurl'],
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
        if (is_array($instance) && isset($instance['title']) && is_scalar($instance['title'])) {
            $title = apply_filters('widget_title', $instance['title']);
            if (!is_string($title)) {
                $title = '';
            }
        }

        $webStoryUrl = '';
        if (isset($instance['webstoryurl']) && is_scalar($instance['webstoryurl'])) {
            $webStoryUrl = (string)$instance['webstoryurl'];
        }

        if (!empty($webStoryUrl)) {
            echo isset($args['before_widget']) && is_scalar($args['before_widget']) ? (string)$args['before_widget'] : '';
            if ($title) {
                $before_title = isset($args['before_title']) && is_scalar($args['before_title']) ? (string)$args['before_title'] : '';
                $after_title = isset($args['after_title']) && is_scalar($args['after_title']) ? (string)$args['after_title'] : '';
                echo $before_title . esc_html($title) . $after_title;
            }
            $storyId = $this->getStoryIdFromUrl($webStoryUrl);
            echo "<slick-webstory-player id=\"story-" . esc_attr($storyId) . "\">\n";
            echo "  <a href=\"" . esc_url($webStoryUrl) . "\"></a>\n";
            echo "</slick-webstory-player>\n";
            echo isset($args['after_widget']) && is_scalar($args['after_widget']) ? (string)$args['after_widget'] : '';
        }
    }

    public function getStoryIdFromUrl(string $url): string
    {
        if (strpos($url, 'slickstream.com') !== false && strpos($url, '/d/webstory') !== false) {
            $parts = explode('/', $url);
            if (count($parts) > 1) {
                if (!empty($parts[count($parts) - 1])) {
                    return $parts[count($parts) - 1];
                }
            }
        }
        return substr(hash('md5', $url), 0, 5);
    }
}
