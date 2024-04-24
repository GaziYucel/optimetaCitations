/**
 * @file cypress/e2e/integration/22-configuration-plugin-citations.cy.js
 *
 * @copyright (c) 2022 Gazi Yücel
 * @copyright (c) 2022 Daniel Nüst
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief
 */

describe('Citation Manager Plugin Configuration', function () {

    it('Disable Plugin', function () {
        cy.login('admin');
        cy.get('a:contains("admin")').click();
        cy.get('a:contains("Dashboard")').click();
        cy.wait(2000);

        // navigate to plugins
        cy.get('nav[class="app__nav"] a:contains("Website")').click();
        cy.get('button[id="plugins-button"]').click();
        cy.wait(2000);

        // disable plugin if enabled
        cy.get('input[id^="select-cell-CitationManagerPlugin-enabled"]')
            .then($btn => {
                if ($btn.attr('checked') === 'checked') {
                    cy.get('input[id^="select-cell-CitationManagerPlugin-enabled"]').uncheck();
                    cy.get('div[class*="pkp_modal_panel"] button[class*="pkpModalConfirmButton"]').click();
                    cy.wait(1000);

                    // check if disabled
                    cy.get('div:contains(\'The plugin "Citation Manager Plugin" has been disabled.\')');
                }
            });
    });

    it('Enable Plugin', function () {
        cy.login('admin');
        cy.get('a:contains("admin")').click();
        cy.get('a:contains("Dashboard")').click();
        cy.wait(2000);

        // navigate to plugins
        cy.get('nav[class="app__nav"] a:contains("Website")').click();
        cy.get('button[id="plugins-button"]').click();
        cy.wait(2000);

        // Find and enable the plugin
        cy.get('input[id^="select-cell-CitationManagerPlugin-enabled"]').check();
        cy.wait(1000);

        // check if enabled
        cy.get('div:contains(\'The plugin "Citation Manager Plugin" has been enabled.\')');
    });

    it('Configure Plugin', function () {
        cy.login('admin');
        cy.get('a:contains("admin")').click();
        cy.get('a:contains("Dashboard")').click();
        cy.wait(2000);

        cy.get('nav[class="app__nav"] a:contains("Website")').click();
        cy.get('button[id="plugins-button"]').click();
        cy.wait(2000);

        // Open the settings form
        cy.get('tr[id="component-grid-settings-plugins-settingsplugingrid-category-generic-row-CitationManagerPlugin"] a[class="show_extras"]').click();
        cy.get('a[id^="component-grid-settings-plugins-settingsplugingrid-category-generic-row-CitationManagerPlugin-settings-button"]').click();

        // Fill out settings form

        // Open Citations
        cy.get('form[id="citationManagerSettings"] input[name^="CitationManager_OpenCitations_Owner"]')
            .clear().type(Cypress.env("OPEN_CITATIONS_OWNER"));
        cy.get('form[id="citationManagerSettings"] input[name^="CitationManager_OpenCitations_Repository"]')
            .clear().type(Cypress.env("OPEN_CITATIONS_REPOSITORY"));
        cy.get('form[id="citationManagerSettings"] input[name^="CitationManager_OpenCitations_Token"]')
            .clear().type(Cypress.env("GITHUB_TOKEN"));

        // Wikidata
        cy.get('form[id="citationManagerSettings"] input[name^="CitationManager_Wikidata_Username"]')
            .clear().type('wikidata-username');
        cy.get('form[id="citationManagerSettings"] input[name="CitationManager_Wikidata_Password"]')
            .clear().type('wikidata-password');

        // submit settings form
        cy.get('form[id="citationManagerSettings"] button[id^="submitFormButton"]').click();
    });

    it('Has the citations management UI in the third submissions step', function () {
        cy.login('admin');
        cy.get('a:contains("admin")').click();
        cy.get('a:contains("Dashboard")').click();
        cy.wait(2000);

        cy.get('a:contains("New Submission"):visible').click();
        cy.wait(2000);

        cy.get('input[id^="checklist-"]').click({multiple: true});
        cy.get('input[id="privacyConsent"]').click();

        cy.get('#submitStep1Form').within(() => {
            cy.get('button.submitFormButton').click();
        });
        cy.wait(2000);

        cy.get('#submitStep2Form').within(() => {
            cy.get('button.submitFormButton').click();
        });
        cy.wait(2000);

        // checking contents
        cy.get('#citationManager').should('exist');
    });

});
