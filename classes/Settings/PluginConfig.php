<?php
/**
 * @file classes/Settings/PluginConfig.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PluginConfig
 * @brief Plugin Configuration
 */

namespace APP\plugins\generic\citationManager\classes\Settings;

use PKP\config\Config;

class PluginConfig
{
    /**
     * Get isDebugMode from config, return false if setting not present
     *
     * @return bool
     */
    public static function isDebugMode(): bool
    {
        $config_value = Config::getVar(CITATION_MANAGER_PLUGIN_NAME, 'isDebugMode');

        if (!empty($config_value)
            && (strtolower($config_value) === 'true' || (string)$config_value === '1')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get isTestMode from config, return false if setting not present
     *
     * @return bool
     */
    public static function isTestMode(): bool
    {
        $config_value = Config::getVar(CITATION_MANAGER_PLUGIN_NAME, 'isTestMode');

        if (!empty($config_value)
            && (strtolower($config_value) === 'true' || (string)$config_value === '1')
        ) {
            return true;
        }

        return false;
    }
}
