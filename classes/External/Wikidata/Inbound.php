<?php
/**
 * @file classes/External/Wikidata/Inbound.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Inbound
 * @brief Inbound class for Wikidata
 */

namespace APP\plugins\generic\citationManager\classes\External\Wikidata;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\DataModels\Citation\CitationModel;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataAuthor;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataJournal;
use APP\plugins\generic\citationManager\classes\Db\PluginDAO;
use APP\plugins\generic\citationManager\classes\External\ExecuteAbstract;
use APP\plugins\generic\citationManager\classes\External\Wikidata\DataModels\Property;
use APP\plugins\generic\citationManager\classes\Helpers\ClassHelper;
use APP\plugins\generic\citationManager\classes\PID\Orcid;
use APP\plugins\generic\citationManager\classes\PID\Wikidata;
use Author;

class Inbound extends ExecuteAbstract
{
    /** @var Property */
    public Property $property;

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

    /** @copydoc InboundAbstract::execute */
    public function execute(): bool
    {
        $pluginDao = new PluginDAO();
        $context = $pluginDao->getContext($this->contextId);
        $publication = $pluginDao->getPublication($this->publicationId);

        // journal
        $onlineIssn = $context->getData('onlineIssn');
        $journalWikidataId = $context->getData(MetadataJournal::wikidataId);
        if (empty($journalWikidataId) && !empty($onlineIssn)) {
            $journalWikidataId = $this->processJournal($onlineIssn);
            $context->setData(MetadataJournal::wikidataId, $journalWikidataId);
            $pluginDao->saveContext($context);
        }

        // authors of publication
        /* @var Author $author */
        foreach ($publication->getData('authors') as $index => $author) {
            if (empty($author->getData(MetadataAuthor::wikidataId))) {
                $pluginDao->saveAuthor($this->processAuthor($author));
            }
        }
        $publication = $pluginDao->getPublication($this->publicationId);

        // citations
        $citations = $pluginDao->getCitations($publication);
        $countCitations = count($citations);
        for ($i = 0; $i < $countCitations; $i++) {
            /* @var CitationModel $citation */
            $citation = ClassHelper::getClassWithValuesAssigned(new CitationModel(), $citations[$i]);

            if (!empty($citation->wikidata_id)) continue;

            $citation = $this->processCitation($citation);

            $citation->wikidata_id = Wikidata::removePrefix($citation->wikidata_id);

            $citations[$i] = $citation;
        }
        $publication->setData(CitationManagerPlugin::CITATIONS_STRUCTURED, json_encode($citations));
        $pluginDao->savePublication($publication);

        return true;
    }

    /**
     * Get wikidata id for journal
     *
     * @param string $pid
     * @return string
     */
    public function processJournal(string $pid): string
    {
        return $this->api
            ->getQidFromItem($this->api
                ->getItemWithPropertyAndPid(
                    $this->property->issnL['id'], $pid));
    }

    /**
     * Get citation (work) from external service
     *
     * @param CitationModel $citation
     * @return CitationModel
     */
    public function processCitation(CitationModel $citation): CitationModel
    {
        $qid = $this->api
            ->getQidFromItem($this->api
                ->getItemWithPropertyAndPid(
                    $this->property->doi['id'], $citation->doi));

        if (!empty($qid)) $citation->wikidata_id = $qid;

        return $citation;
    }

    /**
     * Get wikidata id for author
     *
     * @param Author $author
     * @return Author
     */
    public function processAuthor(Author $author): Author
    {
        $wikidataId = $author->getData(MetadataAuthor::wikidataId);
        $orcidId = Orcid::removePrefix($author->getData('orcid'));

        if (empty($wikidataId) && !empty($orcidId))
            $wikidataId = $this->api
                ->getQidFromItem($this->api
                    ->getItemWithPropertyAndPid(
                        $this->property->orcidId['id'], $orcidId));

        $wikidataId = Wikidata::removePrefix($wikidataId);

        $author->setData(MetadataAuthor::wikidataId, $wikidataId);

        return $author;
    }
}
