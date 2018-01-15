<?php

/*
 * This file is part of WordMan WordPress Bootstrap plugin.
 *
 * (c) WordMan <hello@foundation.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HnhDigital\WordPressPlugin;

class Base
{
    /**
     * Foundation.
     * 
     * @var WordMan
     */
    private $foundation;

    /**
     * Initialize the class.
     *
     * @return void
     */
    public function __construct(&$foundation)
    {
        $this->foundation = &$foundation;

        $this->boot();
    }

    /**
     * Get the plugin name.
     *
     * @return string
     */
    public function name()
    {
        return $this->foundation->getPluginName();
    }

    /**
     * Get the plugin version.
     *
     * @return string
     */
    public function version()
    {
        return $this->foundation->getPluginVersion();
    }

    /**
     * Get the URL.
     *
     * @param string $sub_domain
     * @param string $path
     *
     * @return string
     */
    public function remoteUrl($sub_domain, $path = '')
    {
        return $this->foundation->getRemoteUrl($sub_domain, $path);
    }

    /**
     * Render the template.
     *
     * @param string $template
     * @param array  $data
     *
     * @return string
     */
    public function render($template, $data = [])
    {
        if (($template_file = $this->foundation->getViewPath($template, 'php')) === false) {
            return '';
        }

        $this->foundation->loadViewAsset($template, 'css');
        $this->foundation->loadViewAsset($template, 'js');

        $data['foundation'] = $this;
        
        ob_start();
        extract($data);
        include($template_file);
        $render = ob_get_contents();
        ob_clean();

        return $render;
    }

    /**
     * Add action.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function action(...$arguments)
    {
        $this->foundation->addAction(...$arguments);
    }

    /**
     * Add filter.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function filter(...$arguments)
    {
        $this->foundation->addAction(...$arguments);
    }
}
