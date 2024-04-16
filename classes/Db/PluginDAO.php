<?php
/**
 * @file classes/Db/PluginDAO.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PluginDAO
 * @brief DAO Schema
 */

namespace APP\plugins\generic\citationManager\classes\Db;

use APP\facades\Repo;
use APP\core\Services;
use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\DataModels\Citation\CitationModel;
use APP\plugins\generic\citationManager\classes\DataModels\Metadata\MetadataAuthor;
use APP\plugins\generic\citationManager\classes\DataModels\Metadata\MetadataJournal;
use APP\plugins\generic\citationManager\classes\DataModels\Metadata\MetadataPublication;
use APP\plugins\generic\citationManager\classes\Helpers\ClassHelper;
use Author;
use DAORegistry;
use Issue;
use Journal;
use JournalDAO;
use PKP\services\PKPSchemaService;
use Publication;
use Submission;

class PluginDAO
{
    /**
     * This method retrieves the structured citations for a publication.
     * After this, the method returns a normalized citations as an array of CitationModels.
     * If no citations are found, the method returns an empty array.
     *
     * @param int $publicationId
     * @return array
     */
    public function getCitations(int $publicationId): array
    {
        if (empty($publicationId)) return [];

        $publication = $this->getPublication($publicationId);

        $fromDb =
            json_decode(
                $publication->getData(CitationManagerPlugin::CITATIONS_STRUCTURED),
                true
            );

        if (empty($fromDb) || json_last_error() !== JSON_ERROR_NONE) return [];

        $result = [];

        foreach ($fromDb as $row) {
            if (!empty($row) && (is_object($row) || is_array($row))) {
                $result[] = ClassHelper::getClassAsArrayWithValuesAssigned(new CitationModel(), $row);
            }
        }

        return $result;
    }

    /**
     * This method saves the parsed citations for a publication.
     *
     * @param int $publicationId
     * @param array $citations
     * @return bool
     */
    public function saveCitations(int $publicationId, array $citations): bool
    {
        if (empty($publicationId)) return false;

        $publication = $this->getPublication($publicationId);

        $publication->setData(
            CitationManagerPlugin::CITATIONS_STRUCTURED,
            json_encode($citations)
        );

        $this->savePublication($publication);

        return true;
    }

    /**
     * This method retrieves metadata for a publication and returns normalized to MetadataPublication.
     * If nothing found, the method returns a new MetadataPublication.
     * If publication is provided, the metadata is retrieved from provided publication object.
     *
     * @param int $publicationId
     * @param Publication|null $publication
     * @return MetadataPublication
     */
    public function getMetadataPublication(int $publicationId, ?Publication $publication = null): MetadataPublication
    {
        if (empty($publicationId)) return new MetadataPublication();

        $key = CitationManagerPlugin::METADATA_PUBLICATION;

        if (empty($publication) || empty($publication->getData($key)))
            $publication = $this->getPublication($publicationId);

        $metadata = $publication->getData($key);

        if (is_string($metadata)) $metadata = json_decode($metadata, true);

        if (empty($metadata) || json_last_error() !== JSON_ERROR_NONE) return new MetadataPublication();

        return ClassHelper::getClassWithValuesAssigned(new MetadataPublication(), (array)$metadata);
    }

    /**
     * This method saves publicationWork for a publication.
     *
     * @param int $publicationId
     * @param MetadataPublication $metadataPublication
     * @return bool
     */
    public function saveMetadataPublication(int $publicationId, MetadataPublication $metadataPublication): bool
    {
        if (empty($publicationId)) return false;

        $publication = $this->getPublication($publicationId);

        $publication->setData(
            CitationManagerPlugin::METADATA_PUBLICATION,
            json_encode($metadataPublication)
        );

        $this->savePublication($publication);

        return true;
    }

