<?php
/**
 * @file classes/Workflow/WorkflowTab.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WorkflowTab
 * @brief Workflow Publication Tab
 */

namespace APP\plugins\generic\citationManager\classes\Workflow;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\DataModels\Citation\AuthorModel;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataJournal;
use APP\plugins\generic\citationManager\classes\Db\PluginDAO;
use APP\plugins\generic\citationManager\classes\Helpers\ClassHelper;
use APP\plugins\generic\citationManager\classes\PID\Doi;
use APP\plugins\generic\citationManager\classes\PID\GitHubIssue;
use APP\plugins\generic\citationManager\classes\PID\OpenAlex;
use APP\plugins\generic\citationManager\classes\PID\Orcid;
use APP\plugins\generic\citationManager\classes\PID\Wikidata;
use Application;
use Author;
use Exception;
use Publication;

class WorkflowTab
{
    /** @var CitationManagerPlugin */
    public CitationManagerPlugin $plugin;

    /** @param CitationManagerPlugin $plugin */
    public function __construct(CitationManagerPlugin &$plugin)
    {
        $this->plugin = &$plugin;
    }

    /**
     * Show tab under Publications
     *
     * @param string $hookName
     * @param array $args [string, TemplateManager]
     * @return void
     * @throws Exception
     */
    public function execute(string $hookName, array $args): void
    {
        /* @var Publication $publication */
        $templateMgr = &$args[1];

        $pluginDao = new PluginDAO();
        $request = $this->plugin->getRequest();
        $context = $request->getContext();
        $submission = $templateMgr->getTemplateVars('submission');
        $submissionId = $submission->getId();
        $publication = $submission->getLatestPublication();
        $publicationId = $publication->getId();
        $locale = $publication->getData('locale');

        $apiBaseUrl = $request->getDispatcher()->url(
            $request,
            ROUTE_API,
            $context->getData('urlPath'),
            '');

        $locales = $context->getSupportedLocaleNames();
        $locales = array_map(
            fn(string $locale, string $name) => ['key' => $locale, 'label' => $name],
            array_keys($locales), $locales);

        $form = new WorkflowForm(
            CitationManagerPlugin::CITATIONS_STRUCTURED_FORM,
            'PUT',
            $apiBaseUrl . 'submissions/' . $submissionId . '/publications/' . $publicationId,
            $locales);

        $state = $templateMgr->getTemplateVars('state');
        $state['components'][CitationManagerPlugin::CITATIONS_STRUCTURED_FORM] = $form->getConfig();
        $templateMgr->assign('state', $state);

        $templateParameters = [
            'assetsUrl' => $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/assets',
            'apiBaseUrl' => $apiBaseUrl,
            'url' => [
                'doi' => Doi::prefix,
                'openAlex' => OpenAlex::prefix,
                'openCitations' => GitHubIssue::prefix,
                'orcid' => Orcid::prefix,
                'wikidata' => Wikidata::prefix
            ],
            'authorModel' => json_encode(ClassHelper::getClassAsArrayNullAssigned(new AuthorModel())),
            'publication' => json_encode($publication->_data),
            'publicationId' => $publicationId,
            'citationStructured' => json_encode($pluginDao->getCitations($publication)),
            'metadataJournal' => json_encode([
                MetadataJournal::openAlexId => $context->getData(MetadataJournal::openAlexId),
                MetadataJournal::wikidataId => $context->getData(MetadataJournal::wikidataId)
            ])
        ];
        $templateMgr->assign($templateParameters);

        $templateMgr->display($this->plugin->getTemplateResource("workflowTab.tpl"));
    }
}
