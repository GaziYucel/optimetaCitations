/**
 * @file cypress/tests/support/commands.js
 *
 * @copyright (c) 2022 Gazi Yücel
 * @copyright (c) 2022 Daniel Nüst
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief This file is processed and loaded automatically before your test files.
 *
 * @see https://on.cypress.io/configuration
 */

// Import commands.js using ES2015 syntax:
import './commands'

import '@foreachbe/cypress-tinymce'

// Alternatively you can use CommonJS syntax:
// require('./commands')

Cypress.config('defaultCommandTimeout', 10000);

Cypress.on('uncaught:exception', (err, runnable) => {
    console.log(`********* Uncaught Exception: ${JSON.stringify(err)}`);

    return false;
});

// https://stackoverflow.com/a/55168680
Cypress.on('before:browser:launch', (browser = {}, args) => {
    if (browser.name === 'chrome') {
        args.push('--remote-debugging-port=9222')
        // whatever you return here becomes the new args
        return args
    }
});

