<?php

declare(strict_types=1);

namespace Slickstream;

require_once 'LifeCycle.php';
require_once 'Plugin.php';

const GENESIS_AFTER_HEADER_POSTS = 'After header on posts (for Genesis themes)';
const GENESIS_BEFORE_CONTENT_POSTS = 'Before content on posts (for Genesis themes)';
const GENESIS_AFTER_CONTENT = 'After content (for Genesis themes)';
const GENESIS_BEFORE_FOOTER = 'Before footer (for Genesis themes)';

class ActionsFilters extends PluginLifecycle
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param array<string, string> $shortcodes
     */
    public function addShortcodes(array $shortcodes): void
    {
        foreach ($shortcodes as $tag => $method) {
            add_shortcode($tag, [$this, $method]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getOptionMetaData(): array
    {
        $domain = 'slick-engagement';
        $options = [
            'SiteCode' => [
                (string)__('Site Code', $domain)
            ],
            'SlickServerUrl' => [
                (string)__('Service URL (optional)', $domain)
            ],
        ];

        if (function_exists('genesis')) {
            $options = array_merge($options, [
                'InsertFilmstrip' => [
                    (string)__('Insert filmstrip', $domain),
                    'None',
                    GENESIS_AFTER_HEADER_POSTS,
                    GENESIS_BEFORE_CONTENT_POSTS,
                ],
                'InsertSearchPanel' => [
                    (string)__('Insert inline search panel', $domain),
                    'None',
                    GENESIS_AFTER_CONTENT,
                    GENESIS_BEFORE_FOOTER,
                ],
            ]);
        }

        return $options;
    }

    /**
     * @param array<string, array<int, string>> $options
     */
    protected function addOptionsFromArray(array $options): void
    {
        foreach ($options as $key => $arr) {
            if (count($arr) > 1) {
                $this->addOption($key, $arr[1]);
            }
        }
    }

    protected function initOptions(): void
    {
        $this->addOptionsFromArray($this->getOptionMetaData());
    }

    public function getPluginDisplayName(): string
    {
        return 'Slickstream Engagement';
    }

    protected function getMainPluginFileName(): string
    {
        return 'slick-engagement.php';
    }

    protected function installDatabaseTables(): void
    {
    }

    protected function unInstallDatabaseTables(): void
    {
    }

    public function upgrade(): void
    {
    }

    // Exclude slickstream scripts from JS delay in WP-Rocket
    /**
     * @param array<int, string> $excludedStrings
     * @return array<int, string>
     */
    public function addWpRocketExclusions(array $excludedStrings = []): array
    {
        // MUST ESCAPE PERIODS AND PARENTHESES!
        $addlStringsToExclude = [
            'slickstream',
            'Slickstream',
            'SLICKSTREAM',
            'ads\.adthrive\.com'
        ];

        return array_merge($excludedStrings, $addlStringsToExclude);
    }

    public function addTaxonomiesToPages(): void
    {
        register_taxonomy_for_object_type('post_tag', 'page');
        register_taxonomy_for_object_type('category', 'page');
    }

    public function addActionsAndFilters(): void
    {
        $plugin = new SlickEngagement_Plugin();

        // Admin actions
        if (is_admin()) {
            add_action('admin_menu', [$this, 'addSettingsSubMenuPage']);
        }

        add_action('wp_head', [$plugin, 'addSlickPageHeader']);
        add_action('init', [$this, 'addTaxonomiesToPages']);

        $this->addShortcodes([
            'slick-film-strip' => 'getFilmStripShortcode',
            'slick-grid' => 'getSlickGridShortcode',
            'slick-story' => 'getSlickStoryShortcode',
            'slick-story-carousel' => 'getSlickStoryCarouselShortcode',
            'slick-story-explorer' => 'getSlickStoryExplorerShortcode',
        ]);

        $prefix = is_network_admin() ? 'network_admin_' : '';
        $pluginFile = plugin_basename($this->getPluginDir() . DIRECTORY_SEPARATOR . $this->getMainPluginFileName());

        add_filter($prefix . 'plugin_action_links_' . $pluginFile, [$this, 'onActionLinks']);
        add_filter('rocket_delay_js_exclusions', [$this, 'addWpRocketExclusions']);

        $this->addGenesisHooks();
    }

    public function addSettingsSubMenuPage(): void
    {
        $this->requireExtraPluginFiles();
        $displayName = $this->getPluginDisplayName();
        add_options_page(
            $displayName,
            $displayName,
            'manage_options',
            'SlickstreamSettings',
            [$this, 'settingsPage']
        );
    }

    // TODO: update these to be device type and feature-enabled aware
    private function addGenesisHooks(): void
    {
        // Add filmstrip CLS div for genesis themes
        $insertFilmstrip = $this->getOption('InsertFilmstrip', 'None');
        $filmstripHooks = [
            GENESIS_AFTER_HEADER_POSTS => 'genesis_after_header',
            GENESIS_BEFORE_CONTENT_POSTS => 'genesis_before_content',
        ];
        $filmstripHook = $filmstripHooks[$insertFilmstrip] ?? null;
        if ($filmstripHook) {
            add_action($filmstripHook, [$this, 'insertFilmStripMarkup'], 15);
        }

        // Add inline search panel markup for genesis themes
        $InsertSearchPanel = $this->getOption('InsertSearchPanel', 'None');
        $searchPanelHooks = [
            GENESIS_AFTER_CONTENT => 'genesis_after_content',
            GENESIS_BEFORE_FOOTER => 'genesis_before_footer',
        ];
        $searchPanelHook = $searchPanelHooks[$InsertSearchPanel] ?? null;
        if ($searchPanelHook) {
            add_action($searchPanelHook, [$this, 'insertInlineSearchPanelMarkup'], 15);
        }
    }

    // TODO: this is not device aware nor is it even aware if the filmstrip feature is turned on or not
    public function insertFilmStripMarkup(): void
    {
        if (is_singular('post')) {
            echo '<div style="min-height:72px;margin:10px auto" class="slick-film-strip"></div>';
        }
    }

    public function insertInlineSearchPanelMarkup(): void
    {
        if (is_singular('post')) {
            echo "\n<style>.slick-inline-search-panel { margin: 50px 15px; min-height: 428px; } @media (max-width: 600px) { .slick-inline-search-panel { min-height: 334px; } } </style>\n";
            echo "<div class=\"slick-inline-search-panel\" data-config=\"_default\"></div>\n";
        }
    }

    /**
     * @param array<int, string> $links
     * @return array<int, string>
     */
    public function onActionLinks(array $links): array
    {
        $settingsUrl = esc_url(admin_url('options-general.php?page=SlickstreamSettings'));
        $myLinks = ["<a href=\"" . (string)$settingsUrl . "\">Settings</a>"];
        return array_merge($links, $myLinks);
    }

    public function getFilmStripShortcode(): string
    {
        return '<div class="slick-widget slick-film-strip slick-shortcode"></div>';
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public function getSlickGridShortcode(array $attrs): string
    {
        $id = isset($attrs['id']) && is_scalar($attrs['id']) ? (string)$attrs['id'] : '';
        $sanitizedId = esc_attr($id);
        return "<div class=\"slick-content-grid\" data-config=\"$sanitizedId\"></div>\n";
    }

    public function getSlickStoryCarouselShortcode(): string
    {
        return "<style>.slick-story-carousel {min-height: 324px;} @media (max-width: 600px) {.slick-story-carousel {min-height: 224px;}}</style>\n<div class=\"slick-widget slick-story-carousel slick-shortcode\"></div>";
    }

    public function getSlickStoryExplorerShortcode(): string
    {
        return '<div class="slick-widget slick-story-explorer slick-shortcode"></div>';
    }

    //TODO: remove this eventually / deprecated feature
    /**
     * @param array<string, mixed> $attrs
     * @param string|null $content
     * @param string|null $tag
     */
    public function getSlickStoryShortcode(array $attrs, ?string $content = null, ?string $tag = null): string
    {
        extract(shortcode_atts(['src' => ''], $attrs));

        $src = isset($src) && is_scalar($src) ? (string)$src : '';
        $regexPatterns = [
            '/^https\:\/\/([^\/]+)\/d\/story\/([^\/]+)\/([^\/]+)$/i', // old-style
            '/^https\:\/\/([^\/]+)\/([^\/]+)\/d\/story\/([^\/]+)$/i', // revised-style
            '/^https\:\/\/([^\/]+)\/([^\/]+)\/story\/([^\/]+)$/i',    // story page URL
            '/^([^\/]+)\/([^\/]+)$/i'                                 // new-style
        ];

        $domain = 'stories.slickstream.com';
        $channelId = 'nochannel';
        $storyId = '';
        $webStoryUrl = '';

        foreach ($regexPatterns as $pattern) {
            if (preg_match_all($pattern, $src, $matches)) {
                $domain = $matches[1][0] ?? $domain;
                $channelId = $matches[2][0] ?? $channelId;
                $storyId = $matches[3][0] ?? $storyId;
                $webStoryUrl = $this->getSlickstreamWebStoryUrl($domain, $channelId, $storyId);
                break;
            }
        }

        $webStoryUrl = $webStoryUrl ?: $src;

        $output = '';
        if (!empty($webStoryUrl)) {
            $storyId = $this->getStoryIdFromUrl($webStoryUrl);
            $output .= "<slick-webstory-player id=\"story-" . esc_attr($storyId) . "\">\n";
            $output .= "<a href=\"" . esc_url((string)$webStoryUrl) . "\"></a>\n</slick-webstory-player>\n";
        }

        return $output;
    }

    /**
     * @param string $url
     * @return string
     */
    public function getStoryIdFromUrl(string $url): string
    {
        if (strpos($url, 'slickstream.com') !== false && strpos($url, '/d/webstory') !== false) {
            $parts = explode('/', $url);
            if (count($parts) > 1) {
                if (!empty($parts[count($parts) - 1])) {
                    return (string)$parts[count($parts) - 1];
                }
            }
        }
        return substr(hash('md5', (string)$url), 0, 5);
    }

    /**
     * @param string $domain
     * @param string $channelId
     * @param string $storyId
     * @return string
     */
    public function getSlickstreamWebStoryUrl(string $domain, string $channelId, string $storyId): string
    {
        $escapedDomain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
        $escapedChannelId = htmlspecialchars($channelId, ENT_QUOTES, 'UTF-8');
        $escapedStoryId = htmlspecialchars($storyId, ENT_QUOTES, 'UTF-8');

        return "https://$escapedDomain/$escapedChannelId/d/webstory/$escapedStoryId";
    }
}
