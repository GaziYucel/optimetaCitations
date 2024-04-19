<?php
/**
 * @file classes/Workflow/WorkflowSave.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WorkflowSave
 * @brief Workflow WorkflowSave
 */

namespace APP\plugins\generic\citationManager\classes\Workflow;

use APP\plugins\generic\citationManager\CitationManagerPlugin;

class WorkflowSave
{
    /** @var CitationManagerPlugin */
    public CitationManagerPlugin $plugin;

    /** @param CitationManagerPlugin $plugin */
    public function __construct(CitationManagerPlugin &$plugin)
    {
        $this->plugin = &$plugin;
    }

    /**
     * Process data from post/put
     *
     * @param string $hookName
     * @param array $args [ Publication, parameters/publication, Request ]
     */
    public function execute(string $hookName, array $args): void
    {
        $publication = $args[0];
        $params = $args[2];
        $request = $this->plugin->getRequest();

        // citationsStructured

        // submissionWizard
        $citationsStructured = $request->getuserVar(CitationManagerPlugin::CITATIONS_STRUCTURED);

        // publicationTab
        if (array_key_exists(CitationManagerPlugin::CITATIONS_STRUCTURED, $params)) {
            if (!empty($params[CitationManagerPlugin::CITATIONS_STRUCTURED])) {
                $citationsStructured = $params[CitationManagerPlugin::CITATIONS_STRUCTURED];
            }
        }
        $publication->setData(CitationManagerPlugin::CITATIONS_STRUCTURED, $citationsStructured);
    }
}
