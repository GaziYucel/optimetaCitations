<?php
/**
 * @file classes/External/Wikidata/Constants.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Constants
 * @brief Constants class for Wikidata
 */

namespace APP\plugins\generic\citationManager\classes\External\Wikidata;

class Constants
{
    /** @var string The base URL for API requests. */
    public const apiUrl = 'https://test.wikidata.org/w/api.php';

    /** @var string Wikidata username */
    public const username = CITATION_MANAGER_PLUGIN_NAME . '_Wikidata_Username';

    /** @var string Wikidata password */
    public const password = CITATION_MANAGER_PLUGIN_NAME . '_Wikidata_Password';
}
