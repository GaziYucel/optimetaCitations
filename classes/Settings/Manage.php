<?php
/**
 * @file classes/Settings/Manage.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Manage
 * @brief Manage settings page
 */

namespace APP\plugins\generic\citationManager\classes\Settings;

use Application;
use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\Handlers\OutboundHandler;
use APP\plugins\generic\citationManager\classes\Handlers\InboundHandler;
use JSONMessage;
use Notification;
use NotificationManager;
use DAO;

class Manage
{
    /** @var CitationManagerPlugin */
    public CitationManagerPlugin $plugin;

    /** @param CitationManagerPlugin $plugin */
    public function __construct(CitationManagerPlugin &$plugin)
    {
        $this->plugin = &$plugin;
    }

    /** @copydoc Plugin::manage() */
    public function execute($args, $request): JSONMessage
    {
        $context = $request->getContext();
        switch ($request->getUserVar('verb')) {
            case 'settings':
                $form = new ConfigurationForm($this->plugin);
                // Fetch the form the first time it loads, before the user has tried to save it
                if (!$request->getUserVar('save')) {
                    $form->initData();
                    return new JSONMessage(true, $form->fetch($request));
                }
                // Validate and save the form data
                $form->readInputData();
                if ($form->validate()) $form->execute();
                return new JSONMessage(true);
            case 'settings_status':
                $form = new StatusForm($this->plugin);
                $form->initData();
                return new JSONMessage(true, $form->fetch($request));
            case 'batch_process':
                $process = new ProcessHandler();
                $process->batchExecute();
                $notificationManager = new NotificationManager();
                $notificationManager->createTrivialNotification(
                    Application::get()->getRequest()->getUser()->getId(),
                    NOTIFICATION_TYPE_SUCCESS,
                    array('contents' => __('plugins.generic.citationManager.settings.process.notification')));
                return DAO::getDataChangedEvent();
            case 'batch_deposit':
                $deposit = new DepositHandler();
                $deposit->batchExecute();
                $notificationManager = new NotificationManager();
                $notificationManager->createTrivialNotification(
                    Application::get()->getRequest()->getUser()->getId(),
                    NOTIFICATION_TYPE_SUCCESS,
                    array('contents' => __('plugins.generic.citationManager.settings.deposit.notification')));
                return DAO::getDataChangedEvent();
        }

        return new JSONMessage(false);
    }
}

