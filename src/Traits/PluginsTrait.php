<?php

/*
 * This file is part of WordPress Plugin Foundation package.
 *
 * (c) Rocco Howard <rocco@hnh.digital>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HnhDigital\WordPressPlugin\Traits;

use GuzzleHttp\Client as GuzzleClient;

trait PluginsTrait
{
    /**
     * Install plugin.
     *
     * @return bool
     */
    private function installPlugin($destination_path, $source_url, $source_hash = false)
    {
        global $wp_filesystem;

        // Include plugin functions.
        include_once ABSPATH . 'wp-admin/includes/file.php';
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        // Initiate the file system.
        WP_Filesystem();

        // Download.
        if (($downloaded_path = $this->downloadPlugin($source_url, $source_hash)) === false) {
            return false;
        }

        $upgrade_path = $destination_path;

        if ($wp_filesystem->is_dir($destination_path)) {
            $upgrade_path = WP_PLUGIN_DIR.'/'.hash('sha256', $source_url);
        }

        $plugin_installed = unzip_file($downloaded_path, $upgrade_path);

        // Move plugin to destination path.
        if ($upgrade_path !== $destination_path) {
            // Remove existing plugin.
            $wp_filesystem->delete($destination_path, true);

            // Move new content.
            $wp_filesystem->move($upgrade_path, $destination_path);
        }

        // Remove temporary file.
        unlink($downloaded_path);

        // Clear plugin cache.
        wp_clean_plugins_cache(true);

        return $plugin_installed;
    }

    /**
     * Download the plugin.
     *
     * @param string $url
     *
     * @return bool
     */
    private function downloadPlugin($url, $source_hash = false)
    {
        // Temporary path.
        $downloaded_path = wp_tempnam(hash('sha256', $url));

        try {
            $client = new GuzzleClient();
            $response = $client->get($url);

            if ($response->getStatusCode() == 200) {
                // Get the contents.
                $contents = $response->getBody()->getContents();

                // Put the contents into the temporary file.
                file_put_contents($downloaded_path, $contents);

                $download_hash = hash_file('sha256', $downloaded_path);

                if ($download_hash == $source_hash) {
                    return $downloaded_path;
                }

                // Remove temporary file.
                unlink($downloaded_path);
            }
        } catch (\Exception $e) {
        }

        return false;
    }
}
