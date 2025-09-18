<?php

declare(strict_types=1);

namespace Slickstream;

class OptionsManager
{
    private string $optionNamePrefix;

    public function __construct()
    {
        // Note: Do not change this or else settings from previous versions will not be able to be accessed
        $this->optionNamePrefix = 'SlickEngagement_Plugin_';
    }

    /**
     * Define your options meta data here as an array, where each element in the array
     * @return array<string, array<string>|string>
     */
    public function getOptionMetaData(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getOptionNames(): array
    {
        return array_keys($this->getOptionMetaData());
    }

    /**
     * Override this method to initialize options to default values and save to the database with add_option
     */
    protected function initOptions(): void
    {
    }

    /**
     * Cleanup: remove all options from the DB
     */
    protected function deleteSavedOptions(): void
    {
        $optionMetaData = $this->getOptionMetaData();
        foreach ($optionMetaData as $aOptionKey => $aOptionMeta) {
            $prefixedOptionName = $this->prefix($aOptionKey);
            delete_option($prefixedOptionName);
        }
    }

    /**
     * @return string display name of the plugin to show as a name/title in HTML.
     * Just returns the class name. Override this method to return something more readable
     */
    public function getPluginDisplayName(): string
    {
        return 'Slickstream Engagement';
    }

    /**
     * Get the prefixed version input $name suitable for storing in WP options
     * Idempotent: if $optionName is already prefixed, it is not prefixed again, it is returned without change
     */
    public function prefix(string $name): string
    {
        if (strpos($name, $this->optionNamePrefix) === 0) {
            return $name;
        }
        return "{$this->optionNamePrefix}$name";
    }

    /**
     * A wrapper function delegating to WP get_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     *
     * @param $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @param $default string default value to return if the option is not set
     * @return string the value from delegated call to get_option(), or optional default value
     * if option is not set.
     */
    public function getOption(string $optionName, ?string $default = null): string
    {
        $prefixedOptionName = $this->prefix($optionName);
        $retVal = get_option($prefixedOptionName);
        if (!$retVal && $default !== null) {
            $retVal = $default;
        }
        return is_string($retVal) ? $retVal : (string)$retVal;
    }

    /**
     * A wrapper function delegating to WP delete_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     * @param  $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @return bool from delegated call to delete_option()
     */
    public function deleteOption(string $optionName): bool
    {
        $prefixedOptionName = $this->prefix($optionName);
        $result = delete_option($prefixedOptionName);
        return (bool)$result;
    }

    /**
     * A wrapper function delegating to WP add_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     *
     * @param  $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @param  $value mixed the new value
     * @return null from delegated call to delete_option()
     */
    /**
     * @param string $optionName
     * @param mixed $value
     * @return bool
     */
    public function addOption(string $optionName, $value): bool
    {
        $prefixedOptionName = $this->prefix($optionName);
        $result = add_option($prefixedOptionName, $value);
        return (bool)$result;
    }

    /**
     * A wrapper function delegating to WP update_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     *
     * @param  $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @param  $value mixed the new value
     * @return null from delegated call to delete_option()
     */
    /**
     * @param string $optionName
     * @param mixed $value
     * @return bool
     */
    public function updateOption(string $optionName, $value): bool
    {
        $prefixedOptionName = $this->prefix($optionName);
        $result = update_option($prefixedOptionName, $value);
        return (bool)$result;
    }

    /**
     * A Role Option is an option defined in getOptionMetaData() as a choice of WP standard roles.
     */
    public function getRoleOption(string $optionName): string
    {
        $roleAllowed = $this->getOption($optionName);
        if (!$roleAllowed || $roleAllowed === '') {
            $roleAllowed = 'Administrator';
        }
        return $roleAllowed;
    }

    /**
     * Given a WP role name, return a WP capability which only that role and roles above it have
     * @see http://codex.wordpress.org/Roles_and_Capabilities
     *
     * @param  $roleName
     * @return string a WP capability or '' if unknown input role
     */
    protected function roleToCapability(string $roleName): string
    {
        switch ($roleName) {
            case 'Super Admin':
                return 'manage_options';
            case 'Administrator':
                return 'manage_options';
            case 'Editor':
                return 'publish_pages';
            case 'Author':
                return 'publish_posts';
            case 'Contributor':
                return 'edit_posts';
            case 'Subscriber':
                return 'read';
            case 'Anyone':
                return 'read';
        }
        return '';
    }

    /**
     * @param string $roleName a standard WP role name like 'Administrator'
     */
    public function isUserRoleEqualOrBetterThan(string $roleName): bool
    {
        if ($roleName === 'Anyone') {
            return true;
        }
        $capability = $this->roleToCapability($roleName);
        return $capability !== '' && current_user_can($capability);
    }

    /**
     * @param string $optionName name of a Role option (see comments in getRoleOption())
     */
    public function canUserDoRoleOption(string $optionName): bool
    {
        $roleAllowed = $this->getRoleOption($optionName);
        if ($roleAllowed === 'Anyone') {
            return true;
        }
        return $this->isUserRoleEqualOrBetterThan($roleAllowed);
    }

    /**
     * Creates HTML for the Administration page to set options for this plugin.
     * Override this method to create a customized page.
     */
    public function settingsPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'slick-engagement'));
        }

        $optionMetaData = $this->getOptionMetaData();

        // Save Posted Options
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['slick_engagement_nonce']) || !wp_verify_nonce($_POST['slick_engagement_nonce'], 'slick_engagement_save_settings')) {
                wp_die(__('Security check failed', 'slick-engagement'));
            }
            if ($optionMetaData != null) {
                foreach ($optionMetaData as $aOptionKey => $aOptionMeta) {
                    if (isset($_POST[$aOptionKey])) {
                        $this->updateOption($aOptionKey, $_POST[$aOptionKey]);
                    }
                }
            }
        }

        // HTML for the page
        $settingsGroup = get_class($this) . '-settings-group';
        ?>
        <div class="wrap">
            <h2><?php echo $this->getPluginDisplayName();
            echo ' ';
            _e('Settings', 'slick-engagement'); ?></h2>
            <div style="max-width: 600px;">
                <p>This plugin adds the embed code needed to use <a href="https://www.slickstream.com">Slickstream</a>.
                    To use this service, you need to sign up and get a site code. Enter that code below in the Site Code field.</p>
                <p>The remaining settings are optional.</p>
            </div>
            <form method="post" action="">
                <?php settings_fields($settingsGroup); ?>
                <?php wp_nonce_field('slick_engagement_save_settings', 'slick_engagement_nonce'); ?>
                <style type="text/css">
                    table.plugin-options-table {padding: 0; border-collapse: collapse;}
                    table.plugin-options-table td {vertical-align: middle; padding: 0 5px;}
                    table.plugin-options-table td+td {width: auto}
                    table.plugin-options-table td > p {margin-top: 0; margin-bottom: 0;}
                    table.plugin-options-table th {text-align:right; padding:0 5px;}
                </style>
                <table class="plugin-options-table"><tbody>
                <?php
                foreach ($optionMetaData as $aOptionKey => $aOptionMeta) {
                    $displayText = is_array($aOptionMeta) ? $aOptionMeta[0] : $aOptionMeta;
                    ?>
                    <tr valign="top">
                        <th scope="row" style="text-align:right;padding-right:5px;">
                            <p>
                                <label for="<?php echo esc_attr((string)$aOptionKey); ?>">
                                    <?php echo esc_html((string)$displayText); ?>
                                </label>
                            </p>
                        </th>
                        <td>
                            <?php $this->createFormControl($aOptionKey, $aOptionMeta, $this->getOption($aOptionKey)); ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody></table>
                <p class="submit">
                    <input type="submit" class="button-primary"
                        value="<?php _e('Save Changes', 'slick-engagement'); ?>"/>
                </p>
            </form>
            <div style="max-width: 600px;">
                <p>The Service URL should be left blank unless directed otherwise when setting up your service.</p>
                <p>Have problems or need customization? <a href="mailto:support@slickstream.com">Contact us</a>.</p>
            </div>
        </div>
        <?php
    }

    /**
     * Helper function that outputs the correct form element (input tag, select tag) for the given item
     *
     * @param  $aOptionKey string name of the option (un-prefixed)
     * @param  $aOptionMeta mixed meta-data for $aOptionKey (either a string display-name or an array(display-name, option1, option2, ...)
     * @param  $savedOptionValue string current value for $aOptionKey
     * @return void
     */
    /**
     * @param string $aOptionKey
     * @param array|string $aOptionMeta
     * @param mixed $savedOptionValue
     */
    protected function createFormControl(string $aOptionKey, $aOptionMeta, $savedOptionValue): void
    {
        if (is_array($aOptionMeta) && count($aOptionMeta) > 2) { // Drop-down list
            $choices = array_slice($aOptionMeta, 1);
            ?>
            <p>
                <select name="<?php echo esc_attr((string)$aOptionKey); ?>" id="<?php echo esc_attr((string)$aOptionKey); ?>">
                    <?php
                    foreach ($choices as $aChoice) {
                        $selected = ($aChoice == $savedOptionValue) ? 'selected' : '';
                        ?>
                        <option value="<?php echo esc_attr((string)$aChoice); ?>" <?php echo $selected; ?>>
                            <?php echo esc_html((string)$this->getOptionValueI18nString((string)$aChoice)); ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </p>
            <?php
        } else { // Simple input field
            ?>
            <p>
                <input type="text" name="<?php echo esc_attr((string)$aOptionKey); ?>" id="<?php echo esc_attr((string)$aOptionKey); ?>"
                       value="<?php echo esc_attr((string)$savedOptionValue); ?>" size="50" style="max-width: 200px;"/>
            </p>
            <?php
        }
    }

    /**
     * The purpose of this method is to provide i18n display strings for the values of options.
     */
    protected function getOptionValueI18nString(string $optionValue): string
    {
        $pluginName = 'slick-engagement';
        switch ($optionValue) {
            case 'true':
                return (string)__('true', $pluginName);
            case 'false':
                return (string)__('false', $pluginName);
            case 'Administrator':
                return (string)__('Administrator', $pluginName);
            case 'Editor':
                return (string)__('Editor', $pluginName);
            case 'Author':
                return (string)__('Author', $pluginName);
            case 'Contributor':
                return (string)__('Contributor', $pluginName);
            case 'Subscriber':
                return (string)__('Subscriber', $pluginName);
            case 'Anyone':
                return (string)__('Anyone', $pluginName);
        }

        return (string)$optionValue;
    }
}
