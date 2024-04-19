<?php
/**
 * @file CitationManagerPlugin.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CitationManagerPlugin
 * @brief Plugin for structuring, enriching and depositing Citations from and to external services.
 */

define('CITATION_MANAGER_PLUGIN_NAME', basename(__FILE__, '.php'));

require_once(__DIR__ . '/vendor/autoload.php');

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.site.VersionCheck');
import('lib.pkp.classes.handler.APIHandler');
import('lib.pkp.classes.linkAction.request.AjaxAction');

use APP\plugins\generic\citationManager\classes\Db\PluginSchema;
use APP\plugins\generic\citationManager\classes\FrontEnd\ArticleView;
use APP\plugins\generic\citationManager\classes\Handlers\PluginAPIHandler;
use APP\plugins\generic\citationManager\classes\Settings\Actions;
use APP\plugins\generic\citationManager\classes\Settings\Manage;
use APP\plugins\generic\citationManager\classes\Workflow\SubmissionWizard;
use APP\plugins\generic\citationManager\classes\Workflow\WorkflowSave;
use APP\plugins\generic\citationManager\classes\Workflow\WorkflowTab;

class CitationManagerPlugin extends GenericPlugin
{
    /** @var string Whether show the structured or the raw citations */
    public const FRONTEND_SHOW_STRUCTURED = CITATION_MANAGER_PLUGIN_NAME . '_FrontEndShowStructured';
    /** @var string Key for structured citations saved in publications */
    public const CITATIONS_STRUCTURED = 'citationsStructured';
    /** @var string Key used for the form used in workflow and submission wizard */
    public const CITATIONS_STRUCTURED_FORM = 'citationsStructuredForm';
    /** @var string Wikidata username */
    public const WIKIDATA_USERNAME = CITATION_MANAGER_PLUGIN_NAME . '_Wikidata_Username';
    /** @var string Wikidata password */
    public const WIKIDATA_PASSWORD = CITATION_MANAGER_PLUGIN_NAME . '_Wikidata_Password';
    /** @var string GitHub handle / account used for Open Citations */
    public const OPEN_CITATIONS_OWNER = CITATION_MANAGER_PLUGIN_NAME . '_OpenCitations_Owner';
    /** @var string GitHub repository used for Open Citations */
    public const OPEN_CITATIONS_REPOSITORY = CITATION_MANAGER_PLUGIN_NAME . '_OpenCitations_Repository';
    /** @var string GitHub APi token used for Open Citations */
    public const OPEN_CITATIONS_TOKEN = CITATION_MANAGER_PLUGIN_NAME . '_OpenCitations_Token';
    /** @var array Roles which can access PluginApiHandler */
    public const apiRoles = [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR];

    /** @copydoc Plugin::register */
    public function register($category, $path, $mainContextId = null): bool
    {
        if (parent::register($category, $path, $mainContextId)) {

            if ($this->getEnabled()) {

//                HookRegistry::register('AcronPlugin::parseCronTab', function ($hookName, $args) {
//                    $taskFilesPath =& $args[0];
//                    $taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';
//                    return false;
//                });

                $pluginSchema = new PluginSchema();
                HookRegistry::register('Schema::get::publication', [$pluginSchema, 'addToSchemaPublication']);
                HookRegistry::register('Schema::get::author', [$pluginSchema, 'addToSchemaAuthor']);
                HookRegistry::register('Schema::get::context', [$pluginSchema, 'addToSchemaJournal']);

                $submissionWizard = new SubmissionWizard($this);
                HookRegistry::register('Templates::Submission::SubmissionMetadataForm::AdditionalMetadata', [$submissionWizard, 'execute']);

                $workflowTab = new WorkflowTab($this);
                HookRegistry::register('Template::Workflow', [$workflowTab, 'execute']);

                $workflowSave = new WorkflowSave($this);
                HookRegistry::register('Publication::edit', [$workflowSave, 'execute']);

                $articlePage = new ArticleView($this);
                HookRegistry::register('TemplateManager::display', [$articlePage, 'execute']);

                $pluginApiHandler = new PluginAPIHandler();
                HookRegistry::register('Dispatcher::dispatch', [$pluginApiHandler, 'register']);
		
		/*
		function ($hookName, $args) {
                    try {
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
                    } catch (Throwable $ex) {
                        error_log(__METHOD__ . ' ' . $ex->getMessage());
                    }

                    return false;
                }
		*/
            }

            return true;
        }

        return false;
    }

    /** @copydoc Plugin::getActions() */
    public function getActions($request, $actionArgs): array
    {
        if (!$this->getEnabled()) return parent::getActions($request, $actionArgs);

        $actions = new Actions($this);
        return $actions->execute($request, $actionArgs, parent::getActions($request, $actionArgs));
    }

    /** @copydoc Plugin::manage() */
    public function manage($args, $request): JSONMessage
    {
        $manage = new Manage($this);
        return $manage->execute($args, $request);
    }

    /** @copydoc PKPPlugin::getDescription */
    public function getDescription(): string
    {
        return __('plugins.generic.citationManager.description');
    }

    /** @copydoc PKPPlugin::getDisplayName */
    public function getDisplayName(): string
    {
        return __('plugins.generic.citationManager.displayName');
    }

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

class_alias('\CitationManagerPlugin', '\APP\plugins\generic\citationManager\CitationManagerPlugin');
