<?php
/**
 * @file classes/External/Orcid/Inbound.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Inbound
 * @brief Inbound class for Orcid
 */

namespace APP\plugins\generic\citationManager\classes\External\Orcid;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\DataModels\Citation\AuthorModel;
use APP\plugins\generic\citationManager\classes\DataModels\Citation\CitationModel;
use APP\plugins\generic\citationManager\classes\Db\PluginDAO;
use APP\plugins\generic\citationManager\classes\External\ExecuteAbstract;
use APP\plugins\generic\citationManager\classes\External\Orcid\DataModels\Mappings;
use APP\plugins\generic\citationManager\classes\Helpers\ArrayHelper;
use APP\plugins\generic\citationManager\classes\Helpers\ClassHelper;
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
        $publication = $pluginDao->getPublication($this->publicationId);

        $citations = $pluginDao->getCitations($publication);
        $countCitations = count($citations);
        for ($i = 0; $i < $countCitations; $i++) {

            // skip if authors empty
            if (empty($citations[$i]->authors) || !is_countable($citations[$i]->authors))
                continue;

            /** @var CitationModel $citation */
            $citation = ClassHelper::getClassWithValuesAssigned(new CitationModel(), (array)$citations[$i]);

            $countAuthors = count($citation->authors);
            for ($j = 0; $j < $countAuthors; $j++) {
                /* @var AuthorModel $author */
                $author = $citation->authors[$j];

                if (!empty($author->orcid)) {

                    $person = $this->api->getPerson(Orcid::removePrefix($author->orcid));

                    if (empty($person)) continue;

                    foreach (Mappings::getAuthor() as $key => $value) {
                        if (is_array($value)) {
                            $author->$key = ArrayHelper::getValue($person, $value);
                        } else {
                            $author->$key = $person[$value];
                        }

                        if (str_contains(strtolower($author->$key), 'deactivated'))
                            $author->$key = '';
                    }
                }

                $citation->authors[$j] = $author;
            }

            $citations[$i] = $citation;
        }

        $publication->setData(CitationManagerPlugin::CITATIONS_STRUCTURED, json_encode($citations));
        $pluginDao->savePublication($publication);

        return true;
    }
}
