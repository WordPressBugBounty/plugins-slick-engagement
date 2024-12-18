<?php 
declare(strict_types=1);
namespace Slickstream;

class OptionsManager {
    private $optionNamePrefix;

    public function __construct() {
        // Note: Do not change this or else settings from previous versions will not be able to be accessed
        $this->optionNamePrefix = 'SlickEngagement_Plugin_';
    }

    /**
     * Define your options meta data here as an array, where each element in the array
     * @return array of key=>display-name and/or key=>array(display-name, choice1, choice2, ...)
     * key: an option name for the key (this name will be given a prefix when stored in
     * the database to ensure it does not conflict with other plugin options)
     * value: can be one of two things:
     *   (1) string display name for displaying the name of the option to the user on a web page
     *   (2) array where the first element is a display name (as above) and the rest of
     *       the elements are choices of values that the user can select
     * e.g.
     * array(
     *   'item' => 'Item:',             // key => display-name
     *   'rating' => array(             // key => array ( display-name, choice1, choice2, ...)
     *       'CanDoOperationX' => array('Can do Operation X', 'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber'),
     *       'Rating:', 'Excellent', 'Good', 'Fair', 'Poor')
     */
    public function getOptionMetaData(): array {
        return [];
    }

    /**
     * @return array of string name of options
     */
    public function getOptionNames(): array {
        return array_keys($this->getOptionMetaData());
    }

    /**
     * Override this method to initialize options to default values and save to the database with add_option
     * @return void
     */
    protected function initOptions(): void {}

    /**
     * Cleanup: remove all options from the DB
     * @return void
     */
    protected function deleteSavedOptions(): void  {
        $optionMetaData = $this->getOptionMetaData();
        if (is_array($optionMetaData)) {
            foreach ($optionMetaData as $aOptionKey => $aOptionMeta) {
                $prefixedOptionName = $this->prefix($aOptionKey);
                delete_option($prefixedOptionName);
            }
        }
    }

    /**
     * @return string display name of the plugin to show as a name/title in HTML.
     * Just returns the class name. Override this method to return something more readable
     */
    public function getPluginDisplayName(): string {
        return 'Slickstream Engagement';
    }
    
    /**
     * Get the prefixed version input $name suitable for storing in WP options
     * Idempotent: if $optionName is already prefixed, it is not prefixed again, it is returned without change
     * @param  $name string option name to prefix. Defined in settings.php and set as keys of $this->optionMetaData
     * @return string
     */
    public function prefix($name): string {
        if (strpos($name, $this->optionNamePrefix) === 0) { // 0 but not false
            return $name; // already prefixed
        }
        return $this->optionNamePrefix . $name;
    }

    /**
     * A wrapper function delegating to WP get_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     * @param $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @param $default string default value to return if the option is not set
     * @return string the value from delegated call to get_option(), or optional default value
     * if option is not set.
     */
    public function getOption($optionName, $default = null): string {
        $prefixedOptionName = $this->prefix($optionName);
        $retVal = get_option($prefixedOptionName);
        if (!$retVal && $default) {
            $retVal = $default;
        }
        return (string) $retVal;
    }

    /**
     * A wrapper function delegating to WP delete_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     * @param  $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @return bool from delegated call to delete_option()
     */
    public function deleteOption($optionName): bool {
        $prefixedOptionName = $this->prefix($optionName);
        return delete_option($prefixedOptionName);
    }

    /**
     * A wrapper function delegating to WP add_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     * @param  $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @param  $value mixed the new value
     * @return null from delegated call to delete_option()
     */
    public function addOption($optionName, $value): bool {
        $prefixedOptionName = $this->prefix($optionName);
        return add_option($prefixedOptionName, $value);
    }

    /**
     * A wrapper function delegating to WP add_option() but it prefixes the input $optionName
     * to enforce "scoping" the options in the WP options table thereby avoiding name conflicts
     * @param  $optionName string defined in settings.php and set as keys of $this->optionMetaData
     * @param  $value mixed the new value
     * @return null from delegated call to delete_option()
     */
    public function updateOption($optionName, $value): bool {
        $prefixedOptionName = $this->prefix($optionName);
        return update_option($prefixedOptionName, $value);
    }

    /**
     * A Role Option is an option defined in getOptionMetaData() as a choice of WP standard roles, e.g.
     * 'CanDoOperationX' => array('Can do Operation X', 'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber')
     * The idea is use an option to indicate what role level a user must minimally have in order to do some operation.
     * So if a Role Option 'CanDoOperationX' is set to 'Editor' then users which role 'Editor' or above should be
     * able to do Operation X.
     * Also see: canUserDoRoleOption()
     * @param  $optionName
     * @return string role name
     */
    public function getRoleOption($optionName): string {
        $roleAllowed = $this->getOption($optionName);
        if (!$roleAllowed || $roleAllowed == '') {
            $roleAllowed = 'Administrator';
        }
        return $roleAllowed;
    }

