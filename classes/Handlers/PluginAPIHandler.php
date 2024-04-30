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
use APP\plugins\generic\citationManager\classes\Db\PluginDAO;
use APIRouter;
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
        'action' => 'none',
        'version' => '1',
        'publication' => []
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
        $request = $args;
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

        $body = json_decode(file_get_contents('php://input'), true);

        if (empty($body) || json_last_error() !== JSON_ERROR_NONE)
            return $response->withJson($this->responseBody, 200);

        if (isset($body['submissionId'])) $submissionId = trim($body['submissionId']);
        if (isset($body['publicationId'])) $publicationId = trim($body['publicationId']);
        if (isset($body['citationsRaw'])) $citationsRaw = trim($body['citationsRaw']);

        if (empty($submissionId) || empty($publicationId) || empty($citationsRaw))
            return $response->withJson($this->responseBody, 200);

        $process = new ProcessHandler();
        $process->execute(
            $request->getContext()->getId(),
            (int)$submissionId,
            (int)$publicationId,
            $citationsRaw);

        return $this->response('process', $publicationId, $response);
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

        $body = json_decode(file_get_contents('php://input'), true);

        if (empty($body) || json_last_error() !== JSON_ERROR_NONE)
            return $response->withJson($this->responseBody, 200);

        if (isset($body['submissionId'])) $submissionId = trim($body['submissionId']);
        if (isset($body['publicationId'])) $publicationId = trim($body['publicationId']);
        if (isset($body['citations'])) $citations = $body['citations'];

        if (empty($submissionId) || empty($publicationId) || empty($citations))
            return $response->withJson($this->responseBody, 200);

        $deposit = new DepositHandler();
        $deposit->execute(
            $request->getContext()->getId(),
            (int)$submissionId,
            (int)$publicationId,
            (array)$citations);

        return $this->response('deposit', $publicationId, $response);
    }

    /**
     * Prepare and return response.
     *
     * @param string $action
     * @param string $publicationId
     * @param APIResponse $response
     * @return APIResponse
     */
    private function response(string $action, string $publicationId, APIResponse $response): APIResponse
    {
        $pluginDao = new PluginDao();

        $this->responseBody['action'] = $action;

        $publication = $pluginDao->getPublication((int)$publicationId);
        $this->responseBody['publication'] = $publication->_data;

        return $response->withJson($this->responseBody, 200);
    }
}
