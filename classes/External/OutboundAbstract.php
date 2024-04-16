<?php
/**
 * @file classes/External/OutboundAbstract.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OutboundAbstract
 * @brief Abstract outbound class to be extended by Outbound classes.
 */

namespace APP\plugins\generic\citationManager\classes\External;

use APP\plugins\generic\citationManager\CitationManagerPlugin;
use APP\plugins\generic\citationManager\classes\DataModels\Metadata\MetadataJournal;
use APP\plugins\generic\citationManager\classes\DataModels\Metadata\MetadataPublication;
use Context;
use Issue;
use Publication;
use Submission;

abstract class OutboundAbstract
{
    /** @var ApiAbstract */
    public ApiAbstract $api;

    /** @var CitationManagerPlugin */
    protected CitationManagerPlugin $plugin;

    /** @var Context|null */
    protected ?Context $context = null;

    /** @var Issue|null */
    protected ?Issue $issue = null;

    /** @var Submission|null */
    protected ?Submission $submission = null;

    /** @var Publication|null */
    protected ?Publication $publication = null;

    /** @var MetadataJournal|null */
    protected ?MetadataJournal $metadataJournal = null;

    /** @var MetadataPublication|null */
    protected ?MetadataPublication $metadataPublication = null;

    /** @var array|null */
    protected ?array $citations = [];

    /** @var array|null */
    protected ?array $authors = null;

    /**
     * Constructor
     *
     * @param CitationManagerPlugin $plugin
     * @param Context|null $context
     * @param Issue|null $issue
     * @param Submission|null $submission
     * @param Publication|null $publication
     * @param MetadataJournal|null $metadataJournal
     * @param MetadataPublication|null $metadataPublication
     * @param array|null $authors
     * @param array|null $citations
     */
    public function __construct(CitationManagerPlugin &$plugin,
                                ?Context              $context,
                                ?Issue                $issue,
                                ?Submission           $submission,
                                ?Publication          $publication,
                                ?MetadataJournal      $metadataJournal,
                                ?MetadataPublication  $metadataPublication,
                                ?array                $authors,
                                ?array                $citations)
    {
        $this->plugin = &$plugin;
        $this->context = $context;
        $this->issue = $issue;
        $this->submission = $submission;
        $this->publication = $publication;
        $this->metadataJournal = $metadataJournal;
        $this->metadataPublication = $metadataPublication;
        $this->authors = $authors;
        $this->citations = $citations;
    }

    /**
     * Executes deposits to external services
     *
     * @return bool
     */
    public function execute(): bool
    {
        return true;
    }

    // region getters
    public function getMetadataJournal(): MetadataJournal
    {
        return $this->metadataJournal;
    }
    public function getMetadataPublication(): MetadataPublication
    {
        return $this->metadataPublication;
    }
    public function getCitations(): array
    {
        return $this->citations;
    }
    public function getAuthors(): array
    {
        return $this->authors;
    }
    // endregion
}
