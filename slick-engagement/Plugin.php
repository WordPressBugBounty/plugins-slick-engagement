<?php

declare(strict_types=1);

namespace Slickstream;

require_once PLUGIN_DIR_PATH(__FILE__) . 'SlickWidgets.php';
require_once PLUGIN_DIR_PATH(__FILE__) . 'OptionsManager.php';
require_once PLUGIN_DIR_PATH(__FILE__) . 'PageBootData.php';
require_once PLUGIN_DIR_PATH(__FILE__) . 'Utils.php';

class SlickEngagement_Plugin extends OptionsManager
{
    private const PLUGIN_VERSION = '3.0.1';
    private const DEFAULT_APP_SERVER = 'app.slickstream.com';
    private const CDN_SERVER = 'c.slickstream.com';
    private string $scriptClass = 'slickstream-script';
    private string $serverUrlBase;
    private string $siteCode;
    private Utils $utils;
    private const CLIENT_CODE_BRANCH = 'main';
    private const CLIENT_VERSION = '2.15.3'; // Update this when the embed code, bootloader, or CLS insertion scripts change
    public function __construct()
    {
        parent::__construct();
        $this->siteCode = rawurlencode(substr(trim($this->getOption('SiteCode', '')), 0, 9));
        $serverHost = $this->getOption('SlickServerUrl', self::DEFAULT_APP_SERVER);
        $serverHost = preg_replace('#^https?://#', '', $serverHost);
        $this->serverUrlBase = "https://$serverHost";
        $this->utils = Utils::getInstance();
    }

    private function echoCLSDebugScript(): void
    {
        $this->utils->echoComment("CLS Monitor Script", false, true);
        echo <<<JSDOC
        <script id='slick-wp-plugin-debug-cls' class='\$this->scriptClass'>(function(){if(!window.chrome){return}const clsDataCallback=(clsData)=>{if(typeof clsData.value!=="number"||!clsData.attribution){console.info(`[slickstream] Invalid CLS data object.`);return}console.info(`[slickstream] The CLS score on this page is: \${clsData.value.toFixed(3)}, which is considered \${clsData.rating}`);if(clsData.value>0.000){console.info(`[slickstream] The element that contributed the most CLS is:`);console.info(clsData.attribution?.largestShiftSource?.node||"No node information available.");console.table(clsData.attribution)}};console.info(`[slickstream] Monitoring for CLS...`);const script=document.createElement('script');script.src='https://unpkg.com/web-vitals/dist/web-vitals.attribution.iife.js';script.onload=function(){if(typeof webVitals!=='undefined'&&webVitals.onCLS){webVitals.onCLS(clsDataCallback)}else{console.warn(`[slickstream] webVitals library did not load correctly.`)}};document.head.appendChild(script)})();</script>
        JSDOC;
        $this->utils->echoComment("END CLS Monitor Script", false, true);
    }

    private function getCurrentTimestampByTimeZone(string $timezone): string
    {
        $timestamp = time();
        $dt = new \DateTime('now', new \DateTimeZone($timezone));
        $dt->setTimestamp($timestamp);
        return $dt->format('n/j/Y, g:i:s A');
    }

