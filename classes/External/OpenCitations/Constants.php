<?php
/**
 * @file classes/External/Wikidata/Constants.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Constants
 * @brief Constants class for OpenCitations
 */

namespace APP\plugins\generic\citationManager\classes\External\OpenCitations;

class Constants
{
    /** @var string GitHub handle / account used for Open Citations */
    public const owner = CITATION_MANAGER_PLUGIN_NAME . '_OpenCitations_Owner';

    /** @var string GitHub repository used for Open Citations */
    public const repository = CITATION_MANAGER_PLUGIN_NAME . '_OpenCitations_Repository';

    /** @var string GitHub APi token used for Open Citations */
    public const token = CITATION_MANAGER_PLUGIN_NAME . '_OpenCitations_Token';
}
