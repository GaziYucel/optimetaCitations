<link rel="stylesheet" href="{$assetsUrl}/css/backend.css" type="text/css"/>
<link rel="stylesheet" href="{$assetsUrl}/css/frontend.css" type="text/css"/>

<tab v-if="supportsReferences" id="citationManager"
     label="{translate key="plugins.generic.citationManager.workflow.label"}">

    <div class="header">
        <div>
            {translate key="plugins.generic.citationManager.workflow.description"}
        </div>
        <table>
            <tr>
                <td><strong>{translate key="context.context"}</strong></td>
                <td colspan="2">
                    <a class="pkpButton citationManager-Button" target="_blank"
                       :class="(citationManagerApp.metadataJournal.openalex_id)?'':'citationManager-Disabled'"
                       :href="'{$url.openAlex}/' + citationManagerApp.metadataJournal.openalex_id">OpenAlex</a>
                    <a class="pkpButton citationManager-Button" target="_blank"
                       :class="(citationManagerApp.metadataJournal.wikidata_id)?'':'citationManager-Disabled'"
                       :href="'{$url.wikidata}/' + citationManagerApp.metadataJournal.wikidata_id">Wikidata</a>
                </td>
            </tr>
            <tr>
                <td><strong>{translate key="article.authors"}</strong></td>
                <td colspan="2">
                    <span v-for="(author, i) in citationManagerApp.authors" style="margin-right: 5px;">
                        <span class="citationManager-Tag">
                            {{ author.givenName[workingPublication.locale] }} {{ author.familyName[workingPublication.locale] }}
                        </span>
                        <a class="pkpButton citationManager-Button" target="_blank"
                           :class="(author.orcid)?'':'citationManager-Disabled'"
                           :href="author.orcid">iD</a>
                        <a class="pkpButton citationManager-Button" target="_blank"
                           :class="(author.wikidata_id)?'':'citationManager-Disabled'"
                           :href="'{$url.wikidata}/' + author.wikidata_id">WD</a>
			        </span>
                </td>
            </tr>
            <tr>
                <td><strong>{translate key="common.publication"}</strong></td>
                <td>
                    <a class="pkpButton citationManager-Button" target="_blank"
                       :class="(citationManagerApp.publication.wikidata_id)?'':'citationManager-Disabled'"
                       :href="'{$url.wikidata}/' + citationManagerApp.publication.wikidata_id">Wikidata</a>
                    <a class="pkpButton citationManager-Button" target="_blank"
                       :class="(citationManagerApp.publication.opencitations_id)?'':'citationManager-Disabled'"
                       :href="'{$url.openCitations}/' + citationManagerApp.publication.opencitations_id">OpenCitations</a>
                </td>
                <td class="citationManager-AlignRight">
                    <a @click="citationManagerApp.deposit()" id="buttonDeposit" class="pkpButton"
                       :class="(citationManagerApp.isStructured && citationManagerApp.isPublished)?'':'citationManager-Disabled'">
                        {translate key="plugins.generic.citationManager.deposit.button"}</a>
                    <a @click="citationManagerApp.clear()" id="buttonClear" class="pkpButton"
                       :class="(citationManagerApp.isStructured && !citationManagerApp.isPublished)?'':'citationManager-Disabled'">
                        {translate key="plugins.generic.citationManager.clear.button"}</a>
                    <a @click="citationManagerApp.process()" id="buttonProcess" class="pkpButton"
                       :class="(!citationManagerApp.isPublished)?'':'citationManager-Disabled'">
                        {translate key="plugins.generic.citationManager.process.button"}</a>
                </td>
            </tr>
        </table>
    </div>

    <div class="citationManager-ScrollableDiv">
        <div id="citationManager-ScrollableDivLoading"
             class="citationManager-ScrollableDivLoading citationManager-Hide">
            <span aria-hidden="true" class="pkpSpinner"></span>
        </div>
        <div id="citationManager-ScrollableDivEmpty" class="citationManager-ScrollableDivEmpty"
             v-show="!citationManagerApp.isStructured">
            {translate key="plugins.generic.citationManager.citations.empty.description"}
        </div>
        <div id="citationManager-ScrollableDivValue" class="citationManager-ScrollableDivValue">
            <table v-show="citationManagerApp.isStructured">
                <colgroup>
                    <col class="grid-column column-nr" style="width: 2%;">
                    <col class="grid-column column-parts" style="">
                    <col class="grid-column column-action" style="width: 6%;">
                </colgroup>
                <tbody>
                <tr v-for="(citation, i) in citationManagerApp.citations" class="citationManager-Row">
                    <td class="citationManager-ScrollableDiv-nr">{{ i + 1 }}</td>
                    <td class="citationManager-ScrollableDiv-parts">
                        <div>
                            <a :href="'{$url.doi}' + '/' + citation.doi"
                               v-show="!citation.editRow" target="_blank">{{ citation.doi }}</a>
                            <input id="doi-{{ i + 1 }}" placeholder="DOI" v-show="citation.editRow"
                                   v-model="citation.doi" class="citationManager-Input"/>
                            <a :href="citation.urn"
                               v-show="!citation.editRow" target="_blank">{{ citation.urn }}</a>
                            <input id="urn-{{ i + 1 }}" placeholder="URN" v-show="citation.editRow"
                                   v-model="citation.urn" class="citationManager-Input"/>
                            <a :href="citation.url"
                               v-show="!citation.editRow" target="_blank">{{ citation.url }}</a>
                            <input id="url-{{ i + 1 }}" placeholder="URL" v-show="citation.editRow"
                                   v-model="citation.url" class="citationManager-Input"/>
                        </div>
                        <div>
                            <div>
                                <span v-for="(author, j) in citation.authors">
                                    <span v-show="!citation.editRow"
                                          class="citationManager-Tag">{{ citation.authors[j].given_name }}</span>
                                    <input id="given_name-{{ i + 1 }}-{{ j + 1 }}" placeholder="Given name"
                                           v-show="citation.editRow"
                                           v-model="citation.authors[j].given_name"
                                           class="citationManager-Input"/>
                                    <span v-show="!citation.editRow"
                                          class="citationManager-Tag">{{ citation.authors[j].family_name }}</span>
                                    <input id="family_name-{{ i + 1 }}-{{ j + 1 }}" placeholder="Family name"
                                           v-show="citation.editRow"
                                           v-model="citation.authors[j].family_name"
                                           class="citationManager-Input"/>
                                    <input id="orcid-{{ i + 1 }}-{{ j + 1 }}" placeholder="Orcid"
                                           v-show="citation.editRow"
                                           v-model="citation.authors[j].orcid_id"
                                           class="citationManager-Input"/>
                                    <a class="pkpButton citationManager-Button" target="_blank"
                                       :class="(citation.authors[j].orcid_id)?'':'citationManager-Disabled'"
                                       :href="'{$url.orcid}' + '/' + citation.authors[j].orcid_id">iD</a>
                                    <a class="pkpButton" v-show="citation.editRow"
                                       v-on:click="citationManagerApp.removeAuthor(i, j)">
                                        <i class="fa fa-trash" aria-hidden="true"></i></a>
                                        <br v-show="citation.editRow"/>
                                </span>
                                <a class="pkpButton" v-show="citation.editRow"
                                   v-on:click="citationManagerApp.addAuthor(i)">
                                    {translate key="plugins.generic.citationManager.author.add.button"}
                                </a>
                            </div>
                            <div>
                                <span v-show="!citation.editRow && !citation.isProcessed"
                                      class="citationManager-Tag">No information found</span>

                                <span v-show="!citation.editRow && citation.title"
                                      class="citationManager-Tag">{{ citation.title }}</span>
                                <input id="title-{{ i + 1 }}" placeholder="Title" v-show="citation.editRow"
                                       class="citationManager-Input"
                                       v-model="citation.title"/>

                                <span v-show="!citation.editRow && citation.journal_name"
                                      class="citationManager-Tag">{{ citation.journal_name }}</span>
                                <input id="venue_display_name-{{ i + 1 }}" placeholder="Venue" v-show="citation.editRow"
                                       class="citationManager-Input"
                                       v-model="citation.journal_name"/>

                                <span v-show="!citation.editRow && citation.publication_year"
                                      class="citationManager-Tag">{{ citation.publication_year }}</span>
                                <input id="publication_year-{{ i + 1 }}" placeholder="Year" v-show="citation.editRow"
                                       class="citationManager-Input"
                                       v-model="citation.publication_year"/>

                                <span v-show="!citation.editRow && citation.volume"
                                      class="citationManager-Tag">Volume {{ citation.volume }}</span>
                                <input id="volume-{{ i + 1 }}" placeholder="Volume" v-show="citation.editRow"
                                       class="citationManager-Input"
                                       v-model="citation.volume"/>

                                <span v-show="!citation.editRow && citation.issue"
                                      class="citationManager-Tag">Issue {{ citation.issue }}</span>
                                <input id="issue-{{ i + 1 }}" placeholder="Issue" v-show="citation.editRow"
                                       class="citationManager-Input" v-model="citation.issue"/>

                                <span v-show="!citation.editRow && citation.first_page"
                                      class="citationManager-Tag">Pages {{ citation.first_page }} - {{ citation.last_page }}</span>
                                <input id="first_page-{{ i + 1 }}" placeholder="First page" v-show="citation.editRow"
                                       class="citationManager-Input"
                                       v-model="citation.first_page"/>
                                <input id="last_page-{{ i + 1 }}" placeholder="Last page" v-show="citation.editRow"
                                       class="citationManager-Input"
                                       v-model="citation.last_page"/>
                            </div>
                        </div>
                        <div class="citationManager-RawText">{{ citation.raw }}</div>
                        <div>
                            <a class="pkpButton citationManager-Button" target="_blank"
                               :class="(citation.wikidata_id)?'':'citationManager-Disabled'"
                               :href="'{$url.wikidata}/' + citation.wikidata_id">Wikidata</a>
                            <a class="pkpButton citationManager-Button" target="_blank"
                               :class="(citation.wikidata_id)?'':'citationManager-Disabled'"
                               :href="'{$url.openAlex}/' + citation.openalex_id">OpenAlex</a>
                        </div>
                    </td>
                    <td class="citationManager-ScrollableDiv-actions">
                        <a v-show="!citation.editRow" @click="citationManagerApp.toggleEdit(i)" class="pkpButton"
                           :class="(!citationManagerApp.isPublished)?'':'citationManager-Disabled'">
                            <i class="fa fa-pencil" aria-hidden="true"></i></a>
                        <a v-show="citation.editRow" @click="citationManagerApp.toggleEdit(i)" class="pkpButton">
                            <i class="fa fa-check" aria-hidden="true"></i></a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="citationManager-Hide">
            <span>{{ citationManagerApp.workingPublication         = workingPublication }}</span>
            <span>{{ citationManagerApp.workingPublicationStatus   = workingPublication.status }}</span>
            <span>{{ citationManagerApp.submissionId               = workingPublication.submissionId }}</span>
            <span>{{ citationManagerApp.workingPublicationId       = workingPublication.id }}</span>
            <span>{{ citationManagerApp.formCitations = components.{CitationManagerPlugin::CITATIONS_STRUCTURED}.fields[0]['value'] }}</span> <!-- //todo: citationsStructured gets emptied -->

            <span>{{ citationManagerApp.isUserInteracting ? components.{CitationManagerPlugin::CITATIONS_STRUCTURED}.fields[0]['value'] = JSON.stringify(citationManagerApp.citationsClean) : '' }}</span>
            <span>{{ components.{CitationManagerPlugin::CITATIONS_STRUCTURED}.action = '{$apiBaseUrl}submissions/' + workingPublication.submissionId + '/publications/' + workingPublication.id }}</span>
        </div>
        <div>
            <pkp-form v-bind="components.{CitationManagerPlugin::CITATIONS_STRUCTURED}" @set="set"></pkp-form>
        </div>
    </div>
