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

require_once(CitationManagerPlugin::autoloadFile());

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.site.VersionCheck');
import('lib.pkp.classes.handler.APIHandler');
import('lib.pkp.classes.linkAction.request.AjaxAction');

use APP\plugins\generic\citationManager\classes\Db\PluginSchema;
use APP\plugins\generic\citationManager\classes\FrontEnd\ArticleView;
use APP\plugins\generic\citationManager\classes\Handlers\PluginAPIHandler;
use APP\plugins\generic\citationManager\classes\Settings\Actions;
use APP\plugins\generic\citationManager\classes\Settings\Manage;
use APP\plugins\generic\citationManager\classes\Settings\PluginConfig;
use APP\plugins\generic\citationManager\classes\Workflow\SubmissionWizard;
use APP\plugins\generic\citationManager\classes\Workflow\WorkflowSave;
use APP\plugins\generic\citationManager\classes\Workflow\WorkflowTab;

class CitationManagerPlugin extends GenericPlugin
{
    /** @var string Whether show the structured or the raw citations */
    public const FRONTEND_SHOW_STRUCTURED = CITATION_MANAGER_PLUGIN_NAME . '_FrontEndShowStructured';
    /** @var string Key for structured citations saved in publications */
    public const CITATIONS_STRUCTURED = 'citationsStructured';
    /** @var array Roles which can access PluginApiHandler */
    public const apiRoles = [ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR];

    /** @copydoc Plugin::register */
    public function register($category, $path, $mainContextId = null): bool
    {
        if (parent::register($category, $path, $mainContextId)) {

            if ($this->getEnabled()) {
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

//                // task scheduler; not working as expected
//                Hook::add('AcronPlugin::parseCronTab', function ($hookName, $args) {
//                    $taskFilesPath =& $args[0];
//                    $taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';
//                    return false;
//                });
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
     * Return composer autoload file path
     *
     * @return string
     */
    public static function autoloadFile(): string
    {
        if (PluginConfig::isTestMode()) return __DIR__ . '/tests/vendor/autoload.php';
        return __DIR__ . '/vendor/autoload.php';
    }
}

class_alias('\CitationManagerPlugin', '\APP\plugins\generic\citationManager\CitationManagerPlugin');
