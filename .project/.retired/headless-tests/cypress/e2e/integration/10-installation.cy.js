/**
 * @file cypress/e2e/integration/10-installation.cy.js
 *
 * @copyright (c) 2022 Gazi Yücel
 * @copyright (c) 2022 Daniel Nüst
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief
 */

describe('Install OJS and configure journal, issue and users', function () {

    it('Installs the software', function () {
        cy.install();
    });

    it('Adds a journal', function () {
        cy.createContext();
    });

    it('Adds issues to the journal', function () {
        cy.createIssues();
    });

    it('Adds author', function () {
        cy.log('Register author');
        let author = {
            'username': 'aauthor',
            'givenName': 'Augusta',
            'familyName': 'Author',
            'affiliation': 'University of Research',
            'country': 'Germany',
            'roles': ['Author']
        }
        cy.register(author);
        cy.logout();
    });

    it('Adds editor', function () {
        cy.log('Add editor');
        let editor = {
            'username': 'eeditor',
            'givenName': 'Edd',
            'familyName': 'Editor',
            'country': 'Germany',
            'affiliation': 'University of Science',
            'roles': ['Journal manager', 'Journal editor', 'Section editor']
        }
        cy.login('admin');
        cy.get('a:contains("admin"):visible').click();
        cy.get('a:contains("Dashboard")').click({force: true});
        cy.get('a:contains("Users & Roles")').click();
        cy.createUser(editor);
        cy.logout();
    });
});
