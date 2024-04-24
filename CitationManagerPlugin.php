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

namespace APP\plugins\generic\citationManager;

define('CITATION_MANAGER_PLUGIN_NAME', basename(__FILE__, '.php'));

require_once(__DIR__ . '/vendor/autoload.php');

use APP\plugins\generic\citationManager\classes\Db\PluginSchema;
use APP\plugins\generic\citationManager\classes\FrontEnd\ArticleView;
use APP\plugins\generic\citationManager\classes\Handlers\PluginAPIHandler;
use APP\plugins\generic\citationManager\classes\Settings\Actions;
use APP\plugins\generic\citationManager\classes\Settings\Manage;
use APP\plugins\generic\citationManager\classes\Workflow\SubmissionWizard;
use APP\plugins\generic\citationManager\classes\Workflow\WorkflowSave;
use APP\plugins\generic\citationManager\classes\Workflow\WorkflowTab;
use Config;
use PKP\core\JSONMessage;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\security\Role;

class CitationManagerPlugin extends GenericPlugin
{
    /** @var string Whether show the structured or the raw citations */
    public const FRONTEND_SHOW_STRUCTURED = CITATION_MANAGER_PLUGIN_NAME . '_FrontEndShowStructured';
    /** @var string Key for structured citations saved in publications */
    public const CITATIONS_STRUCTURED = 'citationsStructured';
    /** @var string Key used for the form used in workflow and submission wizard */
    public const CITATIONS_STRUCTURED_FORM = 'citationsStructuredForm';
    /** @var array Roles which can access PluginApiHandler */
    public const apiRoles = [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT, Role::ROLE_ID_REVIEWER, Role::ROLE_ID_AUTHOR];

    /** @copydoc Plugin::register */
    public function register($category, $path, $mainContextId = null): bool
    {
        if (parent::register($category, $path, $mainContextId)) {

            if ($this->getEnabled()) {

//                // task scheduler; not working as expected
//                Hook::add('AcronPlugin::parseCronTab', function ($hookName, $args) {
//                    $taskFilesPath =& $args[0];
//                    $taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';
//                    return false;
//                });

                $pluginSchema = new PluginSchema();
                Hook::add('Schema::get::publication', [$pluginSchema, 'addToSchemaPublication']);
                Hook::add('Schema::get::author', [$pluginSchema, 'addToSchemaAuthor']);
                Hook::add('Schema::get::context', [$pluginSchema, 'addToSchemaJournal']);

                $submissionWizard = new SubmissionWizard($this);
                Hook::add('Template::SubmissionWizard::Section', [$submissionWizard, 'execute']);

                $workflowTab = new WorkflowTab($this);
                Hook::add('Template::Workflow', [$workflowTab, 'execute']);

                $workflowSave = new WorkflowSave($this);
                Hook::add('Publication::edit', [$workflowSave, 'execute']);

                $articlePage = new ArticleView($this);
                Hook::add('TemplateManager::display', [$articlePage, 'execute']);

                $pluginApiHandler = new PluginAPIHandler();
                Hook::add('Dispatcher::dispatch', [$pluginApiHandler, 'register']);
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

// For backwards compatibility -- expect this to be removed approx. OJS/OMP/OPS 3.6
if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\citationManager\CitationManagerPlugin', '\CitationManagerPlugin');
}
