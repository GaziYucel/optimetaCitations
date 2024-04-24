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

use Context;
use Exception;
use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\DataModels\Citation\CitationModel;
use APP\plugins\generic\citationManager\classes\Helpers\ClassHelper;
use Author;
use AuthorDAO;
use DAORegistry;
use Issue;
use IssueDAO;
use Journal;
use JournalDAO;
use Publication;
use PublicationDAO;
use Submission;
use SubmissionDAO;

class PluginDAO
{
    /**
     * This method retrieves the structured citations for a publication from the publication object.
     * After this, the method returns a normalized citations as an array of CitationModels.
     * If no citations are found, the method returns an empty array.
     *
     * @param Publication|null $publication
     * @return array
     */
    public function getCitations(Publication|null $publication): array
    {
        if (empty($publication)) return [];

        $citationsIn = json_decode(
            $publication->getData(CitationManagerPlugin::CITATIONS_STRUCTURED),
            true
        );

        if (empty($citationsIn) || json_last_error() !== JSON_ERROR_NONE)
            return [];

        $citationsOut = [];

        foreach ($citationsIn as $citation) {
            if (!empty($citation) && (is_object($citation) || is_array($citation))) {
                $citationsOut[] =
                    ClassHelper::getClassAsArrayWithValuesAssigned(new CitationModel(), $citation);
            }
        }

        return $citationsOut;
    }

    /* OJS getters */
    public function getContext(int $contextId): ?Context
    {
        try {
            /* @var JournalDAO $dao */
            $dao = DAORegistry::getDAO('JournalDAO');
        } catch (Exception $e) {
            error_log($e->getMessage());
        }

        /* @var Context */
        return $dao->getById($contextId);
    }

    public function getIssue(int $issueId): ?Issue
    {
        /* @var IssueDAO $dao */
        $dao = DAORegistry::getDAO('IssueDAO');
        /* @var Issue */
        return $dao->getById($issueId);
    }

    public function getSubmission(int $submissionId): ?Submission
    {
        /* @var SubmissionDAO $dao */
        $dao = DAORegistry::getDAO('SubmissionDAO');
        /* @var Submission */
        return $dao->getById($submissionId);
    }

    public function getPublication(int $publicationId): ?Publication
    {
        /* @var PublicationDAO $dao */
        $dao = DAORegistry::getDAO('PublicationDAO');
        /* @var Publication */
        return $dao->getById($publicationId);
    }

    public function getAuthor(int $authorId): ?Author
    {
        /* @var AuthorDAO $dao */
        $dao = DAORegistry::getDAO('AuthorDAO');
        /* @var Author */
        return $dao->getById($authorId);
    }

    /* OJS setters */
    public function saveContext(Context $context): void
    {
        /* @var JournalDAO $dao */
        $dao = DAORegistry::getDAO('JournalDAO');
        $dao->updateObject($journal);
    }

    public function saveIssue(Issue $issue): void
    {
        /* @var IssueDAO $dao */
        $dao = DAORegistry::getDAO('IssueDAO');
        $dao->updateObject($issue);
    }

    public function saveSubmission(Submission $submission): void
    {
        /* @var SubmissionDAO $dao */
        $dao = DAORegistry::getDAO('SubmissionDAO');
        $dao->updateObject($submission);
    }

    public function savePublication(Publication $publication): void
    {
        /* @var PublicationDAO $dao */
        $dao = DAORegistry::getDAO('PublicationDAO');
        $dao->updateObject($publication);
    }

    public function saveAuthor(Author $author): void
    {
        /* @var AuthorDAO $dao */
        $dao = DAORegistry::getDAO('AuthorDAO');
        $dao->updateObject($author);
    }
}
