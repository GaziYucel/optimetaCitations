<?php
/**
 * @file classes/PID/Wikidata.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Wikidata
 * @brief Wikidata class
 */

namespace APP\plugins\generic\citationManager\classes\PID;

class Wikidata extends AbstractPid
{
    /** @copydoc AbstractPid::prefix */
    public const prefix = 'https://test.wikidata.org/wiki';

    /** @copydoc AbstractPid::prefixInCorrect */
    public const prefixInCorrect = [
        'wikidata:'
    ];
}
