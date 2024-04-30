<?php
/**
 * @file External/Orcid/DataModels/Mappings.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Mappings
 * @brief Mapping of internal data models and external
 *
 * @see https://orcid.org/0000-0002-2013-6920
 * @see https://pub.orcid.org/v2.1/0000-0002-2013-6920
 */

namespace APP\plugins\generic\citationManager\classes\External\Orcid\DataModels;

final class Mappings
{
    /**
     * Authors are people who create works.
     *
     * @return array [ internal => orcid, ... ]
     */
    public static function getAuthor(): array
    {
        return [
            'givenName' => ['person', 'name', 'given-names', 'value'],
            'familyName' => ['person', 'name', 'family-name', 'value']
        ];
    }
}
