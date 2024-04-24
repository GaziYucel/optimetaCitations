<?php
/**
 * @file classes/External/Wikidata/Api.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Api
 * @brief Api class
 */

namespace APP\plugins\generic\citationManager\classes\External\Wikidata;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\External\ApiAbstract;
use APP\plugins\generic\citationManager\classes\Helpers\ArrayHelper;
use Application;
use GuzzleHttp\Client;

class Api extends ApiAbstract
{
    /** @var string $url The base URL for API requests. */
    public string $url = 'https://www.wikidata.org/w/api.php';

    /** @var string|null $username The username for authentication. */
    public ?string $username = '';

    /** @var string|null $password The password for authentication. */
    public ?string $password = '';

    /** @var bool $isLoggedIn Whether the client is logged in. */
    public bool $isLoggedIn = false;

    /** @var string|null $loginToken The login token. */
    public ?string $loginToken = '';

    /** @var string|null $csrfToken The CSRF token. */
    public ?string $csrfToken = '';

    /**
     * @param CitationManagerPlugin $plugin
     * @param string|null $url The base URL for API requests (optional).
     */
    function __construct(CitationManagerPlugin &$plugin, ?string $url = '')
    {
        parent::__construct($plugin, $url);

        $this->username = $this->plugin->getSetting(
            $this->plugin->getCurrentContextId(),
            Constants::username,);

        $this->password = $this->plugin->getSetting(
            $this->plugin->getCurrentContextId(),
            Constants::password);

        $this->httpClient = new Client(
            [
                'headers' =>
                    [
                        'User-Agent' => Application::get()->getName() . '/' . CITATION_MANAGER_PLUGIN_NAME,
                        'Accept' => 'application/json'
                    ],
                'verify' => false,
                'cookies' => true
            ]
        );

        // login if username/password provided; else proceed anonymously
        if (!empty($this->username) && !empty($this->password)) {
            $this->loginToken = $this->getLoginToken();
            $this->isLoggedIn = $this->login();
            if ($this->isLoggedIn) $this->csrfToken = $this->getCsrfToken();
        }
    }

    /**
     * Logs in the account.
     * Example response {"login":{"result":"Success","lguserid":1234,"lgusername":"MyUsername"}}
     *
     * @return bool Whether the login was successful.
     */
    public function login(): bool
    {
        $action = 'login';
        $query = ['lgname' => $this->username];
        $form['lgname'] = $this->username;
        $form['lgpassword'] = $this->password;
        $form['lgtoken'] = $this->loginToken;

        $response = $this->actionPost($action, $query, $form);

        if (empty($response)) return false;

        if (!empty($response['login']['result']) && strtolower($response['login']['result']) == 'success')
            return true;

        return false;
    }

    /**
     * Logs the account out of the wiki and destroys all their session data.
     *
     * @return bool Whether the logout was successful.
     */
    public function logout(): bool
    {
        if (!empty($this->username) && !empty($this->loginToken)) return false;

        $action = 'logout';
        $query['lgname'] = $this->username;
        $form['token'] = $this->csrfToken;

        $response = $this->actionPost($action, $query, $form);

        if (empty($response)) return true;

        return false;
    }

    /**
     * Gets and sets the (login) token.
     *
     * @return string The login token.
     */
    public function getLoginToken(): string
    {
        $action = 'query';
        $query['meta'] = 'tokens';
        $query['type'] = 'login';

        $response = $this->actionGet($action, $query);

        if (empty($response)) return '';

        if (!empty($response['query']['tokens']['logintoken']))
            return $response['query']['tokens']['logintoken'];

        return '';
    }

    /**
     * Gets and sets the (CSRF) token.
     *
     * @return string The CSRF token.
     */
    public function getCsrfToken(): string
    {
        $action = 'query';
        $query['meta'] = 'tokens';
        $query['type'] = 'csrf';

        $response = $this->actionGet($action, $query);

        if (empty($response)) return '';

        if (!empty($response['query']['tokens']['csrftoken']))
            return $response['query']['tokens']['csrftoken'];

        return '';
    }

    /**
     * Checks whether deposits possible for this service
     *
     * @return bool
     */
    public function isDepositPossible(): bool
    {
        if ($this->isLoggedIn) return true;

        return false;
    }

