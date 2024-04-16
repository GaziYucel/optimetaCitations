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
use APP\plugins\generic\citationManager\classes\DataModels\Metadata\MetadataAuthor;
use APP\plugins\generic\citationManager\classes\DataModels\Metadata\MetadataJournal;
use APP\plugins\generic\citationManager\classes\DataModels\Metadata\MetadataPublication;
use APP\plugins\generic\citationManager\classes\Db\PluginDAO;
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

    /** @var MetadataJournal|null */
    private ?MetadataJournal $metadataJournal = null;

    /** @var MetadataPublication|null */
    private ?MetadataPublication $metadataPublication = null;

    /** @var array|null [ { CitationModel }, ... ] */
    private ?array $citations = [];

    /** @var array|null */
    private ?array $authors = null;

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
     * Execute
     *
     * @param int $submissionId The ID of the submission.
     * * @param int $publicationId The ID of the publication.
     * @param string $citationsRaw
     * @return bool
     */
    public function execute(int $submissionId,
                            int $publicationId,
                            string $citationsRaw): bool
    {
        if (empty($submissionId) || empty($publicationId) || empty($citationsRaw)) return false;

        $pluginDao = new PluginDAO();
        $context = $this->plugin->getRequest()->getContext();
        $submission = $pluginDao->getSubmission($submissionId);
        $publication = $pluginDao->getPublication($publicationId);
        $issue = null;
        if (!empty($publication->getData('issueId')))
            $issue = $pluginDao->getIssue($publication->getData('issueId'));
        $this->metadataJournal = $pluginDao->getMetadataJournal($context->getId(), $context);
        $this->metadataPublication = $pluginDao->getMetadataPublication($publicationId, $publication);
        $this->citations = [];

        // author(s)
        /* @var Author $author */
        foreach ($publication->getData('authors') as $index => $author) {
            $author->setData(CitationManagerPlugin::METADATA_AUTHOR,
                $pluginDao->getMetadataAuthor($author->getId(), $author));
            $this->authors[] = $author;
        }

        // cleanup and split
        $this->citations = $this->cleanupAndSplit($citationsRaw);

        if (empty($this->citations)) return false;

        // extract pid's
        $this->citations = $this->extractPIDs($this->citations);

        // iterate services
        foreach ($this->services as $serviceClass) {
            $service = new $serviceClass ($this->plugin, $context, $issue, $submission, $publication,
                $this->metadataJournal, $this->metadataPublication, $this->authors, $this->citations);

            $service->execute();

            $this->metadataJournal = $service->getMetadataJournal();
            $this->metadataPublication = $service->getMetadataPublication();
            $this->authors = $service->getAuthors();
            $this->citations = $service->getCitations();
        }

        // save to database
        $pluginDao->saveMetadataJournal($context->getId(), $this->metadataJournal);
        $pluginDao->saveMetadataPublication($publicationId, $this->metadataPublication);
        $pluginDao->saveCitations($publicationId, $this->citations);
        /* @var Author $author */
        foreach ($this->authors as $id => $author) {
            $pluginDao->saveMetadataAuthor(
                $author->getId(),
                $author->getData(CitationManagerPlugin::METADATA_AUTHOR));
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

        $contextDao = Application::getContextDAO();

        $contextFactory = $contextDao->getAll();

        try {
            while ($context = $contextFactory->next()) {
                $contextIds[] = $context->getId();
            }
        } catch (Exception $e) {
            error_log(__METHOD__ . ' ' . $e->getMessage());
        }

        $pluginDao = new PluginDAO();

        foreach ($contextIds as $contextId) {

            $submissions = Repo::submission()->getCollector()
                ->filterByContextIds([$contextId]);

            /* @var Submission $submission */
            foreach ($submissions as $submission) {

                // skip if declined
                if($submission->getData('status') === Submission::STATUS_DECLINED)
                    continue;

                $publications = $submission->getData('publications');

                /* @var Publication $publication */
                foreach ($publications as $publication) {

                    // skip if declined or citations found
                    if (!empty($pluginDao->getCitations($publication->getId()))
                        || $publication->getData('status') === Submission::STATUS_DECLINED) {
                        continue;
                    }

                    // not processed yet, proceed
                    $citationsRaw = $publication->getData('citationRaw');
                    $this->execute($submission->getId(), $publication->getId(), $citationsRaw);
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
     * Extract PID's
     *
     * @param array $citations
     * @return array
     */
    private function extractPIDs(array $citations): array
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

    // region getters
    public function getMetadataJournal(): MetadataJournal
    {
        return $this->metadataJournal;
    }
    public function getMetadataPublication(): MetadataPublication
    {
        return $this->metadataPublication;
    }
    public function getCitations(): array
    {
        return $this->citations;
    }
    public function getAuthors(): array
    {
        return $this->authors;
    }
    // endregion
}
