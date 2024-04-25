<?php
/**
 * @file classes/External/DataCite/Api.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Api
 * @brief Api class which makes the actual requests
 */

namespace APP\plugins\generic\citationManager\classes\External\DataCite;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\External\ApiAbstract;
use Application;
use GuzzleHttp\Client;

class Api extends ApiAbstract
{
    /** @var string The base URL for API requests. */
    public string $url = 'https://api.datacite.org';

    /**
     * Api constructor.
     *
     * @param CitationManagerPlugin $plugin
     * @param string|null $url The base URL for API requests (optional).
     */
    function __construct(CitationManagerPlugin &$plugin, ?string $url = '')
    {
        parent::__construct($plugin, $url);

        $this->httpClient = new Client(
            [
                'headers' => [
                    'User-Agent' => Application::get()->getName() . '/' . CITATION_MANAGER_PLUGIN_NAME,
                    'Accept' => 'application/json'
                ],
                'verify' => false
            ]
        );
    }

    /**
     * Retrieves information about a DOI from the API.
     *
     * @param string $doi The Digital Object Identifier (DOI) to retrieve information for.
     * @return array The response body as an associative array.
     */
    public function getWork(string $doi): array
    {
        if (empty($doi)) return [];

        return $this->apiRequest('GET', $this->url . '/dois/' . $doi, []);
    }
}
