/**
 * @file cypress/e2e/integration/21-configuration-plugin-doi.cy.js
 *
 * @copyright (c) 2022 Gazi Yücel
 * @copyright (c) 2022 Daniel Nüst
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief
 */

describe('Enable and configure DOI Plugin', function () {

    it('Disable DOI Plugin', function () {
        cy.login('admin');
        cy.get('a:contains("admin")').click();
        cy.get('a:contains("Dashboard")').click();
        cy.wait(2000);

        // navigate to plugins
        cy.get('nav[class="app__nav"] a:contains("Website")').click();
        cy.get('button[id="plugins-button"]').click();
        cy.wait(2000);

        // disable plugin if enabled
        cy.get('input[id^="select-cell-doipubidplugin-enabled"]')
            .then($btn => {
                if ($btn.attr('checked') === 'checked') {
                    cy.get('input[id^="select-cell-doipubidplugin-enabled"]').uncheck();
                    cy.get('div[class*="pkp_modal_panel"] button[class*="pkpModalConfirmButton"]').click();
                    cy.wait(1000);

                    // check if disabled
                    cy.get('div:contains(\'The plugin "DOI" has been disabled.\')');
                }
            });
    });

    it('Enable DOI Plugin', function () {
        cy.login('admin');
        cy.get('a:contains("admin")').click();
        cy.get('a:contains("Dashboard")').click();
        cy.wait(2000);

        // navigate to plugins
        cy.get('nav[class="app__nav"] a:contains("Website")').click();
        cy.get('button[id="plugins-button"]').click();
        cy.wait(2000);

        // Find and enable the plugin
        cy.get('input[id^="select-cell-doipubidplugin-enabled"]').check();
        cy.wait(1000);

        // check if enabled
        cy.get('div:contains(\'The plugin "DOI" has been enabled.\')');
    });

    it('Configure DOI Plugin', function () {
        cy.login('admin');
        cy.get('a:contains("admin")').click();
        cy.get('a:contains("Dashboard")').click();
        cy.wait(2000);

        // navigate to plugins
        cy.get('nav[class="app__nav"] a:contains("Website")').click();
        cy.get('button[id="plugins-button"]').click();
        cy.wait(2000);

        // Open the settings form
        cy.get('tr[id="component-grid-settings-plugins-settingsplugingrid-category-pubIds-row-doipubidplugin"] a[class="show_extras"]').click();
        cy.get('a[id^="component-grid-settings-plugins-settingsplugingrid-category-pubIds-row-doipubidplugin-settings-button"]').click();

        // Fill out settings form
        cy.get('form[id="doiSettingsForm"] input[name^="enableIssueDoi"]').check();
        cy.get('form[id="doiSettingsForm"] input[name^="enablePublicationDoi"]').check();
        cy.get('form[id="doiSettingsForm"] input[name^="enableRepresentationDoi"]').check();
        cy.get('form[id="doiSettingsForm"] input[name^="doiPrefix"]').clear().type('10.1234');
        cy.get('form[id="doiSettingsForm"] input[id^="doiSuffixDefault"]').click();

        // submit settings form
        cy.get('form[id="doiSettingsForm"] button[id^="submitFormButton"]').click();
    });

});