</tab>

<script>
    let citationManagerApp = new pkp.Vue({
        data() {
            return {
                publication: {$publication},
                publicationId: {$publicationId},
                citations: {$citationStructured},
                authors: [],
                citationsRaw: '',
                metadataJournal: {$metadataJournal},
                authorModel: {$authorModel},
                submissionId: 0,                        // workingPublication.submissionId
                workingPublication: { /* */},           // workingPublication
                workingPublicationId: {$publicationId}, // workingPublication.id
                workingPublicationStatus: 0,            // workingPublication.status
                formCitations: [], //todo: citationsStructured gets emptied
                isUserInteracting: 0, //todo: citationsStructured gets emptied
            }
        },
        computed: {
            isStructured: function () {
                return this.citations.length !== 0;
            },
            isPublished: function () {
                let isPublished = false;

                if (pkp.const.STATUS_PUBLISHED === this.workingPublicationStatus) {
                    isPublished = true;
                }

                if (document.querySelector('#citationManager button.pkpButton') !== null) {
                    let saveBtn = document.querySelector('#citationManager button.pkpButton');
                    saveBtn.disabled = isPublished;
                }

                return isPublished;
            },
            citationsClean: function () {
                let result = JSON.parse(JSON.stringify(this.citations));
                for (let i = 0; i < result.length; i++) {
                    if (Object.hasOwn(result[i], 'editRow')) {
                        delete result[i]['editRow'];
                    }
                }
                return result;
            },
        },
        methods: {
            clear: function () {
                if (confirm('{translate key="plugins.generic.citationManager.clear.question"}') !== true) return;
                this.citations = [];
            },
            process: function () {
                if (confirm('{translate key="plugins.generic.citationManager.process.question"}') !== true) {
                    return;
                }

                this.toggleLoading();

                let self = this;
                $.ajax({
                    url: '{$apiBaseUrl}{CITATION_MANAGER_PLUGIN_NAME}/process',
                    method: 'POST',
                    data: {
                        submissionId: self.submissionId,
                        publicationId: self.publicationId,
                        citationsRaw: self.citationsRaw
                    },
                    headers: {
                        'X-Csrf-Token': pkp.currentUser.csrfToken
                    },
                    success(response) {
                        self.publication = JSON.parse(JSON.stringify(response['publication']));
                        self.setCitations(self.publication);
                        self.setCitationsEditRow(self.citations);
                        self.setAuthors(self.publication);
                    },
                    complete() {
                        self.toggleLoading();
                    }
                });

            },
            deposit: function () {
                if (confirm('{translate key="plugins.generic.citationManager.deposit.question"}') !== true) {
                    return;
                }

                this.toggleLoading();

                let self = this;
                $.ajax({
                    url: '{$apiBaseUrl}{CITATION_MANAGER_PLUGIN_NAME}/deposit',
                    method: 'POST',
                    data: {
                        submissionId: self.submissionId,
                        publicationId: self.publicationId,
                        citations: JSON.stringify(self.citations),
                    },
                    headers: {
                        'X-Csrf-Token': pkp.currentUser.csrfToken
                    },
                    success(response) {
                        self.publication = JSON.parse(JSON.stringify(response['publication']));
                        self.setCitations(self.publication);
                        self.setCitationsEditRow(self.citations);
                        self.setAuthors(self.publication);
                    },
                    complete() {
                        self.toggleLoading();
                    }
                });
            },
            addAuthor: function (index) {
                if (this.citations[index].authors === null) {
                    this.citations[index].authors = [];
                }
                this.citations[index].authors.push(JSON.parse(JSON.stringify(this.authorModel)));
            },
            removeAuthor: function (index, authorIndex) {
                if (confirm('{translate key="plugins.generic.citationManager.author.remove.question"}') !== true) {
                    return;
                }
                this.citations[index].authors.splice(authorIndex, 1);
            },
            toggleEdit: function (index) {
                this.citations[index].editRow = !this.citations[index].editRow;
                if (this.citations[index].authors !== null) {
                    for (let i = 0; i < this.citations[index].authors.length; i++) {
                        let rowIsNull = true;
                        for (let key in this.citations[index].authors[i]) {
                            if (this.citations[index].authors[i][key] !== null) {
                                rowIsNull = false;
                            }
                        }
                        if (rowIsNull === true) {
                            this.citations[index].authors.splice(i);
                        }
                    }
                }
            },
            toggleLoading: function () {
                let cssClass = 'citationManager-Hide';
                document.getElementById('citationManager-ScrollableDivEmpty').classList.toggle(cssClass);
                document.getElementById('citationManager-ScrollableDivValue').classList.toggle(cssClass);
                document.getElementById('citationManager-ScrollableDivLoading').classList.toggle(cssClass);
            },
            setCitations: function (publication) {
                this.citations = [];
                if (publication["{CitationManagerPlugin::CITATIONS_STRUCTURED}"] !== undefined) {
                    let citations = publication["{CitationManagerPlugin::CITATIONS_STRUCTURED}"];
                    if (typeof citations === "string") {
                        citations = JSON.parse(citations);
                    }
                    this.citations = citations;
                }
            },
            setCitationsEditRow: function (citations) {
                let result = [];
                Object.values(citations).forEach(citation => {
                    citation = JSON.parse(JSON.stringify(citation));
                    citation.editRow = false;
                    result.push(citation);
                });
                this.citations = result;
            },
            setAuthors: function (publication) {
                this.authors = [];
                if (publication['authors'] !== undefined) {
                    Object.values(publication["authors"]).forEach(author => {
                        let row = author;
                        if (author['_data'] !== undefined) {
                            row = author['_data'];
                        }
                        this.authors.push(row);
                    });
                }
            },
            setCitationsRaw: function (publication) {
                this.citationsRaw = '';
                if (publication['citationsRaw'] !== undefined) {
                    this.citationsRaw = publication['citationsRaw'];
                }
            }
        },
        watch: {
            workingPublicationId(newValue, oldValue) {
                if (newValue !== oldValue) {
                    this.publicationId = this.workingPublicationId;
                    this.publication = this.workingPublication;
                    this.setCitations(this.publication);
                    this.setCitationsEditRow(this.citations);
                    this.setAuthors(this.publication);
                    this.setCitationsRaw(this.publication);

                    console.log(oldValue + ' > ' + newValue);
                }
            }
        },
        created() {
            this.setCitationsEditRow(this.citations);
            this.setAuthors(this.publication);
            this.setCitationsRaw(this.publication);
            this.isUserInteracting = true; //todo: citationsStructured gets emptied
        }
    });
</script>
