<?php
/**
 * @file classes/ScheduledTasks/ProcessTask.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ProcessTask
 * @brief Scheduled task for cleaning, splitting, extracting PID's, structuring and enriching citations
 */

namespace APP\plugins\generic\citationManager\classes\ScheduledTasks;

import('lib.pkp.classes.plugins.PluginRegistry');
import('lib.pkp.classes.scheduledTask.ScheduledTask');

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\Handlers\ProcessHandler;
use ScheduledTask;
use ScheduledTaskHelper;
use PluginRegistry;

class ProcessTask extends ScheduledTask
{
    /** @copydoc ScheduledTask::__construct */
    function __construct($args)
    {
        parent::__construct($args);
    }

    /** @copydoc ScheduledTask::executeActions() */
    public function executeActions(): bool
    {
        /** @var CitationManagerPlugin $plugin */
        $plugin = PluginRegistry::getPlugin('generic',  strtolower(CITATION_MANAGER_PLUGIN_NAME));

        if (!$plugin->getEnabled()) {
            $this->addExecutionLogEntry(
                __METHOD__ . '->pluginEnabled=false [' . date('Y-m-d H:i:s') . ']',
                SCHEDULED_TASK_MESSAGE_TYPE_WARNING);
            return false;
        }

        $process = new ProcessHandler();
        $result = $process->batchExecute();

        if (!$result) {
            $this->addExecutionLogEntry(
                __METHOD__ . '->result=false [' . date('Y-m-d H:i:s') . ']',
                SCHEDULED_TASK_MESSAGE_TYPE_ERROR);
            return false;
        }

        $this->addExecutionLogEntry(
            __METHOD__ . '->result=true [' . date('Y-m-d H:i:s') . ']',
            SCHEDULED_TASK_MESSAGE_TYPE_COMPLETED);

        return true;
    }
}