    private function getTaxTerms($post, $taxonomyName): array
    {
        $taxTerms = [];
        $terms = get_the_terms($post, $taxonomyName);

        if (empty($terms)) {
            return $taxTerms;
        }

        foreach ($terms as $term) {
            $termObject = (object) [
                '@id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ];
            array_push($taxTerms, $termObject);
        }

        return $taxTerms;
    }

    private function createLdJsonTaxElement($taxonomy, $taxTerms): object
    {
        return (object) [
            'name' => $taxonomy->name,
            'label' => $taxonomy->label,
            'description' => $taxonomy->description,
            'terms' => $taxTerms,
        ];
    }

    private function echoWpRocketDetection(): void
    {
        $this->utils->echoComment("WP-Rocket Detection", false, false);
        echo <<<JSBLOCK
        <script id="slick-wp-rocket-detect-script" class='$this->scriptClass'>
        (function() {
            const slickScripts = document.querySelectorAll('script.$this->scriptClass[type=rocketlazyloadscript]');
            const extScripts = document.querySelectorAll('script[type=rocketlazyloadscript][src*="app.slickstream.com"]');
            if (slickScripts.length > 0 || extScripts.length > 0) {
                console.warn('[slickstream]' + ['Slickstream scripts. This ',
                'may cause undesirable behavior, ', 'such as increased CLS scores.',' WP-Rocket is deferring one or more '].sort().join(''));
            }
        })();
        </script>
        JSBLOCK;
        $this->utils->echoComment("END WP-Rocket Detection", false, false);
    }

    private function consoleLogAbTestData(): void
    {
        $this->utils->echoComment("A/B Test Logging Script", false, true);
        echo <<<JSBLOCK
        <script id="slick-ab-test-script" class='$this->scriptClass'>
        "use strict";(async()=>{var e,t;const o=window.\$slickBoot=window.\$slickBoot||{};const n="[slickstream] ";const s="color: red";const a="color: yellow";if(!o.d){console.warn(`%c\${n}Slickstream page boot data not found.`,a);return}const r=(e=o.d)===null||e===void 0?void 0:e.abTests;const i=(t=o.d)===null||t===void 0?void 0:t.siteCode;if(!o){console.warn(`%c\${n}Slickstream config data not found; Slickstream is likely not installed on this site.`,a);return}if(!i){console.warn(`%c\${n}Could not determine Slickstream siteCode for this page.`,a);return}if(o.d.bestBy<Date.now()){console.warn(`%c\${n}WARNING: Slickstream page config data is stale. Please reload the page to fetch up-to-date config data.`,a)}if(!r||Array.isArray(r)&&r.length===0){console.info(`%c\${n}[DEBUG] There are no Slickstream A/B tests running currently.`,a)}else{console.info(`%c\${n}A/B TEST(S) FOR SLICKSTREAM ARE RUNNING. \\n\\nHere are the details:`,s);const e=e=>{var t;const o=localStorage.getItem("slick-ab");const n=o&&JSON.parse(o)||{value:false};return{"Feature being Tested":e.feature,"Is the A/B test running on this site?":!((t=e===null||e===void 0?void 0:e.excludeSites)===null||t===void 0?void 0:t.includes(i))?"yes":"no","Am I in the test group (feature disabled)?":n.value===true?"yes":"no","Percentage of Users this feature is ENABLED For":e.fraction,"Percentage of Users this feature is DISABLED For":100-e.fraction,"Start Date":new Date(e.startDate).toString(),"End Date":new Date(e.endDate).toString(),"Current Time":(new Date).toString()}};r.forEach((t=>{console.table(e(t))}))}})();
        </script>
        JSBLOCK;
        $this->utils->echoComment("END A/B Test Logging Script", false, true);
    }

    private function getPageType(): string
    {
        if (is_front_page() || is_home()) {
            return 'home';
        }
        if (is_category()) {
            return 'category';
        }
        if (is_tag()) {
            return 'tag';
        }
        if (is_singular('post')) {
            return 'post';
        }
        if (is_singular('page')) {
            return 'page';
        }
        return 'other';
    }

    private function addMeta($property, $content): void
    {
        $property = (string) $property;
        $content = (string) $content;
        echo '<meta property="' . htmlspecialchars($property, ENT_QUOTES, 'UTF-8') .
        '" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . "\" />\n";
    }

    private function handleCategoryMeta($ldJsonPost): void
    {
        $term = get_queried_object();
        if (isset($term->slug)) {
            $this->addMeta('slick:category', "$term->slug:$term->name");
            $ldJsonPost->category = (object)[
                '@id' => $term->term_id,
                'slug' => $term->slug,
                'name' => $term->name
            ];
        }
    }

    private function handleTagMeta($ldJsonPost): void
    {
        $term = get_queried_object();
        if (isset($term->slug)) {
            $this->addMeta('slick:tag', "$term->slug:$term->name");
            $ldJsonPost->tag = (object)[
                '@id' => $term->term_id,
                'slug' => $term->slug,
                'name' => $term->name
            ];
        }
    }

    private function handleSingularMeta($post, &$ldJsonPost): void
    {
        if (is_singular('post')) {
            $this->addMeta('slick:group', 'post');
        }

        $this->handleCategories($post, $ldJsonPost);
        $this->handleTags($post, $ldJsonPost);
        $this->handleTaxonomies($post, $ldJsonPost);
    }

    private function handleCategories($post, &$ldJsonPost): void
    {
        $categories = get_the_category();
        if (empty($categories)) {
            return;
        }

        $ldJsonCategoryElements = [];
        foreach ($categories as $category) {
            if (isset($category->slug) && $category->slug !== 'uncategorized') {
                $this->addMeta('slick:category', $category->slug . ':' . $this->utils->removeSemicolons($category->name));
                $ldJsonCategoryElements[] = $this->buildCategoryElement($category);
            }
        }

        if (!empty($ldJsonCategoryElements)) {
            $ldJsonPost->categories = $ldJsonCategoryElements;
        }
    }

    private function buildCategoryElement($category): object
    {
        $ldJsonParents = [];
        $used = [$category->cat_ID];
        $parentCatId = $category->category_parent;

        while ($parentCatId && count($used) < 8 && !in_array($parentCatId, $used)) {
            $parentCat = get_category($parentCatId);

            if ($parentCat instanceof \WPError || $parentCat === null) {
                continue;
            }

            if (is_object($parentCat) && isset($parentCat->slug) && $parentCat->slug !== 'uncategorized') {
                $parentCat = (object) $parentCat; //To placate WPError warnings
                $this->addMeta(';', $parentCat->slug . ':' . $this->utils->removeSemicolons($parentCat->name));
                $ldJsonParents[] = (object)[
                    '@type' => 'CategoryParent',
                    '@id' => $parentCat->cat_ID,
                    'slug' => $parentCat->slug,
                    'name' => $this->utils->removeSemicolons($parentCat->name)
                ];
            }
            $used[] = $parentCatId;

            if (!is_wp_error($parentCat)) {
                $parentCatId = $parentCat->category_parent;
            } else {
                continue;
            }
        }

        return (object)[
            '@id' => $category->term_id,
            'parent' => $category->parent,
            'slug' => $category->slug,
            'name' => $this->utils->removeSemicolons($category->name),
            'parents' => $ldJsonParents
        ];
    }

    private function handleTags($post, &$ldJsonPost): void
    {
        $tags = get_the_tags();
        if (empty($tags)) {
            return;
        }
        $ldJsonTags = array_map(function ($tag) {
            return $tag->name;
        }, $tags);

        if (!empty($ldJsonTags)) {
            $ldJsonPost->tags = $ldJsonTags;
        }
    }

    private function handleTaxonomies($post, &$ldJsonPost): void
    {
        $taxonomies = get_object_taxonomies($post, 'objects');
        if (empty($taxonomies)) {
            return;
        }

        $ldJsonTaxonomies = [];
        foreach ($taxonomies as $taxonomy) {
            if (empty($taxonomy->_builtin) && $taxonomy->public) {
                $taxTerms = $this->getTaxTerms($post, $taxonomy->name);
                if (!empty($taxTerms)) {
                    $ldJsonTaxonomies[] = $this->createLdJsonTaxElement($taxonomy, $taxTerms);
                }
            }
        }

        if (!empty($ldJsonTaxonomies)) {
            $ldJsonPost->taxonomies = $ldJsonTaxonomies;
        }
    }

    private function buildLdJsonPost($post): object
    {
        $ldJsonPost = (object)[
            '@type' => 'WebPage',
            '@id' => $post->ID,
            'isFront' => is_front_page(),
            'isHome' => is_home(),
            'isCategory' => is_category(),
            'isTag' => is_tag(),
            'isSingular' => is_singular(),
            'date' => get_the_time('c'),
            'modified' => get_the_modified_time('c'),
            'title' => $post->post_title,
            'pageType' => $this->getPageType(),
            'postType' => $post->post_type
        ];

        return $ldJsonPost;
    }


    // Check the transient cache for the embed code first
    // If not present, fetch it from the server
    public function getEmbedCode(string $version, string $branch = 'main', string $overrideUrl = ''): string
    {
        $embedCodeTransientName = 'slickstream_embed_code';
        $embedCode = get_transient($embedCodeTransientName);

        if ($embedCode) {
            return $embedCode;
        }

        $embedCodeObj = $this->fetchRemoteEmbedCode($version, $branch, $overrideUrl);

        if (!$embedCodeObj) {
            return "// ERROR: Failed to fetch embed code from the server (Version: $version / Branch $branch)";
        }

        $embedCode = $this->replaceEmbedPlaceholders($embedCodeObj->body);

        if ($embedCode === null) {
            return "// ERROR: Embed code contains placeholders and was not cached";
        }

        $this->cacheEmbedCode($embedCode, $embedCodeTransientName);
        return $embedCode;
    }

    private function fetchRemoteEmbedCode(string $version, string $branch, string $overrideUrl = ''): ?object
    {
        $codeBranchStr = ($branch === self::CLIENT_CODE_BRANCH) ? 'app' : "app-branch/$branch";

        $remoteUrl = (!empty($overrideUrl)) ?
            $overrideUrl :
            "https://" . self::CDN_SERVER . "/$codeBranchStr/$version/embed-code.js";

        $this->utils->echoComment("Fetching Embed Code");
        $embedCodeObj = $this->utils->fetchRemote($remoteUrl, 2);

        if (
            $embedCodeObj &&
            $embedCodeObj->status === 'success' &&
            $embedCodeObj->body &&
            strpos($embedCodeObj->body, "<Error>") === false
        ) {
            return $embedCodeObj;
        }

        return null;
    }

    private function replaceEmbedPlaceholders(string $embedCode): ?string
    {
        $replacements = [
            '{{{serverRoot}}}' => addslashes($this->serverUrlBase),
            '{{sitecode}}' => addslashes($this->siteCode),
        ];

        foreach ($replacements as $search => $replace) {
            $embedCode = preg_replace('/' . preg_quote($search, '/') . '/u', $replace, $embedCode);
        }

        if (
            strpos($embedCode, '{{{serverRoot}}}') === false &&
            strpos($embedCode, '{{sitecode}}') === false
        ) {
            return $embedCode;
        }
        return null;
    }

    private function cacheEmbedCode(string $embedCode, string $embedCodeTransientName): void
    {
        set_transient($embedCodeTransientName, $embedCode, 8 * HOUR_IN_SECONDS);
    }

    public function echoEmbedCode(): void
    {
        // TODO: update the embed-code URL to point to the latest version (using a permalink/slug)
        $overrideUrl = ''; // Set this to a specific URL if needed, otherwise it will use the default CDN URL

        $embedCode = $this->getEmbedCode(self::CLIENT_VERSION, self::CLIENT_CODE_BRANCH, $overrideUrl);

        if ($embedCode) {
            $this->utils->echoComment("Embed Code", false, false, true);
            echo "<script id=\"slick-embed-code-script\" class='$this->scriptClass'>\n$embedCode\n</script>\n";
            $this->utils->echoComment("END Embed Code", false, false, true);
        } else {
            $this->utils->echoComment("Embed code missing; Slickstream services are disabled", true, false, true);
        }

        return;
    }

    private function echoVersionMetaTag(): void
    {
        $this->utils->echoComment("Slickstream WordPress Plugin Version: " . self::PLUGIN_VERSION, true, true, false);

        echo "<meta property='slick:wpversion' content='" . self::PLUGIN_VERSION . "' />\n";
    }

    // Injects debug info, meta tags, page boot data, and other page metadata into the <head> tag
    public function addSlickPageHeader(): void
    {
        global $post;

        echo "\n\n";
        $this->utils->echoComment("[[[ START Slickstream Output ]]]", false, false, true);
        $this->echoPageGenerationTimestamp();

        if (!$this->siteCode) {
            $this->utils->echoComment("ERROR: Site Code missing from Plugin Settings; Slickstream services are disabled", true, false, true);
            return;
        }

        $this->utils->echoComment("Slickstream Site Code: {$this->siteCode}", true, true, false);
        $pageBootData = new PageBootData($this->serverUrlBase, $this->siteCode, $this->scriptClass);
        $pageBootData->handlePageBootData();
        $this->echoEmbedCode();
        // TODO: fetch, transient cache, and echo the contents of `boot-loader.js`
        $this->echoPageMetadata($post);
        $this->outputDebugInfo();
        $this->echoWpRocketDetection();
        $this->utils->echoComment("[[[ END Slickstream Output ]]]", false, false, true);
        echo "\n\n";
    }

    private function echoPageGenerationTimestamp(): void
    {
        $timezone = 'America/New_York';
        $shortTimezone = 'EST';
        $this->utils->echoComment("Page Generated at: " . $this->getCurrentTimestampByTimeZone($timezone) . " $shortTimezone", true, false, false);
        echo "<script>console.info(`[slickstream] Current timestamp: \${(new Date).toLocaleString('en-US', { timeZone: '$timezone' })} $shortTimezone`);</script>\n";
    }

    private function echoPageMetadata($post): void
    {
        $this->utils->echoComment("Page Metadata:", false, false);
        $this->echoVersionMetaTag();

        $ldJsonElements = [];
        array_push($ldJsonElements, $this->getLdJsonPluginData(), $this->getLdJsonSiteData());

        if (!empty($post)) {
            $ldJsonPost = $this->buildLdJsonPost($post);
            $this->processPostMetadata($post, $ldJsonPost);
            array_push($ldJsonElements, $ldJsonPost);
        }

        $ldJson = (object) [
            '@context' => 'https://slickstream.com',
            '@graph' => $ldJsonElements,
        ];
        echo '<script type="application/x-slickstream+json">' . json_encode($ldJson, JSON_UNESCAPED_SLASHES) . "</script>\n";
        $this->utils->echoComment("END Page Metadata", false, false, true);
    }

    private function getLdJsonPluginData(): object
    {
        return (object) [
            '@type' => 'Plugin',
            'version' => self::PLUGIN_VERSION,
        ];
    }

    private function getLdJsonSiteData(): object
    {
        return (object) [
            '@type' => 'Site',
            'name' => get_bloginfo('name'),
            'url' => get_bloginfo('url'),
            'description' => get_bloginfo('description'),
            'atomUrl' => get_bloginfo('atom_url'),
            'rtl' => is_rtl(),
        ];
    }

    private function processPostMetadata($post, &$ldJsonPost): void
    {
        $this->addMeta('slick:wppostid', $post->ID);

        if (has_post_thumbnail($post)) {
            $images = wp_get_attachment_image_src(get_post_thumbnail_id($post), 'single-post-thumbnail');
            if (!empty($images)) {
                $this->addMeta('slick:featured_image', $images[0]);
                $ldJsonPost->featured_image = $images[0];
            }
        }

        $authorName = get_the_author_meta('display_name');
        if (!empty($authorName)) {
            $ldJsonPost->author = $authorName;
        }

        $this->handlePostTypeMeta($post, $ldJsonPost);
    }

    private function handlePostTypeMeta($post, &$ldJsonPost): void
    {
        switch (true) {
            case is_category():
                $this->addMeta('slick:group', 'category');
                $this->handleCategoryMeta($ldJsonPost);
                break;

            case is_tag():
                $this->addMeta('slick:group', 'tag');
                $this->handleTagMeta($ldJsonPost);
                break;

            case is_singular(['post', 'page']):
                $this->handleSingularMeta($post, $ldJsonPost);
                break;
        }
    }

    private function outputDebugInfo(): void
    {
        if (!$this->utils->isDebugModeEnabled()) {
            return;
        }
        $this->consoleLogAbTestData();
        $this->echoCLSDebugScript();
    }
}
