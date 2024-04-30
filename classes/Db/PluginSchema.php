<?php
/**
 * @file classes/Db/PluginSchema.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PluginSchema
 * @brief Plugin Schema
 */

namespace APP\plugins\generic\citationManager\classes\Db;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataAuthor;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataJournal;
use APP\plugins\generic\citationManager\classes\DataModels\MetadataPublication;
use APP\plugins\generic\citationManager\classes\Helpers\ClassHelper;
use Services;

class PluginSchema
{
    /**
     * This method adds properties to the schema of a publication.
     *
     * @param string $hookName
     * @param array $args
     * @return bool
     */
    public function addToSchemaPublication(string $hookName, array $args): bool
    {
        $schema = &$args[0];

        $schema->properties->{CitationManagerPlugin::CITATIONS_STRUCTURED} = (object)[
            'type' => 'string',
            'multilingual' => false,
            'apiSummary' => true,
            'validation' => ['nullable']
        ];

        foreach(ClassHelper::getClassConstantsAndValuesAsArray(new MetadataPublication()) as $name => $key) {
            $schema->properties->{$key} = (object)[
                'type' => 'string',
                'multilingual' => false,
                'apiSummary' => true,
                'validation' => ['nullable']
            ];
        }

        return false;
    }

    /**
     * This method adds properties to the schema of a journal / context.
     *
     * @param string $hookName
     * @param array $args
     * @return bool
     */
    public function addToSchemaJournal(string $hookName, array $args): bool
    {
        $schema = &$args[0];

        foreach(ClassHelper::getClassConstantsAndValuesAsArray(new MetadataJournal()) as $name => $key) {
            $schema->properties->{$key} = (object)[
                'type' => 'string',
                'multilingual' => false,
                'apiSummary' => true,
                'validation' => ['nullable']
            ];
        }

        return false;
    }

    /**
     * This method adds properties to the schema of an author.
     *
     * @param string $hookName
     * @param array $args
     * @return bool
     */
    public function addToSchemaAuthor(string $hookName, array $args): bool
    {
        $schema = &$args[0];

        foreach(ClassHelper::getClassConstantsAndValuesAsArray(new MetadataAuthor()) as $name => $key) {
            $schema->properties->{$key} = (object)[
                'type' => 'string',
                'multilingual' => false,
                'apiSummary' => true,
                'validation' => ['nullable']
            ];
        }

        return false;
    }

    /**
     * Reload the context so that changes to the context schema can take place.
     *
     * @return void
     */
    public static function reloadJournalSchema(): void
    {
        // import('classes.core.Services');
        Services::get('schema')->get(SCHEMA_CONTEXT, true);
    }
}
