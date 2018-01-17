<?php

/*
 * This file is part of WordPress Plugin Foundation package.
 *
 * (c) Rocco Howard <rocco@hnh.digital>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HnhDigital\WordPressPlugin;

use GuzzleHttp\Client as GuzzleClient;

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

        if (method_exists($this, 'boot')) {
            $this->boot();
        }
    }

    /**
     * Create new class object.
     *
     * @param string $class_name
     *
     * @return object
     */
    public function initClass($class_name)
    {
        return new $class_name($this->foundation);
    }

    /**
     * Return the foundation.
     *
     * @return Foundation
     */
    public function foundation()
    {
        return $this->foundation;
    }

    /**
     * Get the plugin name.
     *
     * @return string
     */
    public function name()
    {
        return $this->foundation()->getPluginName();
    }

    /**
     * Get the plugin name.
     *
     * @return string
     */
    public function slug()
    {
        return $this->foundation()->getPluginSlug();
    }

    /**
     * Get the plugin version.
     *
     * @return string
     */
    public function version()
    {
        return $this->foundation()->getPluginVersion();
    }

    /**
     * Get the remote URL.
     *
     * @param string $sub_domain
     * @param string $path
     *
     * @return string
     */
    public function remoteUrl($sub_domain = '', $path = '')
    {
        return $this->foundation()->getRemoteUrl($sub_domain, $path);
    }

    /**
     * Get the admin URL.
     *
     * @param string $sub_domain
     * @param string $path
     *
     * @return string
     */
    public function adminUrl($path = '')
    {
        $path = !empty($path) ? '&'.$path : '';

        return admin_url('options-general.php?page='.$this->slug().$path);
    }

    /**
     * Get the local URL.
     *
     * @param string $sub_domain
     * @param string $path
     *
     * @return string
     */
    public function localUrl($path = '')
    {
        return $this->foundation()->getLocalUrl($path);
    }

    /**
     * Get the URL.
     *
     * @param string $path
     *
     * @return string
     */
    public function viewUrl($path = '')
    {
        return $this->foundation()->getViewUrl($path);
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
        if (($template_file = $this->foundation()->getViewPath($template, 'tpl.php')) === false) {
            return '';
        }

        $this->foundation()->loadViewAsset($template, 'css');
        $this->foundation()->loadViewAsset($template, 'js');

        $data['renderer'] = $this;
        
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
        $this->foundation()->addAction($this, ...$arguments);
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
        $this->foundation()->addAction($this, ...$arguments);
    }

    /**
     * Process a call request.
     *
     * @param WP_REST_Request $data
     *
     * @return array
     */
    public function callRequest($data)
    {
        $payload = $data->get_json_params();

        @ignore_user_abort(true);
        @set_time_limit(0);

        $action = $data->get_param('action');
        $echo_response = true;

        // Request is a callback.
        if (substr($action, -8) === 'Callback') {
            $echo_response = false;
        }

        // Method is not empty and it exists.
        if (!empty($action) && method_exists($this, 'call'.$action)) {

            // Action responds to WordMan and responds only true.
            if ($echo_response === false) {
                $this->callComplete();
            }

            // Complete the requested method.
            $result = $this->{'call'.$action}($data, $payload);

            return is_null($result) ? (string) true : $result;
        }

        return $this->callError('no_action_found', 'No action was found matching the request.', 400);
    }

    /**
     * Complete a call and continue processing.
     *
     * @return void
     */
    public function callComplete()
    {
        // This server is running FPM.
        if (function_exists('fastcgi_finish_request') && php_sapi_name() === 'fpm-fcgi') {            
            echo (boolean) true;
            session_write_close();
            fastcgi_finish_request();
        }

        // Server is running php in another way.
        header('Connection: close');
        header('Content-Encoding: none');
        ob_start();
        echo (boolean) true;
        header('Content-Length: '.ob_get_length());
        ob_end_flush();
        ob_flush();
        flush();
    }

    /**
     * Call remote url.
     *
     * @param string $url
     * @param array  $data
     *
     * @return void
     */
    public function callRemote($url, $data = [], $return = true)
    {
        $success = false;

        try {
            $response = (new GuzzleClient())->post($url, ['json' => $data]);
            $contents = $response->getBody()->getContents();
            $success = strlen($contents) > 0;

        } catch(\Exception $exception) {
            $contents = $exception->getMessage();
        }

        if ($return) {
            return [$success, $contents];
        }

        echo $contents;
    }

    /**
     * Return a success with message and data.
     *
     * @return array
     */
    public function successResponse($message, $data = [])
    {
        return [
            'code'    => 'success',
            'message' => $message,
            'data'    => $data,
        ];
    }

    /**
     * Return an error with code, message, and data.
     *
     * @return array
     */
    public function errorResponse($code, $message, $status)
    {
        return [
            'code'    => $code,
            'message' => $message,
            'data'    => ['status' => $status],
        ];
    }
}
