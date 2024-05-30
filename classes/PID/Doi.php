<?php
/**
 * @file classes/PID/Doi.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Doi
 * @brief Doi class
 *
 * https://www.crossref.org/blog/dois-and-matching-regular-expressions/
 */

namespace APP\plugins\generic\citationManager\classes\PID;

class Doi extends AbstractPid
{
    /** @copydoc AbstractPid::regex */
    public const regex = '(10[.][0-9]{4,}[^\s"/<>]*/[^\s"<>]+)';

    /** @copydoc AbstractPid::prefix */
    public const prefix = 'https://doi.org';

    /** @copydoc AbstractPid::prefixInCorrect */
    public const prefixInCorrect = [
        'doi:',
        'dx.doi.org'
    ];
}
