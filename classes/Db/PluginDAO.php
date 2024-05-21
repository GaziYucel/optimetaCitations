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
use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\DataModels\Citation\CitationModel;
use APP\plugins\generic\citationManager\classes\Helpers\ClassHelper;
use Author;
use DAORegistry;
use Exception;
use Issue;
use JournalDAO;
use PKP\context\Context;
use Publication;
use Submission;

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

        if (empty($citationsIn) || json_last_error() !== JSON_ERROR_NONE) return [];

        $citationsOut = [];

        foreach ($citationsIn as $citation) {
            if (!empty($citation) && (is_object($citation) || is_array($citation)))
                $citationsOut[] = ClassHelper::getClassWithValuesAssigned(new CitationModel(), $citation);
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
    public function saveContext(Context $context): void
    {
        try {
            /* @var JournalDAO $dao */
            $dao = DAORegistry::getDAO('JournalDAO');
            $dao->updateObject($context);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
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
