<?php
/**
 * @file classes/Settings/Actions.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Actions
 * @brief Actions on the settings page
 */

namespace APP\plugins\generic\citationManager\classes\Settings;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use AjaxAction;
use LinkAction;
use AjaxModal;

class Actions
{
    /** @var CitationManagerPlugin */
    public CitationManagerPlugin $plugin;

    /** @param CitationManagerPlugin $plugin */
    public function __construct(CitationManagerPlugin &$plugin)
    {
        $this->plugin = &$plugin;
    }

    /** @copydoc Plugin::getActions() */
    public function execute($request, $actionArgs, $parentActions): array
    {
        $router = $request->getRouter();

        $linkAction[] = new LinkAction(
            'settings',
            new AjaxModal(
                $router->url(
                    $request, null, null, 'manage', null,
                    [
                        'verb' => 'settings',
                        'plugin' => $this->plugin->getName(),
                        'category' => 'generic'
                    ]
                ),
                $this->plugin->getDisplayName()),
            __('manager.plugins.settings'),
            null);

        $linkAction[] = new LinkAction(
            'settings_status',
            new AjaxModal(
                $router->url(
                    $request, null, null, 'manage', null,
                    [
                        'verb' => 'settings_status',
                        'plugin' => $this->plugin->getName(),
                        'category' => 'generic'
                    ]
                ),
                $this->plugin->getDisplayName()
            ),
            __('common.status'),
            null);

        $linkAction[] = new LinkAction(
            'batch_process',
            new AjaxAction(
                $router->url(
                    $request, null, null, 'manage', null,
                    [
                        'verb' => 'batch_process',
                        'plugin' => $this->plugin->getName(),
                        'category' => 'generic'
                    ]
                )
            ),
            __('plugins.generic.citationManager.settings.process.button'),
            null);

        $linkAction[] = new LinkAction(
            'batch_deposit',
            new AjaxAction(
                $router->url(
                    $request, null, null, 'manage', null,
                    [
                        'verb' => 'batch_deposit',
                        'plugin' => $this->plugin->getName(),
                        'category' => 'generic'
                    ]
                )
            ),
            __('plugins.generic.citationManager.settings.deposit.button'),
            null);

        array_unshift($parentActions, ...$linkAction);

        return $parentActions;
    }
}
