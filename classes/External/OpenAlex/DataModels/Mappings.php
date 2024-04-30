<?php
/**
 * @file External/OpenAlex/DataModels/Mappings.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Mappings
 * @brief Mapping of internal data models and external
 *
 * @see https://api.openalex.org/works/W2369996029
 * @see https://docs.openalex.org/api-entities/works
 * @see https://docs.openalex.org/api-entities/authors
 * @see https://docs.openalex.org/api-entities/sources
 */

namespace APP\plugins\generic\citationManager\classes\External\OpenAlex\DataModels;

final class Mappings
{
    /**
     * Works are scholarly documents like journal articles, books, datasets, and theses.
     *
     * @see https://docs.openalex.org/api-entities/works
     * @return array [ internal => openalex, ... ]
     */
    public static function getWork(): array
    {
        return [
            'openAlexId' => 'id',
            'title' => 'title',
            'publicationYear' => 'publication_year',
            'publicationDate' => 'publication_date',
            'type' => 'type_crossref',
            'volume' => ['biblio', 'volume'],
            'issue' => ['biblio', 'issue'],
            'firstPage' => ['biblio', 'first_page'],
            'lastPage' => ['biblio', 'last_page'],
            'journalName' => ['locations', 0,'source', 'display_name'],
            'journalIssnL' => ['locations', 0,'source', 'issn_l'],
            'journalPublisher' => ['locations', 0,'source', 'host_organization_name'],
            'authors' => null // [ AuthorModel, ... ]
        ];
    }

    /**
     * Authors are people who create works.
     *
     * @see https://docs.openalex.org/api-entities/authors
     * @return array [ internal => openalex, ... ]
     */
    public static function getAuthor(): array
    {
        return [
            'orcid' => ['author', 'orcid'],
            'displayName' => ['author', 'display_name'],
            'givenName' => ['author', 'display_name'],
            'familyName' => ['author', 'display_name'],
            'openAlexId' => ['author', 'id']
        ];
    }
}
