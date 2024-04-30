<?php
/**
 * @file classes/DataModels/AuthorModel.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AuthorModel
 * @brief Authors are people who create works.
 */

namespace APP\plugins\generic\citationManager\classes\DataModels\Citation;

class AuthorModel
{
    /** @var string|null The ORCID ID for this author. */
    public ?string $orcid = null;

    /** @var string|null The name of the author as a single string. */
    public ?string $displayName = null;

    /** @var string|null The given name of the author as a single string. */
    public ?string $givenName = null;

    /** @var string|null The family name of the author as a single string. */
    public ?string $familyName = null;

    /** @var string|null The Wikidata QID */
    public ?string $wikidataId = null;

    /** @var string|null The OpenAlex ID */
    public ?string $openAlexId = null;
}
