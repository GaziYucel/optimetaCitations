<?php
/**
 * @file classes/External/OpenCitations/Outbound.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Outbound
 * @brief Outbound class for OpenCitations
 */

namespace APP\plugins\generic\citationManager\classes\External\OpenCitations;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\DataModels\Citation\AuthorModel;
use APP\plugins\generic\citationManager\classes\DataModels\Citation\CitationModel;
use APP\plugins\generic\citationManager\classes\DataModels\Metadata\MetadataJournal;
use APP\plugins\generic\citationManager\classes\DataModels\Metadata\MetadataPublication;
use APP\plugins\generic\citationManager\classes\External\OutboundAbstract;
use APP\plugins\generic\citationManager\classes\External\OpenCitations\DataModels\WorkCitingCited;
use APP\plugins\generic\citationManager\classes\External\OpenCitations\DataModels\WorkMetaData;
use APP\plugins\generic\citationManager\classes\Helpers\ClassHelper;
use APP\plugins\generic\citationManager\classes\PID\Arxiv;
use APP\plugins\generic\citationManager\classes\PID\Doi;
use APP\plugins\generic\citationManager\classes\PID\Handle;
use APP\plugins\generic\citationManager\classes\PID\Orcid;
use Author;
use Context;
use Issue;
use Publication;
use Submission;

class Outbound extends OutboundAbstract
{
    /** @var string The syntax for the title of the issue */
    protected string $titleSyntax = 'deposit {domain} {pid}';

    /** @var string The separator to separate the work and the citations CSV */
    protected string $separator = '===###===@@@===';

    /** @var string Default article type */
    protected string $defaultType = 'journal article';

    /** @copydoc InboundAbstract::__construct */
    public function __construct(CitationManagerPlugin &$plugin,
                                ?Context              $context,
                                ?Issue                $issue,
                                ?Submission           $submission,
                                ?Publication          $publication,
                                ?MetadataJournal      $metadataJournal,
                                ?MetadataPublication  $metadataPublication,
                                ?array                $authors,
                                ?array                $citations)
    {
        parent::__construct($plugin, $context, $issue, $submission, $publication,
            $metadataJournal, $metadataPublication, $authors, $citations);

        $this->api = new Api($plugin);
    }

    /**
     * Process this external service
     *
     * @return bool
     */
    public function execute(): bool
    {
        // return if already deposited
        if (!empty($this->metadataPublication->opencitations_id)) return true;

        // return false if required data not provided
        if (!$this->api->isDepositPossible()) return false;

        // title of GitHub issue
        $title =
            str_replace(
                '{domain} {pid}',
                $_SERVER['SERVER_NAME'] . ' ' . 'doi:' . $this->publication->getStoredPubId('doi'),
                $this->titleSyntax);

        // body of GitHub issue
        $body =
            ClassHelper::getClassPropertiesAsCsv(new WorkMetaData()) . PHP_EOL .
            $this->getPublicationCsv() . PHP_EOL .
            $this->getCitationsCsv() . PHP_EOL .
            $this->separator . PHP_EOL .
            ClassHelper::getClassPropertiesAsCsv(new WorkCitingCited()) . PHP_EOL .
            $this->getRelationsCsv() . PHP_EOL;

        $githubIssueId = $this->api->addIssue($title, $body);

        if (!empty($githubIssueId) && $githubIssueId !== 0)
            $this->metadataPublication->opencitations_id = $githubIssueId;

        return true;
    }

    /**
     * Get Work as publication metadata in comma separated format
     *
     * @return string
     */
    private function getPublicationCsv(): string
    {
        $work = new WorkMetaData();

        $locale = $this->publication->getData('locale');

        // id
        $work->id = 'doi:' . Doi::removePrefix($this->publication->getStoredPubId('doi'));

        // title
        $work->title = $this->publication->getData('title')[$locale];

        // familyName, givenNames [orcid: 0000]
        /** @var Author $author */
        foreach ($this->authors as $index => $author) {
            if (!empty($author->getFamilyName($locale))) $work->author .= $author->getFamilyName($locale) . ', ';
            if (!empty($author->getGivenName($locale))) $work->author .= $author->getGivenName($locale);
            if (!empty($author->getData('orcid'))) $work->author .= ' [orcid:' . Orcid::removePrefix($author->getData('orcid')) . ']';
            $work->author .= '; ';
        }
        $work->author = trim($work->author, '; ');

        // pub_date
        $work->pub_date = '';
        if (!empty($this->issue->getData('datePublished')))
            $work->pub_date = date('Y-m-d', strtotime($this->issue->getData('datePublished')));

        // venue
        $work->venue = $this->context->getData('name')[$locale];
        $venueIds = '';
        if (!empty($this->context->getData('onlineIssn'))) $venueIds .= 'issn:' . $this->context->getData('onlineIssn') . ' ';
        if (!empty($this->context->getData('printIssn'))) $venueIds .= 'issn:' . $this->context->getData('printIssn') . ' ';
        if (!empty($this->issue->getStoredPubId('doi'))) $venueIds .= 'doi:' . $this->issue->getStoredPubId('doi') . ' ';
        if (!empty($venueIds)) $work->venue = trim($work->venue) . ' ' . '[' . trim($venueIds) . ']';

        // volume
        $work->volume = '';
        if (!empty($this->issue->getData('volume'))) $work->volume = $this->issue->getData('volume');

        // issue
        $work->issue = '';
        if (!empty($this->issue->getData('number'))) $work->issue = $this->issue->getData('number');

        // page
        $work->page = '';

        // type
        $work->type = $this->defaultType;
        if (!empty($this->context->getData('publisherInstitution')))
            $work->publisher = $this->context->getData('publisherInstitution');

        // editor
        $work->editor = '';

        $values = '';
        foreach ($work as $name => $value) {
            $values .= '"' . str_replace('"', '\"', $value) . '",';
        }

        return trim($values, ',');
    }

