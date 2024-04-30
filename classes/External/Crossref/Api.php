<?php
/**
 * @file classes/External/CrossRef/Api.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Api
 * @brief Api class which makes the actual requests
 */

namespace APP\plugins\generic\citationManager\classes\External\Crossref;

use Application;
use APP\plugins\generic\citationManager\classes\External\ApiAbstract;
use GuzzleHttp\Client;

class Api extends ApiAbstract
{
    /** @copydoc ApiAbstract::__construct */
    function __construct(?array $args = [])
    {
        $args['url'] = Constants::apiUrl;
        parent::__construct($args);

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
     * Retrieves information about a work from the CrossRef API based on bibliographic search.
     *
     * @param string $search The bibliographic information to search for.
     * @return array The response body as an associative array.
     */
    public function getwork(string $search): array
    {
        if (empty($search)) return [];

        return $this->apiRequest('GET', $this->url . '/works/?query.bibliographic=' . $search, []);
    }
}
