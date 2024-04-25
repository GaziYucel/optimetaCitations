<?php
/**
 * @file classes/External/OpenCitations/Api.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Api
 * @brief Api class
 */

namespace APP\plugins\generic\citationManager\classes\External\OpenCitations;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\External\ApiAbstract;
use Application;
use GuzzleHttp\Client;

class Api extends ApiAbstract
{
    /** @var string The base URL for GitHub API requests. */
    public string $url = 'https://api.github.com/repos';

    /** @var string|null The owner of the GitHub repository. */
    public ?string $owner = '';

    /** @var string|null The authentication token for GitHub API requests. */
    public ?string $token = '';

    /** @var string|null The name of the GitHub repository. */
    public ?string $repository = '';

    /**
     * @param CitationManagerPlugin $plugin
     * @param string|null $url The base URL for API requests (optional).
     */
    public function __construct(CitationManagerPlugin &$plugin, ?string $url = '')
    {
        parent::__construct($plugin, $url);

        $this->owner = $this->plugin->getSetting($this->plugin->getCurrentContextId(),
            Constants::owner);

        $this->repository = $this->plugin->getSetting($this->plugin->getCurrentContextId(),
            Constants::repository);

        $this->token = $this->plugin->getSetting($this->plugin->getCurrentContextId(),
            Constants::token);

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
     * @return string The ID of the created issue, or empty string if unsuccessful.
     */
    public function addIssue(string $title, string $body): string
    {
        if (empty($this->owner) || empty($this->token)
            || empty($this->repository) || empty($title) || empty($body)) {
            return '';
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

        if (is_numeric($result['number'] && (string)$result['number'] !== '0')) {
            return $result['number'];
        }

        return '';
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