    /**
     * Get Citations as citations in comma separated format
     *
     * @return string
     */
    private function getCitationsCsv(): string
    {
        $values = '';

        foreach ($this->citations as $citationRow) {

            /* @var CitationModel $citation */
            $citation = ClassHelper::getClassWithValuesAssigned(new CitationModel(), $citationRow);
            $workMetaData = new WorkMetaData();

            if (!empty($citation->doi)) $workMetaData->id .= 'doi:' . Doi::removePrefix($citation->doi) . ' ';
            if (!empty($citation->url)) $workMetaData->id .= $this->getUrl($citation->url) . ' ';
            if (!empty($citation->urn)) $workMetaData->id .= 'urn:' . str_replace(' ', '', $citation->urn) . ' ';
            $workMetaData->id = trim($workMetaData->id);

            $workMetaData->title = $citation->title;

            $workMetaData->author = '';
            if (!empty($citation->authors)) {
                foreach ($citation->authors as $authorRow) {
                    /* @var AuthorModel $author */
                    $author = ClassHelper::getClassWithValuesAssigned(new AuthorModel(), $authorRow);
                    if (empty($author->orcid_id)) {
                        $workMetaData->author .= $author->display_name;
                    } else {
                        $workMetaData->author .= $author->family_name . ', ' . $author->given_name;
                    }
                    $workMetaData->author .= ' [orcid:' . $author->orcid_id . ']';
                    $workMetaData->author .= '; ';
                }
                $workMetaData->author = trim($workMetaData->author, '; ');
            }

            $workMetaData->pub_date = $citation->publication_date;

            $workMetaData->venue = $citation->journal_name;
            if (!empty($citation->journal_issn_l)) $workMetaData->venue .= ' [issn:' . $citation->journal_issn_l . ']';

            $workMetaData->volume = $citation->volume;
            $workMetaData->issue = $citation->issue;
            $workMetaData->page = '';
            $workMetaData->type = str_replace('-', ' ', $citation->type);
            $workMetaData->publisher = $citation->journal_publisher;
            $workMetaData->editor = '';

            if (!empty($workMetaData->id)) {
                foreach ($workMetaData as $name => $value) {
                    $values .= '"' . str_replace('"', '\"', $value) . '",';
                }
                $values = trim($values, ',');
                $values = $values . PHP_EOL;
            }
        }

        return trim($values, PHP_EOL);
    }

    /**
     * Get Citations in comma separated format
     *
     * @return string
     */
    private function getRelationsCsv(): string
    {
        $doi = $this->publication->getStoredPubId('doi');
        $publicationDate = date('Y-m-d', strtotime($this->issue->getData('datePublished')));

        $values = '';
        foreach ($this->citations as $index => $citationRow) {

            /* @var CitationModel $citation22 */
            $citation = ClassHelper::getClassWithValuesAssigned(new CitationModel(), $citationRow);

            $workCitingCited = new WorkCitingCited();

            $workCitingCited->citing_id = 'doi:' . $doi;
            $workCitingCited->citing_publication_date = $publicationDate;

            $workCitingCited->cited_id = '';
            if (!empty($citation->doi)) $workCitingCited->cited_id .= 'doi:' . $citation->doi . ' ';
            if (!empty($citation->url)) $workCitingCited->cited_id .= $this->getUrl($citation->url) . ' ';
            if (!empty($citation->urn)) $workCitingCited->cited_id .= 'urn:' . str_replace(' ', '', $citation->urn) . ' ';
            $workCitingCited->cited_id = trim($workCitingCited->cited_id);

            $workCitingCited->cited_publication_date = $citation->publication_date;

            if (!empty($workCitingCited->cited_id)) {
                foreach ($workCitingCited as $name => $value) {
                    $values .= '"' . str_replace('"', '\"', $value) . '",';
                }
                $values = trim($values, ',');
                $values = $values . PHP_EOL;
            }
        }

        return trim($values, PHP_EOL);
    }

    /**
     * Get url as arxiv, handle or url
     *
     * @param string $url
     * @return string
     */
    private function getUrl(string $url): string
    {
        $url = Handle::normalize($url);
        $url = Arxiv::normalize($url);

        if (str_contains($url, Arxiv::prefix)) {
            return 'arxiv:' . Arxiv::removePrefix($url) . ' ';
        } else if (str_contains($url, Handle::prefix)) {
            return 'handle:' . Handle::removePrefix($url) . ' ';
        } else {
            return 'url:' . str_replace(' ', '', $url) . ' ';
        }
    }
}
