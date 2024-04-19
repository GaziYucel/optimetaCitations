<?php

/**
 * @file classes/Settings/StatusForm
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatusForm
 * @brief Form for journal managers to set up the plugin
 */

namespace APP\plugins\generic\citationManager\classes\Settings;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use Form;
use FormValidatorCSRF;
use FormValidatorPost;

class StatusForm extends Form
{
    /** @var CitationManagerPlugin $plugin */
    public CitationManagerPlugin $plugin;

    /** @param CitationManagerPlugin $plugin */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;

        // Always add POST and CSRF validation to secure your form.
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));

        parent::__construct($plugin->getTemplateResource('settingsStatusForm.tpl'));
    }

    /**
     * Initialize form data.
     */
    public function initData()
    {

    }

    /**
     * Fetch the form.
     *
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false): ?string
    {
        return parent::fetch($request, $template, $display);
    }
}

