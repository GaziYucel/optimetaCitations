<?php
/**
 * @file classes/External/ApiAbstract.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ApiAbstract
 * @brief Abstract Api class to be extended by Api classes.
 */

namespace APP\plugins\generic\citationManager\classes\External;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\Helpers\LogHelper;
use APP\plugins\generic\citationManager\classes\Settings\PluginConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

abstract class ApiAbstract
{
    /** @var CitationManagerPlugin */
    public CitationManagerPlugin $plugin;

    /** @var string The base URL for API requests. */
    public string $url = '';

    /** @var Client $httpClient The HTTP client for making API requests. */
    public Client $httpClient;

    /**
     * @param CitationManagerPlugin $plugin
     * @param string|null $url The base URL for API requests (optional).
     */
    function __construct(CitationManagerPlugin &$plugin, ?string $url = '')
    {
        $this->plugin = &$plugin;
        if (!empty($url)) $this->url = $url;
    }

    /**
     * Makes HTTP request to the API and returns the response as an array.
     *
     * @param string $method The HTTP method (e.g., 'POST', 'GET').
     * @param string $url The API endpoint URL.
     * @param array $options Additional options for the HTTP request.
     * @return array The response data as an associative array.
     */
    public function apiRequest(string $method, string $url, array $options): array
    {
        if ($method !== 'POST' && $method !== 'GET') return [];

        try {
            $response = $this->httpClient->request($method, $url, $options);

            if (!str_contains('200,201,202', (string)$response->getStatusCode())) {
                return [];
            }

            $result = json_decode($response->getBody(), true);

            if (empty($result) || json_last_error() !== JSON_ERROR_NONE) return [];

            if (PluginConfig::isDebugMode()) {
                LogHelper::logInfo([$method, $url, $options, $response->getStatusCode(), $result]);
            }

            return $result;

        } catch (GuzzleException $e) {
            error_log(__METHOD__ . ' ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Checks whether enrich is possible for this service
     *
     * @return bool
     */
    public function isEnrichPossible(): bool
    {
        return true;
    }

    /**
     * Checks whether deposit is possible for this service
     *
     * @return bool
     */
    public function isDepositPossible(): bool
    {
        return true;
    }
}
