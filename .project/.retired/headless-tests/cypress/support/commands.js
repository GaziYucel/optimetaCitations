/**
 * @file cypress/tests/support/commands.js
 *
 * @copyright (c) 2022 Gazi Yücel
 * @copyright (c) 2022 Daniel Nüst
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @see https://github.com/pkp/pkp-lib/blob/main/cypress/support/commands.js
 * @see https://on.cypress.io/custom-commands
 */

import 'cypress-file-upload';
import 'cypress-wait-until';

Cypress.Commands.add('install', function () {
    cy.visit('/');

    // Administrator information
    cy.get('input[name=adminUsername]').type('admin', { delay: 0 });
    cy.get('input[name=adminPassword]').type('adminadmin', { delay: 0 });
    cy.get('input[name=adminPassword2]').type('adminadmin', { delay: 0 });
    cy.get('input[name=adminEmail]').type('pkpadmin@mailinator.com', { delay: 0 });

    // Database configuration
    cy.get('select[name=databaseDriver]').select(Cypress.env('DBTYPE'));
    cy.get('input[id^=databaseHost-]').clear().type(Cypress.env('DBHOST'), { delay: 0 });
    cy.get('input[id^=databasePassword-]').clear().type(Cypress.env('DBPASSWORD'), { delay: 0 });
    cy.get('input[id^=databaseUsername-]').clear().type(Cypress.env('DBUSERNAME'), { delay: 0 });
    cy.get('input[id^=databaseName-]').clear().type(Cypress.env('DBNAME'), { delay: 0 });
    cy.get('select[id=connectionCharset]').select('Unicode (UTF-8)');

    // for OJS 3.2.1.x, see https://github.com/pkp/pkp-lib/blob/9abc0f70f8d151f153fe36270341938216f3e5c2/cypress/support/commands.js
    cy.get('body').then($body => {
        if ($body.find('#createDatabase').length > 0) {   
            cy.get('input[id=createDatabase]').uncheck();
        }
    });

    // Files directory - keep default for containerised OJS
    //cy.get('input[id^=filesDir-]').clear().type(Cypress.env('FILESDIR'), { delay: 0 });

    // Locale configuration
    cy.get('input[id=additionalLocales-en_US').check();
    cy.get('input[id=additionalLocales-de_DE').check();
    cy.get('input[id=additionalLocales-fr_FR').check();
    cy.get('input[id=additionalLocales-es_ES').check();
    cy.get('input[id=additionalLocales-nl_NL').check();
    cy.get('input[id=additionalLocales-tr_TR').check();

    // Complete the installation
    cy.get('button[id^=submitFormButton-]').click();
	cy.get('p:contains("has completed successfully.")');
});

// from https://github.com/pkp/ojs/blob/stable-3_3_0/cypress/tests/data/20-CreateContext.spec.js
Cypress.Commands.add('createContext', () => {
    cy.login('admin');

    // Create a new context
    cy.get('div[id=contextGridContainer]').find('a').contains('Create').click();

    // Fill in various details
    cy.wait(1000); // https://github.com/tinymce/tinymce/issues/4355

    cy.get('input[name="name-en_US"]').type(Cypress.env('contextTitles')['en_US'], { delay: 0 });
    cy.get('input[name=acronym-en_US]').type(Cypress.env('contextAcronyms')['en_US'], { delay: 0 });
    cy.get('span').contains('Enable this journal').siblings('input').check();
    cy.get('input[name="supportedLocales"][value="en_US').check();
    cy.get('input[name="primaryLocale"][value="en_US').check();

    cy.get('input[name=urlPath]').clear().type(Cypress.env('contextPath'), { delay: 0 });

    // Context descriptions
    cy.setTinyMceContent('context-description-control-en_US', Cypress.env('contextDescriptions')['en_US']);
    cy.get('button').contains('Save').click();

    // Wait for it to finish up before moving on
    cy.contains('Settings Wizard', { timeout: 30000 });
});

Cypress.Commands.add('login', (username, password, context) => {
    context = context || 'index';
    password = password || (username + username);
    cy.visit('index.php/' + context + '/login/signIn', {
        method: 'POST',
        body: { username: username, password: password }
    });
});

