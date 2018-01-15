<?php

/*
 * This file is part of WordMan WordPress Bootstrap plugin.
 *
 * (c) WordMan <hello@wordman.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HnhDigital\WordPressPlugin;

class Foundation
{
    /**
     * Plugin name.
     *
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin version.
     *
     * @var string
     */
    private $plugin_version;

    /**
     * Plugin directory.
     *
     * @var string
     */
    private $plugin_directory;

    /**
     * Remote url.
     *
     * @var string
     */
    private $remote_url;

    /**
     * Actions.
     *
     * @var array
     */
    private $wordpress_actions = [];

    /**
     * Filters.
     *
     * @var array
     */
    private $wordpress_filters = [];

    /**
     * Initialize the class and set its properties.
     *
     * @param string $name
     * @param string $version
     * @param string $dir
     * @param string $url
     *
     * @return void
     */
    public function __construct($name, $version, $dir, $url)
    {
        // Assign properties of this plugin.
        $this->plugin_name = $name;
        $this->plugin_version = $version;
        $this->plugin_directory = $dir;
        $this->remote_url = $url;

        // Load all the classes.
        $this->load();

        // Register the action and filter hooks.
        $this->register();
    }

    /**
     * Register all the filters and actions.
     *
     * @return void
     */
    private function register()
    {
        foreach (['filter', 'action'] as $type) {
            foreach ($this->{'wordpress_'.$type.'s'} as $settings) {
                $this->addHook($type, $settings);
            }
        }
    }

    /**
     * Add a filter or action hook.
     *
     * @param string $type     
     * @param array  $settings
     *
     * @return void
     */
    private function addHook($type, $settings)
    {
        $function_name = 'add_'.$type;
        $function_name($settings['hook'], [
            $settings['component'],
            $settings['callback']
        ], $settings['priority'], $settings['accepted_args']);
    }

    /**
     * Add action.
     *
     * @param object $component     A reference to the instance of the object on which the action is defined.
     * @param string $hook          The name of the WordPress action that is being registered.
     * @param string $callback      The name of the function definition on the $component.
     * @param int    $priority      Optional. he priority at which the function should be fired. Default is 10.
     * @param int    $accepted_args Optional. The number of arguments that should be passed to the $callback. 
     *
     * @return void
     */
    public function addAction($component, $hook, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->wordpress_actions[] = [
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        ];
    }

    /**
     * Add filter.
     *
     * @param object $component     A reference to the instance of the object on which the action is defined.
     * @param string $hook          The name of the WordPress action that is being registered.
     * @param string $callback      The name of the function definition on the $component.
     * @param int    $priority      Optional. he priority at which the function should be fired. Default is 10.
     * @param int    $accepted_args Optional. The number of arguments that should be passed to the $callback. 
     *
     * @return void
     */
    public function addFilter($component, $hook, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->wordpress_filters[] = [
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        ];
    }

    /**
     * Get the plugin name.
     *
     * @return string
     */
    public function getPluginName()
    {
        return $this->plugin_name.'/'.$this->plugin_name.'.php';
    }

    /**
     * Get plugin slug.
     *
     * @return string
     */
    public function getPluginSlug()
    {
        return $this->plugin_name;
    }

    /**
     * Get the plugin version.
     *
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->plugin_version;
    }

    /**
     * Get the remote url.
     *
     * @param string $sub_domain
     * @param string $path
     *
     * @return string
     */
    public function getRemoteUrl($sub_domain = '', $path = '')
    {
        $url = $this->remote_url;
        $url = !empty($sub_domain) ? str_replace('//', '//'.$sub_domain.'.', $url) : $url;
        $url .= !empty($path) ? '/'.$path : '';

        return $url;
    }

    /**
     * Get the local url.
     *
     * @param string $path
     *
     * @return string
     */
    public function getLocalUrl($path = '')
    {
        $path = str_replace($this->getPluginPath().'/', '', $path);

        return plugins_url().'/'.$this->getPluginSlug().'/'.$path;
    }

    /**
     * Get the view url.
     *
     * @param string $path
     *
     * @return string
     */
    public function getViewUrl($path = '')
    {
        return $this->getLocalUrl('resources/view/'.$path);
    }

    /**
     * Get the plugin directory.
     *
     * @param string $path
     *
     * @return string
     */
    public function getPluginPath($path = '')
    {
        $path = $this->plugin_directory.$path;

        return $path;
    }

    /**
     * Get the view.
     *
     * @return string
     */
    public function getViewPath($path, $extension = '')
    {
        $path = str_replace('.', '/', $path);
        $extension = $extension ? '.'.$extension : '';

        $path = $this->getPluginPath('/resources/view/'.$path.$extension);

        if (!file_exists($path)) {
            return false;
        }

        return $path;
    }

    /**
     * Load assets for a given view.
     *
     * @return void
     */
    public function loadViewAsset($path, $type)
    {
        // Calculate path to asset.
        $path_array = explode('/', str_replace('.', '/', $path));
        $file_name = array_pop($path_array);
        $path_array[] = $type;
        $path_array[] = $file_name;
        $path = implode('.', $path_array);

        // Check file exists.
        if (($asset_file = $this->getViewPath($path, $type)) === false) {
            return false;
        }

        // Load the asset.
        $settings = [];

        switch ($type) {
            case 'css':
                $type = 'style';
                $location = 'all';
                break;
            case 'js':
                $type = 'script';
                $settings = ['jquery'];
                $location = false;
                break;
            default:
                return false;
        }

        $function_name = 'wp_enqueue_'.$type;
        $function_name($this->getPluginSlug(), $this->getLocalUrl($asset_file), $settings, $this->getPluginVersion(), $location);

        return true;
    }
}