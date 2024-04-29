<?php
/**
 * @file classes/External/OpenAlex/Inbound.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Inbound
 * @brief Inbound class for OpenAlex
 */

namespace APP\plugins\generic\citationManager\classes\External\OpenAlex;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\DataModels\Citation\AuthorModel;
use APP\plugins\generic\citationManager\classes\DataModels\Citation\CitationModel;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataJournal;
use APP\plugins\generic\citationManager\classes\Db\PluginDAO;
use APP\plugins\generic\citationManager\classes\External\ExecuteAbstract;
use APP\plugins\generic\citationManager\classes\External\OpenAlex\DataModels\Mappings;
use APP\plugins\generic\citationManager\classes\Helpers\ArrayHelper;
use APP\plugins\generic\citationManager\classes\Helpers\ClassHelper;
use APP\plugins\generic\citationManager\classes\PID\Doi;
use APP\plugins\generic\citationManager\classes\PID\OpenAlex;
use APP\plugins\generic\citationManager\classes\PID\Orcid;

class Inbound extends ExecuteAbstract
{
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

        $this->api = new Api();
    }

    /** @copydoc InboundAbstract::execute */
    public function execute(): bool
    {
        $pluginDao = new PluginDAO();
        $context = $pluginDao->getContext($this->contextId);
        $publication = $pluginDao->getPublication($this->publicationId);

        // journal
        $onlineIssn = $context->getData('onlineIssn');
        $openAlexId = $context->getData(MetadataJournal::openAlexId);
        if (empty($openAlexId) && !empty($onlineIssn)) {
            $source = $this->api->getSource($onlineIssn);

            if (!empty($source) && !empty($source['id']) && !empty($source['issn_l'] && $source['issn_l'] === $onlineIssn)) {
                $openAlexId = OpenAlex::removePrefix($source['id']);
                $context->setData(MetadataJournal::openAlexId, $openAlexId);
                $pluginDao->saveContext($context);
            }
        }

        // citations
        $citations = $pluginDao->getCitations($publication);
        $countCitations = count($citations);
        for ($i = 0; $i < $countCitations; $i++) {
            /* @var CitationModel $citation */
            $citation = ClassHelper::getClassWithValuesAssigned(new CitationModel(), $citations[$i]);

            if ($citation->isProcessed || empty($citation->doi) || !empty($citation->openalex_id))
                continue;

            $citation = $this->getCitationWork($citation);

            if (!empty($citation->openalex_id)) $citation->isProcessed = true;
            $citation->openalex_id = OpenAlex::removePrefix($citation->openalex_id);

            $citations[$i] = $citation;
        }

        $publication->setData(CitationManagerPlugin::CITATIONS_STRUCTURED, json_encode($citations));
        $pluginDao->savePublication($publication);

        return true;
    }

    /**
     * Get citation (work) from external service
     *
     * @param CitationModel $citation
     * @return CitationModel
     */
    public function getCitationWork(CitationModel $citation): CitationModel
    {
        $openAlexArray = $this->api->getWork(Doi::removePrefix($citation->doi));

        if (empty($openAlexArray)) return $citation;

        foreach (Mappings::getWork() as $key => $value) {
            switch ($key) {
                case 'authors':
                    foreach ($openAlexArray['authorships'] as $index => $authorship) {
                        $citation->authors[] = $this->getCitationAuthor($authorship);
                    }
                    break;
                default:
                    if (is_array($value)) {
                        $citation->$key =
                            ArrayHelper::getValue($openAlexArray, $value);
                    } else {
                        $citation->$key = $openAlexArray[$value];
                    }
                    break;
            }
        }

        return $citation;
    }

    /**
     * Convert to AuthorModel with mappings
     *
     * @param array $authorIn Input values
     * @return AuthorModel
     */
    private function getCitationAuthor(array $authorIn): AuthorModel
    {
        $authorOut = new AuthorModel();
        $mappings = Mappings::getAuthor();

        foreach ($mappings as $key => $val) {
            if (is_array($val)) {
                $authorOut->$key = ArrayHelper::getValue($authorIn, $val);
            } else {
                $authorOut->$key = $authorIn[$key];
            }
        }

        $authorOut->display_name = trim(str_replace('null', '', $authorOut->display_name));
        if (empty($authorOut->display_name)) $authorOut->display_name = $authorIn['raw_author_name'];

        $authorDisplayNameParts = explode(' ', trim($authorOut->display_name));
        if (count($authorDisplayNameParts) > 1) {
            $authorOut->family_name = array_pop($authorDisplayNameParts);
            $authorOut->given_name = implode(' ', $authorDisplayNameParts);
        }

        $authorOut->orcid_id = Orcid::removePrefix($authorOut->orcid_id);
        $authorOut->openalex_id = OpenAlex::removePrefix($authorOut->openalex_id);

        return $authorOut;
    }
}
