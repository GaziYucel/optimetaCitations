<?php
/**
 * @file classes/PID/GitHubIssue.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GitHubIssue
 * @brief GitHubIssue class
 */

namespace APP\plugins\generic\citationManager\classes\PID;

class GitHubIssue extends AbstractPid
{
    /** @copydoc AbstractPid::prefix */
    public const prefix = 'https://github.com/gaziyucel/crowdsourcing/issues';
}
