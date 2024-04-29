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
use APP\plugins\generic\citationManager\classes\DataModels\MetadataAuthor;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataJournal;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataPublication;
use APP\plugins\generic\citationManager\classes\Db\PluginDAO;
use APP\plugins\generic\citationManager\classes\External\ExecuteAbstract;
use APP\plugins\generic\citationManager\classes\External\Wikidata\DataModels\Claim;
use APP\plugins\generic\citationManager\classes\External\Wikidata\DataModels\Property;
use APP\plugins\generic\citationManager\classes\Helpers\ClassHelper;
use APP\plugins\generic\citationManager\classes\PID\Orcid;
use Author;
use Issue;
use Publication;

class Outbound extends ExecuteAbstract
{
    /** @var Property */
    private Property $property;

    /** @copydoc InboundAbstract::__construct */
    public function __construct(CitationManagerPlugin &$plugin,
                                int                   $contextId,
                                int                   $submissionId,
                                int                   $publicationId)
    {
        parent::__construct(
            $plugin,
            $contextId,
            $submissionId,
            $publicationId);

        $this->api = new Api([
            'username' => $this->plugin->getSetting($this->contextId, Constants::username),
            'password' => $this->plugin->getSetting($this->contextId, Constants::password)
        ]);

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

        $pluginDao = new PluginDAO();
        $context = $pluginDao->getContext($this->contextId);
        $publication = $pluginDao->getPublication($this->publicationId);
        $locale = $publication->getData('locale');

        $issue = null;
        if (!empty($publication->getData('issueId'))) {
            $issue = $pluginDao->getIssue($publication->getData('issueId'));
        }

        // journal
        $onlineIssn = $context->getData('onlineIssn');
        $journalLabel = $context->getData('name')[$locale];
        $journalWikidataId = $context->getData(MetadataJournal::wikidataId);
        if (empty($journalWikidataId) && !empty($onlineIssn) && !empty($journalLabel)) {
            $journalWikidataId = $this->processJournal($onlineIssn, $journalLabel, $locale);
            $context->setData(MetadataJournal::wikidataId, $journalWikidataId);
            $pluginDao->saveContext($context);
        }

        // authors of publication
        /* @var Author $author */
        foreach ($publication->getData('authors') as $index => $author) {
            if (empty($author->getData(MetadataAuthor::wikidataId))) {
                $pluginDao->saveAuthor($this->processAuthor($locale, $author));
            }
        }
        $publication = $pluginDao->getPublication($this->publicationId);

        // citations
        $citations = $pluginDao->getCitations($publication);
        $countCitations = count($citations);
        for ($i = 0; $i < $countCitations; $i++) {
            /* @var CitationModel $citation */
            $citation = Classhelper::getClassWithValuesAssigned(new CitationModel(), $citations[$i]);

            if ($citation->isProcessed && empty($citation->wikidata_id))
                $citation->wikidata_id = $this->processCitedArticle($locale, $citation);

            $citations[$i] = $citation;
        }
        $publication->setData(
            CitationManagerPlugin::CITATIONS_STRUCTURED,
            json_encode($citations));
        $pluginDao->savePublication($publication);

        // main article
        $publication->setData(
            MetadataPublication::wikidataId,
            $this->processMainArticle($locale, $issue, $publication));
        $pluginDao->savePublication($publication);

        if (empty($publication->getData(MetadataPublication::wikidataId))) return false;

        // get main article
        $item = $this->api->getItemWithQid($publication->getData(MetadataPublication::wikidataId));

        // published in main article
        $this->addReferenceClaim($item,
            $context->getData(MetadataJournal::wikidataId),
            $this->property->publishedIn['id']);

        // authors in main article
        foreach ($publication->getData('authors') as $index => $author) {
            /** @var Author $author */
            $this->addReferenceClaim($item,
                $author->getData(MetadataAuthor::wikidataId),
                $this->property->author['id']);
        }

        // cites work in main article
        foreach ($citations as $index => $citation) {
            $this->addReferenceClaim($item,
                $citation->wikidata_id,
                $this->property->citesWork['id']);
        }

        return true;
    }

    /**
     * Create journal and return QID
     *
     * @param string $pid
     * @param string $label
     * @param string $locale
     * @return string
     */
    private function processJournal(string $pid, string $label, string $locale): string
    {
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
     * Create author and return Author
     *
     * @param string $locale
     * @param Author $author
     * @return Author
     */
    private function processAuthor(string $locale, Author $author): Author
    {
        $pid = Orcid::removePrefix($author->getData('orcid'));
        $label = trim($author->getGivenName($locale) . ' ' . $author->getFamilyName($locale));

        // find qid and return author
        $qid = $this->api
            ->getQidFromItem($this->api
                ->getItemWithPropertyAndPid(
                    $this->property->orcidId['id'], $pid));
        if (!empty($qid)) {
            $author->setData(MetadataAuthor::wikidataId, $qid);
            return $author;
        }

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

        $author->setData(MetadataAuthor::wikidataId, $this->api->addItemAndReturnQid($data));

        return $author;
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
                (string)$issue->getVolume())
        ];

        return $this->api->addItemAndReturnQid($data);
    }

    /**
     * Add published in reference to the main article.
     *
     * @param array $item https://www.wikidata.org/w/api.php?action=wbgetentities&ids=Q106622495
     * @param string $referencedQId
     * @param string $property
     * @return void
     */
    private function addReferenceClaim(array $item, string $referencedQId, string $property): void
    {
        if (empty($referencedQId)) return;

        $createClaim = true;

        if (!empty($item['claims'][$property])) {
            foreach ($item['claims'][$property] as $index => $claim) {
                if (strtolower($claim['mainsnak']['datavalue']['value']['id'])
                    === strtolower($referencedQId)) {
                    $createClaim = false;
                }
            }
        }

        $claim = new Claim();

        if ($createClaim) {
            $this->api->createWikibaseItemClaim(
                $item['title'],
                $property,
                $claim->getWikibaseItemReference($referencedQId));
        }
    }
}
