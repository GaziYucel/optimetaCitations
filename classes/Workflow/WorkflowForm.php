<?php
/**
 * @file classes/Components/Forms/WorkflowForm.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class WorkflowForm
 * @brief A preset form for setting a publication's parsed citations
 */

namespace APP\plugins\generic\citationManager\classes\Workflow;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\Db\PluginDAO;
use Application;
use PKP\components\forms\FieldText;
use PKP\components\forms\FormComponent;

class WorkflowForm extends FormComponent
{
    /** @copydoc FormComponent::__construct */
    public function __construct(string $id, string $method, string $action, array $locales)
    {
        parent::__construct($id, $method, $action, $locales);

        $publicationId = array_reverse(explode('/', $action))[0];
        $pluginDao = new PluginDAO();
        $publication = $pluginDao->getPublication($publicationId);

        $this->addField(new FieldText(
            CitationManagerPlugin::CITATIONS_STRUCTURED, [
            'label' => '',
            'description' => '',
            'isMultilingual' => false,
            'value' => $pluginDao->getCitations($publication)
        ]));
    }
}