Cypress.Commands.add('logout', function () {
    cy.visit('index.php/index/login/signOut');
});

Cypress.Commands.add('setLocale', locale => {
    cy.visit('index.php/index/user/setLocale/' + locale);
});

Cypress.Commands.add('register', data => {
    if (!('email' in data)) data.email = data.username + '@mailinator.com';
    if (!('password' in data)) data.password = data.username + data.username;
    if (!('password2' in data)) data.password2 = data.username + data.username;

    cy.visit('');
    cy.get('a').contains('Register').click();
    cy.get('input[id=givenName]').type(data.givenName, { delay: 0 });
    cy.get('input[id=familyName]').type(data.familyName, { delay: 0 });
    cy.get('input[id=affiliation]').type(data.affiliation, { delay: 0 });
    cy.get('select[id=country]').select(data.country);
    cy.get('input[id=email]').type(data.email, { delay: 0 });
    cy.get('input[id=username]').type(data.username, { delay: 0 });
    cy.get('input[id=password]').type(data.password, { delay: 0 });
    cy.get('input[id=password2]').type(data.password2, { delay: 0 });

    cy.get('input[name=privacyConsent]').click();
    cy.get('button').contains('Register').click();
});

Cypress.Commands.add('createIssues', (data, context) => {
    // create and publish issue
    cy.login('admin');
    cy.get('a:contains("admin"):visible').click();
    cy.get('a:contains("Dashboard")').click({ force: true });
    cy.get('.app__nav a').contains('Issues').click();
    cy.get('a[id^=component-grid-issues-futureissuegrid-addIssue-button-]').click();
    cy.wait(1000); // Avoid occasional failure due to form init taking time
    cy.get('input[name="volume"]').type('1', { delay: 0 });
    cy.get('input[name="number"]').type('2', { delay: 0 });
    cy.get('input[name="year"]').type('2022', { delay: 0 });
    cy.get('input[id=showTitle]').click();
    cy.get('button[id^=submitFormButton]').click();

    cy.get('a.show_extras').click();
    cy.contains('Publish Issue').click();
    cy.get('input[id="sendIssueNotification"]').click();
    cy.get('button[id^=submitFormButton]').click();

    // create a future issue
    cy.wait(1000);
    cy.get('a[id^=component-grid-issues-futureissuegrid-addIssue-button-]').click();
    cy.wait(1000); // Avoid occasional failure due to form init taking time
    cy.get('input[name="volume"]').type('3', { delay: 0 });
    cy.get('input[name="number"]').type('4', { delay: 0 });
    cy.get('input[name="year"]').type('2023', { delay: 0 });
    cy.get('input[id=showTitle]').click();
    cy.get('button[id^=submitFormButton]').click();
});