    /**
     * This method retrieves author metadata for an author and returns normalized.
     * If nothing found, the method returns a new MetadataAuthor.
     * If author is provided, the metadata is retrieved from provided author object.
     *
     * @param int $authorId
     * @param Author|null $author
     * @return MetadataAuthor
     */
    public function getMetadataAuthor(int $authorId, ?Author $author = null): MetadataAuthor
    {
        if (empty($authorId)) return new MetadataAuthor();

        $key = CitationManagerPlugin::METADATA_AUTHOR;

        if (empty($author) || empty($author->getData($key)))
            $author = $this->getAuthor($authorId);

        $metadata = $author->getData($key);

        if (is_string($metadata)) $metadata = json_decode($metadata, true);

        if (empty($metadata) || json_last_error() !== JSON_ERROR_NONE) return new MetadataAuthor();

        return ClassHelper::getClassWithValuesAssigned(new MetadataAuthor(), (array)$metadata);
    }

    /**
     * This method saves publicationWork for a publication.
     *
     * @param int $authorId
     * @param MetadataAuthor $metadataAuthor
     * @return bool
     */
    public function saveMetadataAuthor(int $authorId, MetadataAuthor $metadataAuthor): bool
    {
        if (empty($authorId)) return false;

        $author = $this->getAuthor($authorId);

        $author->setData(
            CitationManagerPlugin::METADATA_AUTHOR,
            json_encode($metadataAuthor)
        );

        $this->saveAuthor($author);

        return true;
    }

    /**
     * This method retrieves metadata for a journal and returns normalized to MetadataJournal.
     * If nothing found, the method returns a new MetadataJournal.
     * If journal is provided, the metadata is retrieved from provided journal object.
     *
     * @param int $journalId
     * @param Journal|null $journal
     * @return MetadataJournal
     */
    public function getMetadataJournal(int $journalId, ?Journal $journal = null): MetadataJournal
    {
        if (empty($journalId)) return new MetadataJournal();

        $key = CitationManagerPlugin::METADATA_JOURNAL;

        // Reload the context schema
        Services::get('schema')->get(PKPSchemaService::SCHEMA_CONTEXT, true);

        if (empty($journal) || empty($journal->getData($key)))
            $journal = $this->getJournal($journalId);

        $metadata = $journal->getData($key);

        if (is_string($metadata)) $metadata = json_decode($metadata, true);

        if (empty($metadata) || json_last_error() !== JSON_ERROR_NONE)
            return new MetadataJournal();

        return ClassHelper::getClassWithValuesAssigned(new MetadataJournal(), (array)$metadata);
    }

    /**
     * This method saves JournalModel to journal_settings.
     *
     * @param int $contextId
     * @param MetadataJournal $metadataJournal
     * @return bool
     */
    public function saveMetadataJournal(int $contextId, MetadataJournal $metadataJournal): bool
    {
        if (empty($contextId)) return false;

        // Reload the context schema
        Services::get('schema')->get(PKPSchemaService::SCHEMA_CONTEXT, true);

        $journal = $this->getJournal($contextId);

        $journal->setData(
            CitationManagerPlugin::METADATA_JOURNAL,
            json_encode($metadataJournal)
        );

        $this->saveJournal($journal);

        return true;
    }

    /* OJS getters */
    public function getJournal(int $journalId): ?Journal
    {
        /* @var JournalDAO $dao */
        $dao = DAORegistry::getDAO('JournalDAO');
        /* @var Journal */
        return $dao->getById($journalId);
    }
    public function getIssue(int $issueId): ?Issue
    {
        return Repo::issue()->get($issueId);
    }
    public function getSubmission(int $submissionId): ?Submission
    {
        return Repo::submission()->get($submissionId);
    }
    public function getPublication(int $publicationId): ?Publication
    {
        return Repo::publication()->get($publicationId);
    }
    public function getAuthor(int $authorId): ?Author
    {
        return Repo::author()->get($authorId);
    }

    /* OJS setters */
    public function saveJournal(Journal $journal): void
    {
        /* @var JournalDAO $dao */
        $dao = DAORegistry::getDAO('JournalDAO');
        $dao->updateObject($journal);
    }
    public function saveIssue(Issue $issue): void
    {
        Repo::issue()->dao->update($issue);
    }
    public function saveSubmission(Submission $submission): void
    {
        Repo::submission()->dao->update($submission);
    }
    public function savePublication(Publication $publication): void
    {
        Repo::publication()->dao->update($publication);
    }
    public function saveAuthor(Author $author): void
    {
        Repo::author()->dao->update($author);
    }
}
