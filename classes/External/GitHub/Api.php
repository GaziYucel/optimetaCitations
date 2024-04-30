<?php
/**
 * @file classes/External/GitHub/Api.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Api
 * @brief Api class
 */

namespace APP\plugins\generic\citationManager\classes\External\GitHub;

use APP\plugins\generic\citationManager\classes\External\ApiAbstract;
use Application;
use GuzzleHttp\Client;

class Api extends ApiAbstract
{
    /** @var string|null The owner of the GitHub repository. */
    public ?string $owner = '';

    /** @var string|null The authentication token for GitHub API requests. */
    public ?string $token = '';

    /** @var string|null The name of the GitHub repository. */
    public ?string $repository = '';

    /** @copydoc ApiAbstract::__construct */
    function __construct(?array $args = [])
    {
        $args['url'] = Constants::apiUrl;
        parent::__construct($args);

        $this->httpClient = new Client([
            'headers' => [
                'User-Agent' => Application::get()->getName() . '/' . CITATION_MANAGER_PLUGIN_NAME,
                'Accept' => 'application/vnd.github.v3+json',
                'Authorization' => 'token ' . $this->token
            ],
            'verify' => false
        ]);
    }

    /**
     * Adds an issue to a given repository and returns the issue ID.
     *
     * @param string $title The title of the issue.
     * @param string $body The body or description of the issue.
     * @return int The ID of the created issue, or 0 if unsuccessful.
     */
    public function addIssue(string $title, string $body): int
    {
        if (empty($this->owner) || empty($this->token)
            || empty($this->repository) || empty($title) || empty($body)) {
            return 0;
        }

        $result = $this->apiRequest(
            'POST',
            $this->url . "/$this->owner/$this->repository/issues",
            [
                'json' =>
                    [
                        'title' => $title,
                        'body' => $body,
                        'labels' => ['Deposit']
                    ]
            ]);

        if (!empty($result['number']) && is_numeric($result['number'])) {
            return (int)$result['number'];
        }

        return 0;
    }

    /**
     * Checks whether deposits possible for this service
     *
     * @return bool
     */
    public function isDepositPossible(): bool
    {
        if (empty($this->owner) || empty($this->repository) || empty($this->token)) {
            return false;
        }

        return true;
    }
}