Cypress.Commands.add('createSubmissionAndPublish', (data, context) => {
    // Initialize some data defaults before starting
    if (data.type == 'editedVolume' && !('files' in data)) {
        data.files = [];
        // Edited volumes should default to a single file per chapter, named after it.
        data.chapters.forEach((chapter, index) => {
            data.files.push({
                'file': 'dummy.pdf',
                'fileName': chapter.title.substring(0, 40) + '.pdf',
                'fileTitle': chapter.title,
                'genre': 'Chapter Manuscript'
            });
            data.chapters[index].files = [chapter.title];
        });
    }
    if (!('files' in data)) data.files = [{
        'file': 'dummy.pdf',
        'fileName': data.title + '.pdf',
        'fileTitle': data.title,
        'genre': Cypress.env('defaultGenre')
    }];
    if (!('keywords' in data)) data.keywords = [];
    if (!('additionalAuthors' in data)) data.additionalAuthors = [];
    if ('series' in data) data.section = data.series; // OMP compatible
    // If 'additionalFiles' is specified, it's to be used to augment the default
    // set, rather than overriding it (as using 'files' would do). Add the arrays.
    if ('additionalFiles' in data) {
        data.files = data.files.concat(data.additionalFiles);
    }
    if (!('issue' in data)) data.issue = '1';

    cy.get('a:contains("Make a New Submission"), div#myQueue a:contains("New Submission"), a:contains("Back to New Submission")').click();
    cy.wait(5000); // extra wait time for GH action

    // === Submission Step 1 ===
    if ('section' in data) cy.get('select[id="sectionId"],select[id="seriesId"]').select(data.section);
    cy.get('input[id^="checklist-"]').click({ multiple: true });
    switch (data.type) { // Only relevant to OMP
        case 'monograph':
            cy.get('input[id="isEditedVolume-0"]').click();
            break;
        case 'editedVolume':
            cy.get('input[id="isEditedVolume-1"]').click();
            break;
    }
    cy.get('input[id=privacyConsent]').click();
    if ('submitterRole' in data) {
        cy.get('input[name=userGroupId]').parent().contains(data.submitterRole).click();
    } else {
        cy.get("body").then($body => {
            cy.get('input[id=userGroupId]').then($input => {
                if ($input.is(':visible')) {
                    cy.get('input[id=userGroupId]').click();
                } // else: user is just an author and selection is not visible
            });
        });
    }
    cy.get('button.submitFormButton').click();

    // === Submission Step 2 ===

    // OPS uses the galley grid
    if (Cypress.env('contextTitles').en_US == 'Public Knowledge Preprint Server') {
        data.files.forEach(file => {
            cy.get('a:contains("Add galley")').click();
            cy.wait(2000); // Avoid occasional failure due to form init taking time
            cy.get('div.pkp_modal_panel').then($modalDiv => {
                cy.wait(3000);
                if ($modalDiv.find('div.header:contains("Create New Galley")').length) {
                    cy.get('div.pkp_modal_panel input[id^="label-"]').type('PDF', { delay: 0 });
                    cy.get('div.pkp_modal_panel button:contains("Save")').click();
                    cy.wait(2000); // Avoid occasional failure due to form init taking time
                }
            });
            cy.get('select[id=genreId]').select(file.genre);
            cy.fixture(file.file, 'base64').then(fileContent => {
                cy.get('input[type=file]').attachFile(
                    { fileContent, 'fileName': file.fileName, 'mimeType': 'application/pdf', 'encoding': 'base64' }
                );
            });
            cy.get('button').contains('Continue').click();
            cy.wait(2000);
            for (const field in file.metadata) {
                cy.get('input[id^="' + Cypress.$.escapeSelector(field) + '"]:visible,textarea[id^="' + Cypress.$.escapeSelector(field) + '"]').type(file.metadata[field], { delay: 0 });
                cy.get('input[id^="language"').click({ force: true }); // Close multilingual and datepicker pop-overs
            }
            cy.get('button').contains('Continue').click();
            cy.get('button').contains('Complete').click();
        });

        // Other applications use the submission files list panel
    } else {
        cy.get('button:contains("Add File")');

        // A callback function used to prevent Cypress from failing
        // when an uncaught exception occurs in the code. This is a
        // workaround for an exception that is thrown when a file's
        // genre is selected in the modal form. This exception happens
        // because the submission step 2 form handler attaches a
        // validator to the modal form.
        //
        // It should be possible to remove this workaround once the
        // submission process has been fully ported to Vue.
        const allowException = function (error, runnable) {
            return false;
        }
        cy.on('uncaught:exception', allowException);

        // File uploads
        const primaryFileGenres = ['Article Text', 'Book Manuscript', 'Chapter Manuscript'];
        data.files.forEach(file => {
            cy.fixture(file.file, 'base64').then(fileContent => {
                cy.get('input[type=file]').attachFile(
                    { fileContent, 'fileName': file.fileName, 'mimeType': 'application/pdf', 'encoding': 'base64' }
                );
                var $row = cy.get('a:contains("' + file.fileName + '")').parents('.listPanel__item');
                if (primaryFileGenres.includes(file.genre)) {
                    // For some reason this is locating two references to the button,
                    // so just click the last one, which should be the most recently
                    // uploaded file.
                    $row.get('button:contains("' + file.genre + '")').last().click();
                    $row.get('span:contains("' + file.genre + '")');
                } else {
                    $row.get('button:contains("Other")').last().click();
                    cy.get('#submission-files-container .modal label:contains("' + file.genre + '")').click();
                    cy.get('#submission-files-container .modal button:contains("Save")').click();
                }
                // Make sure the genre selection is complete before moving to the
                // next file.
                $row.get('button:contains("What kind of file is this?")').should('not.exist');
            });
        });
    }

    // Save the ID to the data object
    cy.location('search')
        .then(search => {
            // this.submission.id = parseInt(search.split('=')[1], 10);
            data.id = parseInt(search.split('=')[1], 10);
        });

    cy.get('button').contains('Save and continue').click();
    cy.wait(2000); // Avoid occasional failure due to form init taking time

    // === Submission Step 3 ===
    // Metadata fields
    let locale = '' // 'en_US';
    cy.get('input[id^="title-' + locale + '"').type(data.title, { delay: 0 });
    cy.get('label').contains('Title').click(); // Close multilingual popover
    cy.get('textarea[id^="abstract-' + locale + '"]').then(node => {
        cy.setTinyMceContent(node.attr('id'), data.abstract);
    });
    let seperator = locale === '' ? '' : '-';
    cy.get('ul[id^="' + locale + seperator + 'keywords-"]').then(node => {
        data.keywords.forEach(keyword => {
            node.tagit('createTag', keyword);
        });
    });
    data.additionalAuthors.forEach(author => {
        if (!('role' in author)) author.role = 'Author';
        cy.get('a[id^="component-grid-users-author-authorgrid-addAuthor-button-"]').click();
        cy.wait(250);
        cy.get('input[id^="givenName-' + locale + '"]').type(author.givenName, { delay: 0 });
        cy.get('input[id^="familyName-' + locale + '"]').type(author.familyName, { delay: 0 });
        cy.get('select[id=country]').select(author.country);
        cy.get('input[id^="email"]').type(author.email, { delay: 0 });
        if ('affiliation' in author) cy.get('input[id^="affiliation-' + locale + '"]').type(author.affiliation, { delay: 0 });
        cy.get('label').contains(author.role).click();
        cy.get('form#editAuthor').find('button:contains("Save")').click();
        cy.get('div[id^="component-grid-users-author-authorgrid-"] span.label:contains("' + Cypress.$.escapeSelector(author.givenName + ' ' + author.familyName) + '")');
    });
    // Chapters (OMP only)
    if ('chapters' in data) data.chapters.forEach(chapter => {
        cy.waitJQuery();
        cy.get('a[id^="component-grid-users-chapter-chaptergrid-addChapter-button-"]:visible').click();
        cy.wait(2000); // Avoid occasional failure due to form init taking time

        // Contributors
        chapter.contributors.forEach(contributor => {
            cy.get('form[id="editChapterForm"] label:contains("' + Cypress.$.escapeSelector(contributor) + '")').click();
        });

        // Title/subtitle
        cy.get('form[id="editChapterForm"] input[id^="title-' + locale + '"]').type(chapter.title, { delay: 0 });
        if ('subtitle' in chapter) {
            cy.get('form[id="editChapterForm"] input[id^="subtitle-' + locale + '"]').type(chapter.subtitle, { delay: 0 });
        }
        cy.get('div.pkp_modal_panel div:contains("Add Chapter")').click(); // fixme: Resolve focus problem on title field

        cy.flushNotifications();
        cy.get('form[id="editChapterForm"] button:contains("Save")').click();
        cy.get('div:contains("Your changes have been saved.")');
        cy.waitJQuery();

        // Files
        if ('files' in chapter) {
            cy.get('div[id="chaptersGridContainer"] a:contains("' + Cypress.$.escapeSelector(chapter.title) + '")').click();
            chapter.files.forEach(file => {
                cy.get('form[id="editChapterForm"] label:contains("' + Cypress.$.escapeSelector(chapter.title.substring(0, 40)) + '")').click();
            });
            cy.flushNotifications();
            cy.get('form[id="editChapterForm"] button:contains("Save")').click();
            cy.get('div:contains("Your changes have been saved.")');
        }

        cy.get('div[id^="component-grid-users-chapter-chaptergrid-"] a.pkp_linkaction_editChapter:contains("' + Cypress.$.escapeSelector(chapter.title) + '")');
    });

    cy.get('form[id=submitStep3Form]').find('button').contains('Save and continue').click();

    // === Submission Step 4 ===
    cy.waitJQuery();
    cy.get('form[id=submitStep4Form]').find('button').contains('Finish Submission').click();
    cy.get('button.pkpModalConfirmButton').click();
    cy.waitJQuery();
    cy.get('h2:contains("Submission complete")');

    cy.logout();

    // === Jump through review and publication  ===
    cy.login('eeditor');
    cy.get('a:contains("eeditor"):visible').click();
    cy.get('a:contains("Dashboard")').click({ force: true });
    // only one editor, should be only one submission under "My Assigned"
    cy.get('a:contains("View")').click();
    cy.get('a[id^="accept-button"]').click();
    cy.get('input[id^="skipEmail-skip"]').click();
    cy.get('form[id="promote"] button:contains("Next:")').click();
    cy.get('input[id^="select"]').click();
    cy.get('button:contains("Record Editorial Decision")').click();
    cy.wait(4000);
    cy.get('a:contains("Send To Production")').click();
    cy.get('input[id="skipEmail-skip"]').click();
    cy.get('form[id="promote"] button:contains("Next:")').click();
    cy.get('input[id^="select"]').click();
    cy.get('button:contains("Record Editorial Decision")').click();
    cy.wait(4000);
    cy.get('div[id="production"]')
        .find('button:contains("Schedule For Publication")').click();
    cy.get('button[id="issue-button"]').click();
    cy.get('button:contains("Assign to Issue")').click();
    cy.get('select[id^="assignToIssue"]').select(data.issue);
    cy.get('div[id^="assign"]').
        find('button:contains("Save")').click();
    cy.wait(2000);
    cy.get('button:contains("Schedule For Publication")');
    cy.get('button:contains("Publish"), div[class="pkpFormPages"] button:contains("Schedule For Publication")').click();
});

