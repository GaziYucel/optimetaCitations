<?php
/**
 * @file classes/Settings/ConfigurationForm.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ConfigurationForm
 * @brief Form for journal managers to set up the plugin
 */

namespace APP\plugins\generic\citationManager\classes\Settings;

import('lib.pkp.classes.form.Form');

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use Application;
use Form;
use FormValidatorCSRF;
use FormValidatorPost;
use Notification;
use NotificationManager;
use TemplateManager;

class ConfigurationForm extends Form
{
    /** @var CitationManagerPlugin */
    public CitationManagerPlugin $plugin;

    /** @var string[] Array of variables saved in the database. */
    private array $settings = [
        CitationManagerPlugin::WIKIDATA_USERNAME,
        CitationManagerPlugin::WIKIDATA_PASSWORD,
        CitationManagerPlugin::OPEN_CITATIONS_OWNER,
        CitationManagerPlugin::OPEN_CITATIONS_REPOSITORY,
        CitationManagerPlugin::OPEN_CITATIONS_TOKEN,
        CitationManagerPlugin::FRONTEND_SHOW_STRUCTURED
    ];

    /** @copydoc Form::__construct() */
    public function __construct(CitationManagerPlugin &$plugin)
    {
        $this->plugin = &$plugin;

        // Always add POST and CSRF validation to secure your form.
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));

        parent::__construct($plugin->getTemplateResource('settingsConfigForm.tpl'));
    }

    /** @copydoc Form::initData() */
    public function initData(): void
    {
        $context = Application::get()
            ->getRequest()
            ->getContext();

        $contextId = $context
            ? $context->getId()
            : CONTEXT_SITE;

        foreach ($this->settings as $key) {
            $this->setData(
                $key,
                $this->plugin->getSetting($contextId, $key));
        }

        parent::initData();
    }

    /** @copydoc Form::readInputData() */
    public function readInputData(): void
    {
        foreach ($this->settings as $key) {
            $this->readUserVars([$key]);
        }
        parent::readInputData();
    }

    /** @copydoc Form::fetch() */
    public function fetch($request, $template = null, $display = false): ?string
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());

        return parent::fetch($request, $template, $display);
    }

    /** @copydoc Form::execute() */
    public function execute(...$functionArgs)
    {
        $context = Application::get()
            ->getRequest()
            ->getContext();

        $contextId = $context
            ? $context->getId()
            : CONTEXT_SITE;

        foreach ($this->settings as $key) {
            $value = $this->getData($key);

            if ($key === CitationManagerPlugin::FRONTEND_SHOW_STRUCTURED && !empty($value)) {
                $value = "true";
            }

            $this->plugin->updateSetting(
                $contextId,
                $key,
                $value);
        }

        $notificationMgr = new NotificationManager();
        $notificationMgr->createTrivialNotification(
            Application::get()->getRequest()->getUser()->getId(),
            NOTIFICATION_TYPE_SUCCESS,
            ['contents' => __('common.changesSaved')]
        );

        return parent::execute();
    }
}
