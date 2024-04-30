<?php
/**
 * @file classes/External/Orcid/Constants.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Constants
 * @brief Constants class
 */

namespace APP\plugins\generic\citationManager\classes\External\Orcid;

use APP\plugins\generic\citationManager\classes\External\ConstantsAbstract;

class Constants extends ConstantsAbstract
{
    /** @var string The base URL for API requests. */
    public const apiUrl = 'https://pub.orcid.org/v2.1';
}
