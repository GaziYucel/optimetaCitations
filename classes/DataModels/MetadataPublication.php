<?php
/**
 * @file classes/DataModels/MetadataPublication.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class MetadataPublication
 * @brief Metadata for Publication
 */

namespace APP\plugins\generic\citationManager\classes\DataModels;

class MetadataPublication
{
    /** @var string OpenAlex ID. */
    public const openAlexId = 'openalex_id';

    /** @var string Wikidata QID. */
    public const wikidataId = 'wikidata_id';

    /** @var string Open Citations ID. */
    public const openCitationsId = 'opencitations_id';

    /** @var string GitHub Issue ID for Open Citations. */
    public const githubIssueId = 'github_issue_id';
}