    /**
     * Given a WP role name, return a WP capability which only that role and roles above it have
     * http://codex.wordpress.org/Roles_and_Capabilities
     * @param  $roleName
     * @return string a WP capability or '' if unknown input role
     */
    protected function roleToCapability($roleName): string {
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
     * @param $roleName string a standard WP role name like 'Administrator'
     * @return bool
     */
    public function isUserRoleEqualOrBetterThan($roleName): bool {
        if ('Anyone' == $roleName) {
            return true;
        }
        $capability = $this->roleToCapability($roleName);
        return current_user_can($capability);
    }

    /**
     * @param  $optionName string name of a Role option (see comments in getRoleOption())
     * @return bool indicates if the user has adequate permissions
     */
    public function canUserDoRoleOption($optionName): bool {
        $roleAllowed = $this->getRoleOption($optionName);
        if ('Anyone' == $roleAllowed) {
            return true;
        }
        return $this->isUserRoleEqualOrBetterThan($roleAllowed);
    }

    /**
     * Creates HTML for the Administration page to set options for this plugin.
     * Override this method to create a customized page.
     * @return void
     */
    public function settingsPage(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'slick-engagement'));
        }

        $optionMetaData = $this->getOptionMetaData();

        // Save Posted Options
        if ($optionMetaData != null) {
            foreach ($optionMetaData as $aOptionKey => $aOptionMeta) {
                if (isset($_POST[$aOptionKey])) {
                    $this->updateOption($aOptionKey, $_POST[$aOptionKey]);
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
                To use this service, you need to sign up and get a site code.  Enter that code below in the Site Code field.</p>
              <p>The remaining settings are optional.</p>
            </div>
            <form method="post" action="">
            <?php settings_fields($settingsGroup);?>
                <style type="text/css">
                    table.plugin-options-table {padding: 0; border-collapse: collapse;}
                    table.plugin-options-table td {vertical-align: middle; padding: 0 5px;}
                    table.plugin-options-table td+td {width: auto}
                    table.plugin-options-table td > p {margin-top: 0; margin-bottom: 0;}
                    table.plugin-options-table th {text-align:right; padding:0 5px;}
                </style>
                <table class="plugin-options-table"><tbody>
                <?php
if ($optionMetaData != null) {
            foreach ($optionMetaData as $aOptionKey => $aOptionMeta) {
                $displayText = is_array($aOptionMeta) ? $aOptionMeta[0] : $aOptionMeta;
                ?>
                            <tr valign="top">
                                <th scope="row" style="text-align:right;padding-right:5px;"><p><label for="<?php echo $aOptionKey ?>"><?php echo $displayText ?></label></p></th>
                                <td>
                                <?php $this->createFormControl($aOptionKey, $aOptionMeta, $this->getOption($aOptionKey));?>
                                </td>
                            </tr>
                        <?php
}
        } //if ($optionMetaData != null) {
        ?>
                </tbody></table>
                <p class="submit">
                    <input type="submit" class="button-primary"
                           value="<?php _e('Save Changes', 'slick-engagement')?>"/>
                </p>
            </form>
            <div style="max-width: 600px;">
              <p>The Service URL should be left blank unless directed otherwise when setting up your service.</p>
              <p>Problems or customization?  <a href="mailto:support@slickstream.com">Contact us</a>.</p>
            </div>
        </div>
        <?php
}

    /**
     * Helper-function outputs the correct form element (input tag, select tag) for the given item
     * @param  $aOptionKey string name of the option (un-prefixed)
     * @param  $aOptionMeta mixed meta-data for $aOptionKey (either a string display-name or an array(display-name, option1, option2, ...)
     * @param  $savedOptionValue string current value for $aOptionKey
     * @return void
     */
    protected function createFormControl($aOptionKey, $aOptionMeta, $savedOptionValue): void {
        if (is_array($aOptionMeta) && count($aOptionMeta) > 2) { // Drop-down list
            $choices = array_slice($aOptionMeta, 1);
            ?>
            <p><select name="<?php echo $aOptionKey ?>" id="<?php echo $aOptionKey ?>">
            <?php
foreach ($choices as $aChoice) {
                $selected = ($aChoice == $savedOptionValue) ? 'selected' : '';
                ?>
                    <option value="<?php echo $aChoice ?>" <?php echo $selected ?>><?php echo $this->getOptionValueI18nString($aChoice) ?></option>
                <?php
}
            ?>
            </select></p>
            <?php

        } else { // Simple input field
            ?>
            <p><input type="text" name="<?php echo $aOptionKey ?>" id="<?php echo $aOptionKey ?>"
                      value="<?php echo esc_attr($savedOptionValue) ?>" size="50" style="max-width: 200px;"/></p>
            <?php

        }
    }

    /**
     * Override this method and follow its format.
     * The purpose of this method is to provide i18n display strings for the values of options.
     * For example, you may create a options with values 'true' or 'false'.
     * In the options page, this will show as a drop down list with these choices.
     * But when the the language is not English, you would like to display different strings
     * for 'true' and 'false' while still keeping the value of that option that is actually saved in
     * the DB as 'true' or 'false'.
     * To do this, follow the convention of defining option values in getOptionMetaData() as canonical names
     * (what you want them to literally be, like 'true') and then add each one to the switch statement in this
     * function, returning the "__()" i18n name of that string.
     * @param  $optionValue string
     * @return string __($optionValue) if it is listed in this method, otherwise just returns $optionValue
     */
    protected function getOptionValueI18nString($optionValue): string {
        switch ($optionValue) {
            case 'true':
                return __('true', 'slick-engagement');
            case 'false':
                return __('false', 'slick-engagement');

            case 'Administrator':
                return __('Administrator', 'slick-engagement');
            case 'Editor':
                return __('Editor', 'slick-engagement');
            case 'Author':
                return __('Author', 'slick-engagement');
            case 'Contributor':
                return __('Contributor', 'slick-engagement');
            case 'Subscriber':
                return __('Subscriber', 'slick-engagement');
            case 'Anyone':
                return __('Anyone', 'slick-engagement');
        }
        return $optionValue;
    }
}
