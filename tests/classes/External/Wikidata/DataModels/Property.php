<?php
/**
 * @file classes/External/Wikidata/DataModels/Property.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Property
 * @brief Properties on Wikidata.
 * @see https://hay.toolforge.org/propbrowse
 */

namespace APP\plugins\generic\citationManager\classes\External\Wikidata\DataModels;

class Property
{
    public array $instanceOfScientificJournal = ['id' => 'P97010', 'default' => 'Q233685'];
    public array $instanceOfScientificArticle = ['id' => 'P97010', 'default' => 'Q227265'];
    public array $instanceOfHuman = ['id' => 'P97010', 'default' => 'Q497'];
    public array $title = ['id' => 'P97011', 'default' => ''];
    public array $author = ['id' => 'P97009', 'default' => ''];
    public array $publicationDate = ['id' => 'P97013', 'default' => ''];
    public array $publishedIn = ['id' => 'P97014', 'default' => ''];
    public array $volume = ['id' => 'P97015', 'default' => ''];
    public array $citesWork = ['id' => 'P97016', 'default' => ''];
    public array $doi = ['id' => 'P97017', 'default' => ''];
    public array $orcidId = ['id' => 'P98119', 'default' => ''];
    public array $issnL = ['id' => 'P98127', 'default' => ''];
}
