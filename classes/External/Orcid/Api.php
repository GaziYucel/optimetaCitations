<?php
/**
 * @file classes/External/Orcid/Api.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Orcid
 * @brief Orcid class for Orcid
 */

namespace APP\plugins\generic\citationManager\classes\External\Orcid;

use APP\plugins\generic\citationManager\classes\External\ApiAbstract;
use Application;
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
     * Gets json object from API and returns the body of the response as array
     *
     * @param string $orcidId
     * @return array
     */
    public function getPerson(string $orcidId): array
    {
        if (empty($orcidId)) return [];

        return $this->apiRequest('GET', $this->url . '/' . $orcidId, []);
    }
}