Cypress.Commands.add('findSubmissionAsEditor', (username, password, familyName, context) => {
    context = context || 'publicknowledge';
    cy.login(username, password, context);
    cy.get('button[id="active-button"]').click();
    cy.contains('View ' + familyName).click({ force: true });
});

Cypress.Commands.add('sendToReview', (toStage, fromStage) => {
    if (!toStage) toStage = 'External';
    cy.get('*[id^=' + toStage.toLowerCase() + 'Review-button-]').click();
    if (fromStage == "Internal") {
        cy.get('form[id="promote"] button:contains("Next:")').click();
        cy.get('button:contains("Record Editorial Decision")').click();
    } else {
        cy.get('form[id="initiateReview"] button:contains("Send")').click();
    }
    cy.get('span.description:contains("Waiting for reviewers")');
});

Cypress.Commands.add('assignParticipant', (role, name, recommendOnly) => {
    var names = name.split(' ');
    cy.get('a[id^="component-grid-users-stageparticipant-stageparticipantgrid-requestAccount-button-"]:visible').click();
    cy.get('select[name=filterUserGroupId').select(role);
    cy.get('input[id^="namegrid-users-userselect-userselectgrid-"]').type(names[1], { delay: 0 });
    cy.get('form[id="searchUserFilter-grid-users-userselect-userselectgrid"]').find('button[id^="submitFormButton-"]').click();
    cy.get('input[name="userId"]').click(); // Assume only one user results from the search.
    if (recommendOnly) cy.get('input[name="recommendOnly"]').click();
    cy.flushNotifications();
    cy.get('button').contains('OK').click();
    cy.waitJQuery();
});

