{**
 * templates/submissionWizard.tpl
 *
 * @copyright (c) 2024+ TIB Hannover
 * @copyright (c) 2024+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Citation Manager
 *}

<div v-if="section.id === 'citations'">

    <link rel="stylesheet" href="{$assetsUrl}/css/backend.css" type="text/css"/>
    <link rel="stylesheet" href="{$assetsUrl}/css/frontend.css" type="text/css"/>

    <div>
        <p><strong>{translate key="plugins.generic.citationManager.wizard.label"}</strong></p>
        <p>{translate key="plugins.generic.citationManager.wizard.description"}</p>
    </div>

</div>