    /**
     * Search for pid and return search results
     *
     * @param string $pid e.g. doi, orcid_id
     * @return array [ entities ]
     */
    public function search(string $pid): array
    {
        $action = 'query';
        $query['prop'] = '';
        $query['list'] = 'search';
        $query['srsearch'] = $pid;
        $query['srlimit'] = '5';

        $response = $this->actionGet($action, $query);

        // not found
        if (empty($response) || empty($response['query']['search'])) return [];

        return $response['query']['search'];
    }

    /**
     * Match and return if found
     *
     * @param string $property
     * @param string $pid e.g. doi, orcid_id
     * @return array
     * @see https://www.wikidata.org/wiki/Special:ApiSandbox#action=wbgetentities&ids=Q106622495
     */
    public function getItemWithPropertyAndPid(string $property, string $pid): array
    {
        $action = 'wbgetentities';

        $entities = $this->search($pid);

        foreach ($entities as $entity) {

            $qid = $entity['title'];
            $query['ids'] = $qid;

            $response = $this->actionGet($action, $query);

            if (!empty($response['entities'][$qid])) {

                $item = $response['entities'][$qid];

                $pidCheck = ArrayHelper::getValue($item,
                    ['claims', $property, 0, 'mainsnak', 'datavalue', 'value']);

                if (strtolower($pid) === strtolower($pidCheck))
                    return $item;
            }
        }

        return [];
    }

    /**
     * Return qid in $item object (array)
     *
     * @param array $item
     * @return string
     */
    public function getQidFromItem(array $item): string
    {
        if (!empty($item['title']))
            return $item['title'];

        return '';
    }

    /**
     * Add item and return qid
     *
     * @param array $data
     * @return string
     */
    public function addItemAndReturnQid(array $data): string
    {
        $action = 'wbeditentity';
        $query['new'] = 'item';
        $form['data'] = json_encode($data);

        $response = $this->actionPost($action, $query, $form);

        if (!empty($response['entity']['id']))
            return $response['entity']['id'];

        return '';
    }

    /**
     * Get item
     *
     * @param string $qid
     * @return array
     */
    public function getItemWithQid(string $qid): array
    {
        $action = 'wbgetentities';
        $query['ids'] = $qid;

        $response = $this->actionGet($action, $query);

        if (!empty($response['entities'][$qid]))
            return $response['entities'][$qid];

        return [];
    }

    /**
     * Create claim
     *
     * @param string $referencingQid
     * @param string $property
     * @param array $value
     * @return bool
     */
    public function createWikibaseItemClaim(string $referencingQid, string $property, array $value): bool
    {
        $action = 'wbcreateclaim';
        $query['entity'] = $referencingQid;
        $query['snaktype'] = "value";
        $query['property'] = $property;
        $form['value'] = json_encode($value);

        $response = $this->actionPost($action, $query, $form);

        if (!empty($response['success']) && $response['success'] === 1)
            return true;

        return false;
    }

    /**
     * Execute action (GET) against API and returns the body.
     *
     * @param string $action The API action.
     * @param array $query Additional query parameters.
     * @return array The response body as an associative array.
     */
    public function actionGet(string $action, array $query = []): array
    {
        $query['action'] = $action;
        $query['format'] = 'json';
        $query['formatversion'] = '2';
        if ($action === 'wbeditentity') $query['bot'] = '1';

        return $this->apiRequest(
            'GET',
            $this->url . '?' . http_build_query($query),
            []
        );
    }

    /**
     * Execute action (POST) against API and returns the body.
     *
     * @param string $action The API action.
     * @param array|null $query Additional query parameters.
     * @param array|null $form Form parameters.
     * @return array The response body as an associative array.
     */
    public function actionPost(string $action, ?array $query = null, ?array $form = null): array
    {
        $query['action'] = $action;
        $query['format'] = 'json';
        $query['formatversion'] = '2';
        if ($action === 'wbeditentity') $query['bot'] = '1';

        if (!empty($this->csrfToken)) $form['token'] = $this->csrfToken;

        return $this->apiRequest(
            'POST',
            $this->url . '?' . http_build_query($query),
            [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'form_params' => $form
            ]
        );
    }
}