Cypress.Commands.add('recordEditorialRecommendation', recommendation => {
    cy.get('a[id^="recommendation-button-"]').click();
    cy.get('select[id=recommendation]').select(recommendation);
    cy.get('button').contains('Record Editorial Recommendation').click();
    cy.get('div').contains('Recommendation:');
});

Cypress.Commands.add('assignReviewer', name => {
    cy.wait(2000); // fixme: Occasional problems opening the grid
    cy.get('a[id^="component-grid-users-reviewer-reviewergrid-addReviewer-button-"]').click();
    cy.waitJQuery();
    cy.get('.listPanel--selectReviewer .pkpSearch__input', { timeout: 20000 }).type(name, { delay: 0 });
    cy.contains('Select ' + name).click();
    cy.waitJQuery();
    cy.get('button:contains("Add Reviewer")').click();
    cy.contains(name + ' was assigned to review');
    cy.waitJQuery();
});

Cypress.Commands.add('recordEditorialDecision', decision => {
    cy.get('ul.pkp_workflow_decisions:visible a:contains("' + Cypress.$.escapeSelector(decision) + '")', { timeout: 30000 }).click();
    if (decision != 'Request Revisions' && decision != 'Decline Submission') {
        cy.get('button:contains("Next:")').click();
    }
    cy.get('button:contains("Record Editorial Decision")').click();
});

