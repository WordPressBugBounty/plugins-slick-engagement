<?php

declare(strict_types=1);

namespace Slickstream;

require_once 'InstallIndicator.php';

class PluginLifecycle extends InstallIndicator
{
    public function __construct()
    {
        parent::__construct();
    }

    public function install(): void
    {

        // Initialize Plugin Options
        $this->initOptions();

        // Initialize DB Tables used by the plugin
        $this->installDatabaseTables();

        // Other Plugin initialization - for the plugin writer to override as needed
        $this->otherInstall();

        // Record the installed version
        $this->saveInstalledVersion();

        // To avoid running install() more then once
        $this->markAsInstalled();
    }

    public function uninstall(): void
    {
        $this->otherUninstall();
        $this->unInstallDatabaseTables();
        $this->deleteSavedOptions();
        $this->markAsUnInstalled();
    }

    /**
     * Perform any version-upgrade activities prior to activation (e.g. database changes)
     * @return void
     */
    public function upgrade(): void
    {
    }

    /**
     * @return void
     */
    public function activate(): void
    {
    }

    /**
     * @return void
     */
    public function deactivate(): void
    {
    }

    /**
     * @return void
     */
    protected function initOptions(): void
    {
    }

    public function addActionsAndFilters(): void
    {
    }

    /**
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables(): void
    {
    }

    /**
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables(): void
    {
    }

    /**
     * Override to add any additional actions to be done at install time
     * @return void
     */
    protected function otherInstall(): void
    {
    }

    /**
     * Override to add any additional actions to be done at uninstall time
     * @return void
     */
    protected function otherUninstall(): void
    {
    }

    protected function requireExtraPluginFiles(): void
    {
        require_once ABSPATH . WPINC . '/pluggable.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }


    /**
     * Returns the full prefixed table name for this plugin.
     *
     * @param string $name
     * @return string
     */
    protected function prefixTableName(string $name): string
    {
        global $wpdb;
        $prefix = (isset($wpdb) && is_object($wpdb) && isset($wpdb->prefix)) ? (string)$wpdb->prefix : '';
        $pluginPrefix = $this->prefix($name);
        return $prefix . strtolower((string)$pluginPrefix);
    }

    public function getAjaxUrl(string $actionName): string
    {
        $adminUrl = admin_url('admin-ajax.php');
        $adminUrlStr = is_string($adminUrl) ? $adminUrl : '';
        $actionNameStr = strip_tags($actionName);
        return "$adminUrlStr?action=$actionNameStr";
    }
}
