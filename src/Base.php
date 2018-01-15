<?php

/*
 * This file is part of WordMan WordPress Bootstrap plugin.
 *
 * (c) WordMan <hello@wordman.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WordMan;

class Base
{
    /**
     * WordMan.
     * 
     * @var WordMan
     */
    private $wordman;

    /**
     * Initialize the class.
     *
     * @return void
     */
    public function __construct(&$wordman)
    {
        $this->wordman = &$wordman;

        $this->boot();
    }

    /**
     * Get the plugin name.
     *
     * @return string
     */
    public function name()
    {
        return $this->wordman->getPluginName();
    }

    /**
     * Get the plugin version.
     *
     * @return string
     */
    public function version()
    {
        return $this->wordman->getPluginVersion();
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
        return $this->wordman->getWordManUrl($sub_domain, $path);
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
        if (($template_file = $this->wordman->getViewPath($template, 'php')) === false) {
            return '';
        }

        $this->wordman->loadViewAsset($template, 'css');
        $this->wordman->loadViewAsset($template, 'js');

        $data['wordman'] = $this;
        
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
        $this->wordman->addAction(...$arguments);
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
        $this->wordman->addAction(...$arguments);
    }
}