Cypress.Commands.add('performReview', (username, password, title, recommendation, comments, context) => {
    context = context || 'publicknowledge';
    comments = comments || 'Here are my review comments';
    cy.login(username, password, context);
    cy.get('a').contains('View ' + title).click({ force: true });
    cy.get('input[id="privacyConsent"]').click();
    cy.get('button:contains("Accept Review, Continue to Step #2")').click();
    cy.get('button:contains("Continue to Step #3")').click();
    cy.wait(2000); // Give TinyMCE control time to load
    cy.get('textarea[id^="comments-"]').then(node => {
        cy.setTinyMceContent(node.attr('id'), comments);
    });
    if (recommendation) {
        cy.get('select#recommendation').select(recommendation);
    }
    cy.get('button:contains("Submit Review")').click();
    cy.get('button:contains("OK")').click();
    cy.get('h2:contains("Review Submitted")');
    cy.logout();
});

Cypress.Commands.add('createUser', user => {
    if (!('email' in user)) user.email = user.username + '@mailinator.com';
    if (!('password' in user)) user.password = user.username + user.username;
    if (!('password2' in user)) user.password2 = user.username + user.username;
    if (!('roles' in user)) user.roles = [];
    cy.get('div[id=userGridContainer] a:contains("Add User")').click();
    cy.wait(2000); // Avoid occasional glitches with given name field
    cy.get('input[id^="givenName-"]').type(user.givenName, { delay: 0 });
    cy.get('input[id^="familyName-"]').type(user.familyName, { delay: 0 });
    cy.get('input[name=email]').type(user.email, { delay: 0 });
    cy.get('input[name=username]').type(user.username, { delay: 0 });
    cy.get('input[name=password]').type(user.password, { delay: 0 });
    cy.get('input[name=password2]').type(user.password2, { delay: 0 });
    if (!user.mustChangePassword) {
        cy.get('input[name="mustChangePassword"]').click();
    }
    cy.get('select[name=country]').select(user.country);
    cy.contains('More User Details').click();
    cy.get('span:contains("Less User Details"):visible');
    cy.get('input[id^="affiliation-"]').type(user.affiliation, { delay: 0 });
    cy.get('form[id=userDetailsForm]').find('button[id^=submitFormButton]').click();
    user.roles.forEach(role => {
        cy.get('form[id=userRoleForm]').contains(role).click();
    });
    cy.get('form[id=userRoleForm] button[id^=submitFormButton]').click();
    cy.get('span[id$="-username"]:contains("' + Cypress.$.escapeSelector(user.username) + '")');
});

Cypress.Commands.add('flushNotifications', function () {
    cy.window().then(win => {
        if (typeof pkp !== 'undefined' && typeof pkp.eventBus !== 'undefined') {
            pkp.eventBus.$emit('clear-all-notify');
        }
    });
});

Cypress.Commands.add('waitJQuery', function () {
    cy.waitUntil(() => cy.window().then(win => win.jQuery.active == 0));
});

Cypress.Commands.add('consoleLog', message => {
    cy.task('consoleLog', message);
});
