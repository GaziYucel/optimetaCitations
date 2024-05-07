<?php
/**
 * @file classes/Handlers/DepositHandler.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DepositHandler
 * @brief Executes the deposit of publications and citations to external services.
 */

namespace APP\plugins\generic\citationManager\classes\Handlers;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataAuthor;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataJournal;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataPublication;
use APP\plugins\generic\citationManager\classes\Db\PluginDAO;
use APP\plugins\generic\citationManager\classes\Db\PluginSchema;
use APP\plugins\generic\citationManager\classes\External\ExecuteAbstract;
use APP\plugins\generic\citationManager\classes\Helpers\ClassHelper;
use APP\plugins\generic\citationManager\classes\Helpers\LogHelper;
use Author;
use Application;
use Publication;
use Submission;
use PluginRegistry;
use Exception;
use APP\facades\Repo;

class DepositHandler
{
    /** @var CitationManagerPlugin */
    public CitationManagerPlugin $plugin;

    /** @var array|string[] A list of external services processed */
    private array $services = [
        '\APP\plugins\generic\citationManager\classes\External\OpenCitations\Outbound',
        '\APP\plugins\generic\citationManager\classes\External\Wikidata\Outbound'
    ];

    public function __construct()
    {
        /** @var CitationManagerPlugin $plugin */
        $plugin = PluginRegistry::getPlugin('generic', strtolower(CITATION_MANAGER_PLUGIN_NAME));
        $this->plugin = $plugin;

        PluginSchema::reloadJournalSchema();
    }

    /**
     * Deposit publication and citations to external services.
     *
     * @param int $contextId
     * @param int $submissionId The ID of the submission.
     * @param int $publicationId The ID of the publication.
     * @param array $citations Array of citations to be deposited.
     * @return bool
     */
    public function execute(int $contextId, int $submissionId, int $publicationId, array $citations): bool
    {
        if (empty($submissionId) || empty($publicationId) || empty($citations)) return false;

        $pluginDao = new PluginDAO();
        $publication = $pluginDao->getPublication($publicationId);
        $context = $pluginDao->getContext($contextId);

        // return false if no doi found
        if (empty($publication->getStoredPubId('doi')) || empty($issue)) return false;

        // journal
        $contextChanged = false;
        foreach (ClassHelper::getClassConstantsAndValuesAsArray(new MetadataJournal()) as $name => $key) {
            if (empty($context->getData($key))) {
                $context->setData($key, '');
                $contextChanged = true;
            }
        }
        if ($contextChanged) $pluginDao->saveContext($context);

        // publication metadata
        $publicationChanged = false;
        foreach (ClassHelper::getClassConstantsAndValuesAsArray(new MetadataPublication()) as $name => $key) {
            if (empty($publication->getData($key))) {
                $publication->setData($key, '');
                $publicationChanged = true;
            }
        }
        if ($publicationChanged) $pluginDao->savePublication($publication);

        // authors of publication
        /* @var Author $author */
        $authorsChanged = false;
        foreach ($publication->getData('authors') as $index => $author) {
            $authorChanged = false;
            foreach (ClassHelper::getClassConstantsAndValuesAsArray(new MetadataAuthor()) as $name => $key) {
                if (empty($author->getData($key))) {
                    $author->setData($key, '');
                    $authorChanged = true;
                    $authorsChanged = true;
                }
            }
            if ($authorChanged) $pluginDao->saveAuthor($author);
        }
        if ($authorsChanged) $publication = $pluginDao->getPublication($publicationId);

        // iterate services
        $services = '';
        /* @var ExecuteAbstract $service */
        foreach ($this->services as $serviceClass) {
            $service = new $serviceClass ($this->plugin, $contextId, $submissionId, $publicationId);
            $service->execute();
            $services .= explode('\\', $serviceClass)[7] . ',';
        }

        LogHelper::logInfo([
            'title' => $publication->getData('title')[$publication->getData('locale')],
            'submission' => $submissionId,
            'publication' => $publicationId,
            'services' => trim($services, ','),
            'status' => 'processed'
        ]);

        return true;
    }

    /**
     * Perform batch deposit for all contexts and submissions.
     *
     * @return bool True if the batch deposit is successful, false otherwise.
     */
    public function batchExecute(): bool
    {
        $batchFilter = [
            // 1, // Submission::STATUS_QUEUED,
            3, // Submission::STATUS_PUBLISHED,
            // 4, // Submission::STATUS_DECLINED,
            // 5  // Submission::STATUS_SCHEDULED
        ];

        $contextIds = [];

        $pluginDao = new PluginDAO();

        $contextDao = Application::getContextDAO();
        $contextFactory = $contextDao->getAll();

        try {
            while ($context = $contextFactory->next()) {
                $contextIds[] = $context->getId();
            }
        } catch (Exception $e) {
            error_log(__METHOD__ . ' ' . $e->getMessage());
        }

        foreach ($contextIds as $contextId) {

            $submissions = Repo::submission()->getCollector()
                ->filterByContextIds([$contextId])
                ->filterByStatus($batchFilter)
                ->getMany();

            /* @var Submission $submission */
            foreach ($submissions as $submission) {

                $publications = $submission->getData('publications');

                /* @var Publication $publication */
                foreach ($publications as $publication) {

                    $citations = $pluginDao->getCitations($publication);

                    if (!in_array($publication->getData('status'), $batchFilter)
                        || empty($citations)) continue;

                    $this->execute(
                        $contextId,
                        $submission->getId(),
                        $publication->getId(),
                        $citations);
                }
            }
        }

        return true;
    }
}
