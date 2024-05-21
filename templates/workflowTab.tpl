{**
 * templates/workflowTab.tpl
 *
 * @copyright (c) 2024+ TIB Hannover
 * @copyright (c) 2024+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Citation Manager tab
 *}
<link rel="stylesheet" href="{$assetsUrl}/css/backend.css" type="text/css" />
<link rel="stylesheet" href="{$assetsUrl}/css/frontend.css" type="text/css" />

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
                       :class="(citationManagerApp.metadataJournal.openAlexId)?'':'citationManager-Disabled'"
                       :href="'{$url.openAlex}/' + citationManagerApp.metadataJournal.openAlexId">OpenAlex</a>
                    <a class="pkpButton citationManager-Button" target="_blank"
                       :class="(citationManagerApp.metadataJournal.wikidataId)?'':'citationManager-Disabled'"
                       :href="'{$url.wikidata}/' + citationManagerApp.metadataJournal.wikidataId">Wikidata</a>
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
                           :class="(author.wikidataId)?'':'citationManager-Disabled'"
                           :href="'{$url.wikidata}/' + author.wikidataId">WD</a>
			        </span>
                </td>
            </tr>
            <tr>
                <td><strong>{translate key="common.publication"}</strong></td>
                <td>
                    <a class="pkpButton citationManager-Button" target="_blank"
                       :class="(citationManagerApp.publication.wikidataId)?'':'citationManager-Disabled'"
                       :href="'{$url.wikidata}/' + citationManagerApp.publication.wikidataId">Wikidata</a>
                    <a class="pkpButton citationManager-Button" target="_blank"
                       :class="(citationManagerApp.publication.openCitationsId)?'':'citationManager-Disabled'"
                       :href="'{$url.openCitations}/' + citationManagerApp.publication.openCitationsId">OpenCitations</a>
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

    <div class="content">
        <span v-show="!citationManagerApp.isStructured && !citationManagerApp.panelVisibility.spinner">
            {translate key="plugins.generic.citationManager.citations.info.description"}
        </span>
        <span v-show="citationManagerApp.panelVisibility.spinner" aria-hidden="true" class="pkpSpinner"></span>
        <table v-show="citationManagerApp.isStructured && !citationManagerApp.panelVisibility.spinner">
            <tbody>
            <tr v-for="(citation, i) in citationManagerApp.citations" class="---citationManager-Row">
                <td class="column-nr">{{ i + 1 }}</td>
                <td class="column-parts">
                    <div>
                        <a :href="'{$url.doi}' + '/' + citation.doi"
                           v-show="!citation.editRow" target="_blank">{{ citation.doi }}</a>
                        <input id="doi-{{ i + 1 }}" placeholder="DOI" v-show="citation.editRow"
                               v-model="citation.doi" />
                        <a :href="citation.urn"
                           v-show="!citation.editRow" target="_blank">{{ citation.urn }}</a>
                        <input id="urn-{{ i + 1 }}" placeholder="URN" v-show="citation.editRow"
                               v-model="citation.urn" />
                        <a :href="citation.url"
                           v-show="!citation.editRow" target="_blank">{{ citation.url }}</a>
                        <input id="url-{{ i + 1 }}" placeholder="URL" v-show="citation.editRow"
                               v-model="citation.url" />
                    </div>
                    <div>
                        <span v-for="(author, j) in citation.authors">
                            <span v-show="!citation.editRow"
                                  class="citationManager-Tag">{{ citation.authors[j].givenName }}</span>
                            <input id="givenName-{{ i + 1 }}-{{ j + 1 }}" placeholder="{translate key="user.givenName"}"
                                   v-show="citation.editRow"
                                   v-model="citation.authors[j].givenName" />
                            <span v-show="!citation.editRow"
                                  class="citationManager-Tag">{{ citation.authors[j].familyName }}</span>
                            <input id="familyName-{{ i + 1 }}-{{ j + 1 }}" placeholder="{translate key="user.familyName"}"
                                   v-show="citation.editRow"
                                   v-model="citation.authors[j].familyName" />
                            <input id="orcid-{{ i + 1 }}-{{ j + 1 }}" placeholder="{translate key="user.orcid"}"
                                   v-show="citation.editRow"
                                   v-model="citation.authors[j].orcid" />
                            <a class="pkpButton citationManager-Button" target="_blank"
                               :class="(citation.authors[j].orcid)?'':'citationManager-Disabled'"
                               :href="'{$url.orcid}' + '/' + citation.authors[j].orcid">iD</a>
                            <a class="pkpButton" v-show="citation.editRow"
                               v-on:click="citationManagerApp.removeAuthor(i, j)">
                                <i class="fa fa-trash" aria-hidden="true"></i></a>
                                <br v-show="citation.editRow" />
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
                        <input id="title-{{ i + 1 }}" placeholder="{translate key="common.title"}" v-show="citation.editRow"
                               v-model="citation.title" />

                        <span v-show="!citation.editRow && citation.journalName"
                              class="citationManager-Tag">{{ citation.journalName }}</span>
                        <input id="venue_display_name-{{ i + 1 }}" placeholder="{translate key="context.context"}" v-show="citation.editRow"
                               v-model="citation.journalName" />

                        <span v-show="!citation.editRow && citation.publicationYear"
                              class="citationManager-Tag">{{ citation.publicationYear }}</span>
                        <input id="publicationYear-{{ i + 1 }}" placeholder="{translate key="common.year"}" v-show="citation.editRow"
                               v-model="citation.publicationYear" />

                        <span v-show="!citation.editRow && citation.volume"
                              class="citationManager-Tag">Volume {{ citation.volume }}</span>
                        <input id="volume-{{ i + 1 }}" placeholder="{translate key="issue.volume"}" v-show="citation.editRow"
                               v-model="citation.volume" />

                        <span v-show="!citation.editRow && citation.issue"
                              class="citationManager-Tag">Issue {{ citation.issue }}</span>
                        <input id="issue-{{ i + 1 }}" placeholder="{translate key="issue.issue"}" v-show="citation.editRow"
                               v-model="citation.issue" />

                        <span v-show="!citation.editRow && citation.firstPage"
                              class="citationManager-Tag">Pages {{ citation.firstPage }} - {{ citation.lastPage }}</span>
                        <input id="firstPage-{{ i + 1 }}" placeholder="{translate key="metadata.property.displayName.fpage"}" v-show="citation.editRow"
                               v-model="citation.firstPage" />
                        <input id="lastPage-{{ i + 1 }}" placeholder="{translate key="metadata.property.displayName.lpage"}" v-show="citation.editRow"
                               v-model="citation.lastPage" />
                    </div>
                    <div class="citationRaw">{{ citation.raw }}</div>
                    <div>
                        <a class="pkpButton citationManager-Button" target="_blank"
                           :class="(citation.wikidataId)?'':'citationManager-Disabled'"
                           :href="'{$url.wikidata}/' + citation.wikidataId">Wikidata</a>
                        <a class="pkpButton citationManager-Button" target="_blank"
                           :class="(citation.wikidataId)?'':'citationManager-Disabled'"
                           :href="'{$url.openAlex}/' + citation.openAlexId">OpenAlex</a>
                    </div>
                </td>
                <td class="column-actions">
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

    <div>
        <div class="citationManager-Hide">
            <span>{{ citationManagerApp.workingPublication         = workingPublication }}</span>
            <span>{{ citationManagerApp.workingPublicationStatus   = workingPublication.status }}</span>
            <span>{{ citationManagerApp.submissionId               = workingPublication.submissionId }}</span>
            <span>{{ citationManagerApp.workingPublicationId       = workingPublication.id }}</span>
            <span>{{ components.{CitationManagerPlugin::CITATIONS_STRUCTURED}.fields[0]['value'] = JSON.stringify(citationManagerApp.citationsClean) }}</span>
            <span>{{ components.{CitationManagerPlugin::CITATIONS_STRUCTURED}.action = '{$apiBaseUrl}submissions/' + workingPublication.submissionId + '/publications/' + workingPublication.id }}</span>
        </div>
        <div>
            <span class="citationManager-Hide">{{ citationManagerApp.saveBtn() }}</span>
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
				workingPublication: { /**/},            // workingPublication
				workingPublicationId: {$publicationId}, // workingPublication.id
				workingPublicationStatus: 0,            // workingPublication.status
				panelVisibility: { /**/ spinner: false},
				pendingRequests: new WeakMap()
			};
		},
		computed: {
			isStructured: function() {
				return this.citations.length !== 0;
			},
			isPublished: function() {
				let isPublished = false;
				if (pkp.const.STATUS_PUBLISHED === this.workingPublicationStatus) {
					isPublished = true;
				}
				return isPublished;
			},
			citationsClean: function() {
				let result = JSON.parse(JSON.stringify(this.citations));
				for (let i = 0; i < result.length; i++) {
					if (Object.hasOwn(result[i], 'editRow')) {
						delete result[i]['editRow'];
					}
				}
				return result;
			}
		},
		methods: {
			clear: function() {
				if (confirm('{translate key="plugins.generic.citationManager.clear.question"}') !== true) return;
				this.citations = [];
			},
			process: function() {
				if (confirm('{translate key="plugins.generic.citationManager.process.question"}') !== true) {
					return;
				}

				this.stopPendingRequests();
				this.panelVisibility.spinner = true;

				this.postData('{$apiBaseUrl}{CITATION_MANAGER_PLUGIN_NAME}/process', {
					submissionId: this.submissionId,
					publicationId: this.publicationId,
					citationsRaw: this.citationsRaw
				})
					.then((response) => {
						this.setFromApi(JSON.parse(JSON.stringify(response['publication'])));
						this.panelVisibility.spinner = false;
					});
			},
			deposit: function() {
				if (confirm('{translate key="plugins.generic.citationManager.deposit.question"}') !== true) {
					return;
				}

				this.stopPendingRequests();
				this.panelVisibility.spinner = true;

				this.postData('{$apiBaseUrl}{CITATION_MANAGER_PLUGIN_NAME}/deposit', {
					submissionId: this.submissionId,
					publicationId: this.publicationId,
					citations: JSON.stringify(this.citations)
				})
					.then((response) => {
						this.setFromApi(JSON.parse(JSON.stringify(response['publication'])));
						this.panelVisibility.spinner = false;
					});
			},
			postData: async function(url = '', data = { /**/}) {
				const controller = new AbortController();
				this.pendingRequests.set(this, controller);

				const response = await fetch(url, {
					method: 'POST',
					signal: controller.signal,
					headers: {
						'Content-Type': 'application/json',
						'X-Csrf-Token': pkp.currentUser.csrfToken
					},
					body: JSON.stringify(data)
				});

				return response.json();
			},
			stopPendingRequests: function() {
				const previousController = this.pendingRequests.get(this);
				if (previousController) previousController.abort();
			},
			addAuthor: function(index) {
				if (this.citations[index].authors === null) {
					this.citations[index].authors = [];
				}
				this.citations[index].authors.push(JSON.parse(JSON.stringify(this.authorModel)));
			},
			removeAuthor: function(index, authorIndex) {
				if (confirm('{translate key="plugins.generic.citationManager.author.remove.question"}') !== true) {
					return;
				}
				this.citations[index].authors.splice(authorIndex, 1);
			},
			toggleEdit: function(index) {
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
			saveBtn: function() {
				if (document.querySelector('#citationManager button.pkpButton') !== null) {
					let saveBtn = document.querySelector('#citationManager button.pkpButton');
					saveBtn.disabled = this.isPublished;
				}
			},
			setCitations: function(publication) {
				this.citations = [];
				if (publication["{CitationManagerPlugin::CITATIONS_STRUCTURED}"] !== undefined
					&& publication["{CitationManagerPlugin::CITATIONS_STRUCTURED}"] !== null) {
					let citations = publication["{CitationManagerPlugin::CITATIONS_STRUCTURED}"];
					if (typeof citations === 'string') {
						citations = JSON.parse(citations);
					}
					this.citations = citations;
				}
			},
			setCitationsEditRow: function(citations) {
				let result = [];
				Object.values(citations).forEach(citation => {
					citation = JSON.parse(JSON.stringify(citation));
					citation.editRow = false;
					result.push(citation);
				});
				this.citations = result;
			},
			setAuthors: function(publication) {
				this.authors = [];
				if (publication['authors'] !== undefined) {
					Object.values(publication['authors']).forEach(author => {
						let row = author;
						if (author['_data'] !== undefined) {
							row = author['_data'];
						}
						this.authors.push(row);
					});
				}
			},
			setCitationsRaw: function(publication) {
				this.citationsRaw = '';
				if (publication['citationsRaw'] !== undefined) {
					this.citationsRaw = publication['citationsRaw'];
				}
			},
			setFromApi: function(publication) {
				this.publication = publication;
				this.setCitations(this.publication);
				this.setCitationsEditRow(this.citations);
				this.setAuthors(this.publication);
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

					console.log('citationManager:workingPublicationId: ' + oldValue + ' > ' + newValue);
				}
			}
		},
		created() {
			this.setCitationsEditRow(this.citations);
			this.setAuthors(this.publication);
			this.setCitationsRaw(this.publication);
		}
	});
</script>
