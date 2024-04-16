<?php
/**
 * @file classes/External/Wikidata/Outbound.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Outbound
 * @brief Outbound class Wikidata
 */

namespace APP\plugins\generic\citationManager\classes\External\Wikidata;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\DataModels\Citation\CitationModel;
use APP\plugins\generic\citationManager\classes\DataModels\Metadata\MetadataAuthor;
use APP\plugins\generic\citationManager\classes\DataModels\Metadata\MetadataJournal;
use APP\plugins\generic\citationManager\classes\DataModels\Metadata\MetadataPublication;
use APP\plugins\generic\citationManager\classes\Db\PluginDAO;
use APP\plugins\generic\citationManager\classes\External\OutboundAbstract;
use APP\plugins\generic\citationManager\classes\External\Wikidata\DataModels\Claim;
use APP\plugins\generic\citationManager\classes\External\Wikidata\DataModels\Property;
use APP\plugins\generic\citationManager\classes\Helpers\ClassHelper;
use APP\plugins\generic\citationManager\classes\PID\Orcid;
use Author;
use Context;
use Issue;
use Publication;
use Submission;

class Outbound extends OutboundAbstract
{
    /** @var Property */
    private Property $property;

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
        $this->property = new Property();
    }

    /**
     * Process this external service
     *
     * @return bool
     */
    public function execute(): bool
    {
        // return false if required data not provided
        if (!$this->api->isDepositPossible()) return false;

        $locale = $this->publication->getData('locale');

        $pluginDao = new PluginDAO();

        // journal
        if (empty($this->metadataJournal->wikidata_id)) {
            $this->metadataJournal->wikidata_id = $this->processJournal($locale, $this->context);
            $pluginDao->saveMetadataJournal($this->context->getId(), $this->metadataJournal);
        }

        // author(s)
        /** @var Author $author */
        $countAuthors = count($this->authors);
        for ($i = 0; $i < $countAuthors; $i++) {
            $author = $this->authors[$i];
            $metadata = $pluginDao->getMetadataAuthor($author->getId(), $author);

            $orcidId = Orcid::removePrefix($author->getData('orcid'));
            $displayName = trim($author->getGivenName($locale) . ' ' . $author->getFamilyName($locale));

            if (empty($metadata->wikidata_id) && !empty($orcidId) && !empty($displayName))
                $metadata->wikidata_id = $this->processAuthor($locale, $orcidId, $displayName);

            $author->setData(CitationManagerPlugin::METADATA_AUTHOR, $metadata);

            $this->authors[$i] = $author;
        }

        // cited articles
        $countCitations = count($this->citations);
        for ($i = 0; $i < $countCitations; $i++) {
            /* @var CitationModel $citation */
            $citation = Classhelper::getClassWithValuesAssigned(new CitationModel(), $this->citations[$i]);

            if ($citation->isProcessed && empty($citation->wikidata_id))
                $citation->wikidata_id = $this->processCitedArticle($locale, $citation);

            $this->citations[$i] = $citation;
        }

        // main article
        $this->metadataPublication->wikidata_id = $this->processMainArticle($locale, $this->issue, $this->publication);

        if (empty($this->metadataPublication->wikidata_id)) return false;

        // get main article
        $item = $this->api->getItemWithQid($this->metadataPublication->wikidata_id);

        // published in main article
        $this->addReferenceClaim($item,
            (array)$this->metadataJournal,
            $this->property->publishedIn['id']);

        // authors in main article
        foreach ($this->authors as $index => $author) {
            /** @var Author $author */
            $this->addReferenceClaim($item,
                (array)$author->getData(CitationManagerPlugin::METADATA_AUTHOR),
                $this->property->author['id']);
        }

        // cites work in main article
        foreach ($this->citations as $index => $citation) {
            $this->addReferenceClaim($item,
                (array)$citation,
                $this->property->citesWork['id']);
        }

        return true;
    }

    /**
     * Create journal and return QID
     *
     * @param string $locale
     * @param Context $context
     * @return string
     */
    private function processJournal(string $locale, Context $context): string
    {
        $pid = $context->getData('onlineIssn');
        $label = $context->getData('name')[$locale];

        if (empty($pid) || empty($label)) return '';

        // find qid and return qid if found
        $qid = $this->api
            ->getQidFromItem($this->api
                ->getItemWithPropertyAndPid(
                    $this->property->doi['id'], $pid));
        if (!empty($qid)) return $qid;

        // not found, create and return qid
        $claim = new Claim();

        $data['labels'] = $claim->getLabels($locale, $label);

        $data['claims'] = [
            $claim->getInstanceOf(
                $this->property->instanceOfScientificJournal['id'],
                $this->property->instanceOfScientificJournal['default']),
            $claim->getExternalId(
                $this->property->issnL['id'],
                $pid),
            $claim->getMonoLingualText(
                $this->property->title['id'],
                $label,
                $locale)
        ];

        return $this->api->addItemAndReturnQid($data);
    }

    /**
     * Create author and return QID
     *
     * @param string $locale
     * @param string $pid
     * @param string $label
     * @return string
     */
    private function processAuthor(string $locale, string $pid, string $label): string
    {
        // find qid and return qid if found
        $qid = $this->api
            ->getQidFromItem($this->api
                ->getItemWithPropertyAndPid(
                    $this->property->orcidId['id'], $pid));
        if (!empty($qid)) return $qid;

        // not found, create and return qid
        $claim = new Claim();

        $data['labels'] = $claim->getLabels($locale, $label);

        $data['claims'] = [
            $claim->getInstanceOf(
                $this->property->instanceOfHuman['id'],
                $this->property->instanceOfHuman['default']
            ),
            $claim->getExternalId(
                $this->property->orcidId['id'],
                $pid)
        ];

        return $this->api->addItemAndReturnQid($data);
    }

    /**
     * Create cited article and return QID
     *
     * @param string $locale
     * @param CitationModel $citation
     * @return string
     */
    private function processCitedArticle(string $locale, CitationModel $citation): string
    {
        if (empty($locale) || empty($citation->doi)) return '';

        $pid = $citation->doi;
        $label = $citation->title;

        // find qid and return qid if found
        $qid = $this->api
            ->getQidFromItem($this->api
                ->getItemWithPropertyAndPid(
                    $this->property->doi['id'], $pid));
        if (!empty($qid)) return $qid;

        // not found, create and return qid
        $claim = new Claim();

        $data['labels'] = $claim->getLabels($locale, $label);

        $data['claims'] = [
            $claim->getInstanceOf(
                $this->property->instanceOfScientificArticle['id'],
                $this->property->instanceOfScientificArticle['default']),
            $claim->getExternalId(
                $this->property->doi['id'],
                $pid),
            $claim->getMonoLingualText(
                $this->property->title['id'],
                $label,
                $locale)
        ];

        return $this->api->addItemAndReturnQid($data);
    }

    /**
     * Create main article and return QID
     *
     * @param string $locale
     * @param Issue $issue
     * @param Publication $publication
     * @return string
     */
    private function processMainArticle(string $locale, Issue $issue, Publication $publication): string
    {
        $pluginDao = new PluginDAO();

        // find qid and return qid if found
        $qid = $this->api
            ->getQidFromItem($this->api
                ->getItemWithPropertyAndPid(
                    $this->property->doi['id'], $publication->getStoredPubId('doi')
                )
            );
        if (!empty($qid)) return $qid;

        // not found, create and return qid
        $claim = new Claim();
        $data['labels'] = $claim->getLabels($locale, $publication->getData('title')[$locale]);

        $data['claims'] = [
            $claim->getInstanceOf(
                $this->property->instanceOfScientificArticle['id'],
                $this->property->instanceOfScientificArticle['default']),
            $claim->getExternalId(
                $this->property->doi['id'],
                $publication->getStoredPubId('doi')),
            $claim->getMonoLingualText(
                $this->property->title['id'],
                $publication->getData('title')[$locale],
                $locale),
            $claim->getPointInTime(
                $this->property->publicationDate['id'],
                date('+Y-m-d\T00:00:00\Z', strtotime($issue->getData('datePublished')))),
            $claim->getString(
                $this->property->volume['id'],
                $issue->getVolume())
        ];

        return $this->api->addItemAndReturnQid($data);
    }

    /**
     * Add published in reference to the main article.
     *
     * @param array $item https://www.wikidata.org/w/api.php?action=wbgetentities&ids=Q106622495
     * @param array $referenced MetadataAuthor, MetadataJournal
     * @param string $property
     * @return void
     */
    private function addReferenceClaim(array $item, array $referenced, string $property): void
    {
        if (empty($referenced['wikidata_id'])) return;

        $createClaim = true;

        if (!empty($item['claims'][$property])) {
            foreach ($item['claims'][$property] as $index => $claim) {
                if (strtolower($claim['mainsnak']['datavalue']['value']['id'])
                    === strtolower($referenced['wikidata_id'])) {
                    $createClaim = false;
                }
            }
        }

        $claim = new Claim();

        if ($createClaim) {
            $this->api->createWikibaseItemClaim(
                $item['title'],
                $property,
                $claim->getWikibaseItemReference($referenced['wikidata_id']));
        }
    }
}
