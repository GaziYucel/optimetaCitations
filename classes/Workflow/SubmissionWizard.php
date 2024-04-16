<?php
/**
 * @file classes/Workflow/SubmissionWizard.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionWizard
 * @brief Submission wizard
 */

namespace APP\plugins\generic\citationManager\classes\Workflow;

use APP\plugins\generic\citationManager\CitationManagerPlugin;

class SubmissionWizard
{
    /**@var CitationManagerPlugin */
    public CitationManagerPlugin $plugin;

    /** @param CitationManagerPlugin $plugin */
    public function __construct(CitationManagerPlugin &$plugin)
    {
        $this->plugin = &$plugin;
    }

    /**
     * Structured citations on submission wizard page
     *
     * @param string $hookname
     * @param array $args
     * @return void
     */
    public function execute(string $hookname, array $args): void
    {
        $templateMgr = &$args[1];
        $templateMgr->display(
            $this->plugin->getTemplateResource("submissionWizard.tpl")
        );
    }
}