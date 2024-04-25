/**
 * @file cypress/e2e/integration/30-submission.cy.js
 *
 * @copyright (c) 2022 Gazi Yücel
 * @copyright (c) 2022 Daniel Nüst
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief
 */

describe('Submission, enrich and deposit', function () {

    var submission;

    before(function () {
        submission = {
            id: 0,
            prefix: 'OJSCitationManager',
            title: 'Strengthening the Open Access publishing system through open citations and spatiotemporal metadata',
            subtitle: 'open access publishing, open research information, geospatial metadata, open citations',
            abstract: 'The BMBF project aims to strengthen the Open Access publishing system by connecting open citations and spatiotemporal metadata from open access journals with openly accessible data sources. For this purpose, we will extend Open Journal Systems (OJS) to give it functionalities for collecting and distributing open data by developing two OJS plugins for capturing citation networks and articles\' spatial and temporal properties as machine-readable and accessible metadata. We will ensure the target group-orientated design of the plugins by performing a comprehensive needs analysis for key stakeholders: the editors or operators of OA journals and the researchers, as authors and readers of articles. The developments will be designed and tested in cooperation with several independent journals and OA publishers. Overall, supports the attraction of independent OA journals as publication venues by substantially improving the discoverability and visibility of OA publications through enrichment and interlinking of article metadata.',
            issue: '1'
        };
    });

    it('Submit New Submission and proceed up to deposit to external site', function () {

        // Login
        cy.login('eeditor');
        cy.get('a:contains("eeditor")').click();
        cy.get('a:contains("Dashboard")').click();
        cy.wait(2000);

        // New Submission
        cy.get('a:contains("New Submission"):visible').click();

        // Step 1 Start');

        // Submission Requirements
        cy.get('input[id^="checklist-"]').click({multiple: true});

        // Submit As
        cy.get("body").then(($body) => {
            let userGroup = 'input[id=userGroup3]'; // Journal Editor
            cy.get(userGroup).then($input => {
                if ($input.is(':visible')) {
                    cy.get(userGroup).click();
                } // else: user is just an author and selection is not visible
            });
        });

        // Privacy Consent
        cy.get('input[id="privacyConsent"]').click();

        // Save and continue
        cy.get('#submitStep1Form').within(() => {
            cy.get('button.submitFormButton').click();
        });
        cy.wait(2000);

        // Step 2 Upload Submission

        // Save and continue
        cy.get('#submitStep2Form').within(() => {
            cy.get('button.submitFormButton').click();
        });
        cy.wait(2000);

        // Step 3 Enter Metadata

        // Title
        cy.get('input[id^="title"]').clear().type(submission.title);

        // Subtitle');
        cy.get('input[id^="subtitle"]').clear().type(submission.subtitle);

        // Abstract
        cy.get('iframe')
            .then(($iframe) => {
                const $body = $iframe.contents().find('body')
                cy.wrap($body)
                    .find('p')
                    .type(submission.abstract);
            });

        // CitationsRaw
        cy.fixture('references.txt').then((references) => {
            cy.get('textarea[id^="citationsRaw"]').clear().type(references);
        });

        // Process
        cy.get('#buttonProcess').click();
        cy.on('window:confirm', () => true);
        cy.wait(10000);
        cy.get('.citationManagerRow').should('have.length', 3);
        cy.wait(2000);

        // Save and continue
        cy.get('#submitStep3Form').within(() => {
            cy.get('button.submitFormButton').click();
        });
        cy.wait(2000);

        // Step 4 Confirmation

        // Finish Submission
        cy.get('#submitStep4Form').within(() => {
            cy.get('button.submitFormButton').click();
        });
        cy.wait(2000);

        // Confirm > OK
        cy.get('button.ok.pkpModalConfirmButton').click();
        cy.wait(10000);

        // Step 5 Next Steps

        // Review this submission
        cy.get('a:contains("Review this submission"):visible').click();
        cy.wait(2000);

        // Tab Workflow

        // Assign editor
        cy.get('a:contains("Assign"):visible').click();

        cy.get('#addParticipantForm').within(() => {
            cy.get('select#filterUserGroupIdgrid-users-userselect-userselectgrid').select('5');
            cy.get('button.submitFormButton').contains('Search').click();
            cy.wait(2000);
            cy.get('input[id="user_3"]').click();
            cy.wait(2000);
            cy.get('button.submitFormButton').contains('OK').click();
        });
        cy.wait(3000);

        // Accept and Skip Review
        cy.get('#submissionEditorDecisionsDiv').within(() => {
            cy.get('a:contains("Accept and Skip Review"):visible').click();
        });

        // Assign editor + Do not send an email notification
        cy.get('#promote').within(() => {
            cy.get('input[id="skipEmail-skip"]').click();
        });
        cy.wait(2000);

        // Click Next: Select Files for Copy Editing
        cy.get('#promote').within(() => {
            cy.get('button.promoteForm-step-btn')
                .contains('Next: Select Files for Copyediting').click();
        });
        cy.wait(2000);

        // Record Editorial Decision
        cy.get('#promote').within(() => {
            cy.get('button#promoteForm-complete-btn')
                .contains('Record Editorial Decision').click();
        });
        cy.wait(2000);

        // Tab Publication

        cy.get('button#publication-button').click();
        cy.wait(2000);

        // Select tab Issue > assign issue
        cy.get('button#issue-button').click();
        cy.wait(2000);

        cy.get('#issue').within(() => {
            cy.get('button').contains('Assign to Issue').click();
        });
        cy.wait(2000);

        cy.get('.pkp_modal_panel').within(() => {
            cy.get('select#assignToIssue-issueId-control').select('1');
            cy.get('button').contains('Save').click();
        });
        cy.wait(2000);

        cy.get('.pkp_modal_panel').within(() => {
            cy.get('a.close:visible').click();
        });
        cy.wait(2000);

        // Select tab Identifiers > assign DOI
        cy.get('button#identifiers-button').click();
        cy.get('#identifiers').within(() => {
            cy.get('button').contains('Assign').click();
            cy.get('button').contains('Save').click();
        });
        cy.wait(2000);

        // Click "Schedule For Publication"
        cy.get('.pkpHeader__actions').within(() => {
            cy.get('button.pkpButton')
                .contains('Schedule For Publication').click();
        });
        cy.wait(2000);

        // Click "Publish"
        cy.get('.pkp_modal_panel').within(() => {
            cy.get('button').contains('Publish').click();
        });
        cy.wait(2000);

        // Select tab Citations > Deposit
        cy.get('button#citationManager-button').click();

        // remove class "citationManagerDisabled" from #buttonDeposit
        cy.get('#buttonDeposit').invoke('removeClass', 'citationManagerDisabled');

        // click #buttonDeposit and confirm
        cy.get('#buttonDeposit').click();
        cy.on('window:confirm', () => true);
    });

});
