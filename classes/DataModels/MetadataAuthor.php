<?php
/**
 * @file classes/DataModels/MetadataAuthor.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MetadataAuthor
 * @brief Metadata for Author
 */

namespace APP\plugins\generic\citationManager\classes\DataModels;

class MetadataAuthor
{
    /** @var string OpenAlex ID. */
    public const openAlexId = 'openalex_id';

    /** @var string Wikidata QID. */
    public const wikidataId = 'wikidata_id';
}
