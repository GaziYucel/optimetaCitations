<?php
/**
 * @file classes/Models/ProcessHandler.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ProcessHandler
 * @brief Executes cleaning, splitting, extracting PID's, structuring and enriching citations
 */

namespace APP\plugins\generic\citationManager\classes\Handlers;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\DataModels\Citation\CitationModel;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataAuthor;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataJournal;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataPublication;
use APP\plugins\generic\citationManager\classes\Db\PluginDAO;
use APP\plugins\generic\citationManager\classes\External\ExecuteAbstract;
use APP\plugins\generic\citationManager\classes\Helpers\ClassHelper;
use APP\plugins\generic\citationManager\classes\Helpers\StringHelper;
use APP\plugins\generic\citationManager\classes\PID\Arxiv;
use APP\plugins\generic\citationManager\classes\PID\Doi;
use APP\plugins\generic\citationManager\classes\PID\Handle;
use APP\plugins\generic\citationManager\classes\PID\Url;
use APP\plugins\generic\citationManager\classes\PID\Urn;
use Author;
use Application;
use Publication;
use Submission;
use PluginRegistry;
use Exception;
use APP\facades\Repo;

class ProcessHandler
{
    /** @var CitationManagerPlugin */
    public CitationManagerPlugin $plugin;

    /** @var array|string[] */
    private array $services = [
        '\APP\plugins\generic\citationManager\classes\External\OpenAlex\Inbound',
        '\APP\plugins\generic\citationManager\classes\External\Orcid\Inbound',
        '\APP\plugins\generic\citationManager\classes\External\Wikidata\Inbound'
    ];

    public function __construct()
    {
        /** @var CitationManagerPlugin $plugin */
        $plugin = PluginRegistry::getPlugin('generic', strtolower(CITATION_MANAGER_PLUGIN_NAME));
        $this->plugin = $plugin;
    }

    /**
     * Extract pids, structure and enrich
     *
     * @param int $submissionId The ID of the submission.
     * @param int $publicationId The ID of the publication.
     * @param string $citationsRaw Raw citations to be processed
     * @return bool
     */
    public function execute(int $submissionId, int $publicationId, string $citationsRaw): bool
    {
        if (empty($submissionId) || empty($publicationId) || empty($citationsRaw)) return false;

        $pluginDao = new PluginDAO();
        $publication = $pluginDao->getPublication($publicationId);
        $context = $this->plugin->getRequest()->getContext();

        // journal
        $contextChanged = false;
        foreach (ClassHelper::getClassConstantsAndValuesAsArray(new MetadataJournal()) as $name => $key) {
            if (empty($context->getData($key))) {
                $context->setData($key, '');
                $contextChanged = true;
            }
        }
        if ($contextChanged) $pluginDao->saveContext($context);

        // citations clean, structure and save
        $citations = $this->cleanupAndSplit($citationsRaw);
        if (empty($citations)) return false;
        $citations = $this->extractPids($citations);
        $publication->setData(CitationManagerPlugin::CITATIONS_STRUCTURED, json_encode($citations));

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
        /* @var ExecuteAbstract $service */
        foreach ($this->services as $serviceClass) {
            $service = new $serviceClass ($this->plugin, $submissionId, $publicationId);
            $service->execute();
        }

        return true;
    }

    /**
     * Perform batch deposit for all contexts and submissions.
     *
     * @return bool True if the batch deposit is successful, false otherwise.
     */
    public function batchExecute(): bool
    {
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
                ->filterByStatus([
                    Submission::STATUS_QUEUED,
                    Submission::STATUS_SCHEDULED,
                    Submission::STATUS_SCHEDULED]);

            /* @var Submission $submission */
            foreach ($submissions as $submission) {

                $publications = $submission->getData('publications');

                /* @var Publication $publication */
                foreach ($publications as $publication) {

                    $citations = $pluginDao->getCitations($publication);
                    $citationsRaw = $publication->getData('citationRaw');

                    if (!empty($citations) || empty($citationsRaw)) continue;

                    $this->execute(
                        $submission->getId(),
                        $publication->getId(),
                        $citationsRaw);
                }
            }
        }

        return true;
    }

    /**
     * Cleans and splits citations raw
     *
     * @param string $citationsRaw
     * @return array
     */
    private function cleanupAndSplit(string $citationsRaw): array
    {
        $citationsRaw = StringHelper::trim($citationsRaw);
        $citationsRaw = StringHelper::stripSlashes($citationsRaw);
        $citationsRaw = StringHelper::normalizeLineEndings($citationsRaw);
        $citationsRaw = StringHelper::trim($citationsRaw, "\n");

        if (empty($citationsRaw)) return [];

        $citations = explode("\n", $citationsRaw);

        $local = [];
        foreach ($citations as $citationRaw) {
            if (!empty($citationRaw)) {
                $citation = new CitationModel();
                $citation->raw = $citationRaw;
                $local[] = $citation;
            }
        }

        return $local;
    }

    /**
     * Extract PIDs
     *
     * @param array $citations
     * @return array
     */
    private function extractPids(array $citations): array
    {
        $local = [];

        foreach ($citations as $index => $citation) {
            $rowRaw = $citation->raw;
            $rowRaw = StringHelper::trim($rowRaw, ' .,');
            $rowRaw = StringHelper::stripSlashes($rowRaw);
            $rowRaw = StringHelper::normalizeWhiteSpace($rowRaw);
            $rowRaw = StringHelper::removeNumberPrefixFromString($rowRaw);

            // extract doi
            $citation->doi = Doi::extractFromString($rowRaw);

            // remove doi from raw
            $rowRaw = str_replace(
                Doi::addPrefix($citation->doi),
                '',
                Doi::normalize($rowRaw));

            // parse url (after parsing doi)
            $citation->url = Url::extractFromString($rowRaw);

            // handle
            $citation->url = Handle::normalize($citation->url);

            // arxiv
            $citation->url = Arxiv::normalize($citation->url);

            // urn
            $citation->urn = Urn::extractFromString($rowRaw);

            $local[] = $citation;
        }

        return $local;
    }
}
