<?php
/**
 * @file classes/External/External/ExecuteAbstract.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ExecuteAbstract
 * @brief Abstract class to be extended by Inbound and Outbound classes.
 */

namespace APP\plugins\generic\citationManager\classes\External;

use APP\plugins\generic\citationManager\CitationManagerPlugin;

class ExecuteAbstract
{
    /** @var ApiAbstract */
    public ApiAbstract $api;

    /** @var CitationManagerPlugin */
    protected CitationManagerPlugin $plugin;

    /** @var int */
    protected int $contextId = 0;

    /** @var int */
    protected int $submissionId = 0;

    /** @var int */
    protected int $publicationId = 0;

    /**
     * Constructor
     *
     * @param CitationManagerPlugin $plugin
     * @param int $contextId
     * @param int $submissionId
     * @param int $publicationId
     */
    public function __construct(CitationManagerPlugin &$plugin,
                                int                   $contextId,
                                int                   $submissionId,
                                int                   $publicationId)
    {
        $this->plugin = &$plugin;
        $this->contextId = $contextId;
        $this->submissionId = $submissionId;
        $this->publicationId = $publicationId;
    }

    /**
     * Executes this external service
     *
     * @return bool
     */
    public function execute(): bool
    {
        return true;
    }
}
