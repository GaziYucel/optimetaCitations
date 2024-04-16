<?php
/**
 * @file classes/Handlers/PluginAPIHandler.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PluginAPIHandler
 * @brief This class handles API requests related to processing and depositing citations for publications.
 */

namespace APP\plugins\generic\citationManager\classes\Handlers;

import('lib.pkp.classes.handler.APIHandler');
import('lib.pkp.classes.security.authorization.PolicySet');
import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');

use APIResponse;
use APIHandler;
use APP\plugins\generic\citationManager\CitationManagerPlugin;
use PKP\core\APIRouter;
use PolicySet;
use RoleBasedHandlerOperationPolicy;
use Slim\Http\Request as SlimRequest;
use Slim\Http\Response;
use Throwable;

class PluginAPIHandler extends APIHandler
{
    /** @var array Structure of the response body */
    private array $responseBody = [
        'status' => 'ok',
        'message-type' => 'empty',
        'message-version' => '1',
        'message' => [
            'metadataJournal' => [],
            'metadataPublication' => [],
            'citations' => [],
            'authors' => []
        ]
    ];

    public function __construct()
    {
        $this->_handlerPath = CITATION_MANAGER_PLUGIN_NAME;

        // Configure API endpoints
        $this->_endpoints = [
            'POST' => [
                [
                    'pattern' => $this->getEndpointPattern() . '/process',
                    'handler' => [$this, 'process'],
                    'roles' => CitationManagerPlugin::apiRoles,
                ],
                [
                    'pattern' => $this->getEndpointPattern() . '/deposit',
                    'handler' => [$this, 'deposit'],
                    'roles' => CitationManagerPlugin::apiRoles,
                ]
            ],
            'GET' => []
        ];

        parent::__construct();
    }

    /**
     * Register custom endpoint
     *
     * @param $hookName
     * @param $args
     * @return void
     * @throws Throwable
     */
    public function register($hookName, $args): void
    {
        $request = $args[0];
        $router = $request->getRouter();

        if ($router instanceof APIRouter
            && str_contains($request->getRequestPath(), 'api/v1/' . CITATION_MANAGER_PLUGIN_NAME)
        ) {
            $handler = new PluginAPIHandler();
            $router->setHandler($handler);
            $handler->getApp()->run();
            exit;
        }
    }

    /** @copydoc PKPHandler::authorize() */
    public function authorize($request, &$args, $roleAssignments): bool
    {
        $rolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

        foreach ($roleAssignments as $role => $operations) {
            $rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
        }
        $this->addPolicy($rolePolicy);

        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Handles the processing of raw citations.
     *
     * @param SlimRequest $slimRequest
     * @param APIResponse $response
     * @param array $args
     * @return Response
     */
    public function process(SlimRequest $slimRequest, APIResponse $response, array $args): Response
    {
        $request = $this->getRequest();

        if (!empty($request->getUserVars()) && sizeof($request->getUserVars()) > 0) {
            if (isset($request->getUserVars()['submissionId']))
                $submissionId = trim($request->getUserVars()['submissionId']);
            if (isset($request->getUserVars()['publicationId']))
                $publicationId = trim($request->getUserVars()['publicationId']);
            if (isset($request->getUserVars()['citationsRaw']))
                $citationsRaw = trim($request->getUserVars()['citationsRaw']);
        }

        if (empty($submissionId) || empty($publicationId) || empty($citationsRaw))
            return $response->withJson($this->responseBody, 200);

        $process = new ProcessHandler();
        $process->execute((int)$submissionId, (int)$publicationId, $citationsRaw);

        $this->responseBody['message-type'] = 'process';
        $this->responseBody['message']['metadataJournal'] = $process->getMetadataJournal();
        $this->responseBody['message']['metadataPublication'] = $process->getMetadataPublication();
        $this->responseBody['message']['citations'] = $process->getCitations();
        $this->responseBody['message']['authors'] = $process->getAuthors();

        return $response->withJson($this->responseBody, 200);
    }

    /**
     * Handles the deposition of citations.
     *
     * @param SlimRequest $slimRequest
     * @param APIResponse $response
     * @param array $args
     * @return Response
     */
    public function deposit(SlimRequest $slimRequest, APIResponse $response, array $args): Response
    {
        $request = $this->getRequest();

        if (!empty($request->getUserVars()) && sizeof($request->getUserVars()) > 0) {
            if (isset($request->getUserVars()['submissionId']))
                $submissionId = trim($request->getUserVars()['submissionId']);
            if (isset($request->getUserVars()['publicationId']))
                $publicationId = trim($request->getUserVars()['publicationId']);
            if (isset($request->getUserVars()['citations']))
                $citations = json_decode(trim($request->getUserVars()['citations']), true);
        }

        if (empty($submissionId) || empty($publicationId) || empty($citations))
            return $response->withJson($this->responseBody, 200);

        $deposit = new DepositHandler();
        $deposit->execute((int)$submissionId, (int)$publicationId, $citations);

        $this->responseBody['message-type'] = 'deposit';
        $this->responseBody['message']['metadataJournal'] = $deposit->getMetadataJournal();
        $this->responseBody['message']['metadataPublication'] = $deposit->getMetadataPublication();
        $this->responseBody['message']['citations'] = $deposit->getCitations();
        $this->responseBody['message']['authors'] = $deposit->getAuthors();

        return $response->withJson($this->responseBody, 200);
    }
}
