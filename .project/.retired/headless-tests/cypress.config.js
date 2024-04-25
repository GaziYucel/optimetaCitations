const { defineConfig } = require("cypress");
const { dotenv } = require("dotenv").config({
  path: 'cypress/.env'
});
const fs = require("fs");

module.exports = defineConfig({
  e2e: {
    setupNodeEvents(on, config) {
      // implement node event listeners here
      on('task', {
        readdir({ path }) {
          return fs.readdirSync(path, { withFileTypes: true })
            .filter((item) => item.isDirectory())
            .map((item) => item.name);
        }
      });
    },
    baseUrl: "http://localhost:" + process.env.OJS_PORT,
  },
  env: {
    DBTYPE: process.env.OJS_DB_DRIVER,
    DBNAME: process.env.OJS_DB_NAME,
    DBUSERNAME: process.env.OJS_DB_USER,
    DBPASSWORD: process.env.OJS_DB_PASSWORD,
    DBHOST: process.env.OJS_DB_HOST,
    "contextTitles": {
      "en_US": "Journal of Citations",
    },
    "contextDescriptions": {
      "en_US": "The Journal of Citations is a very citing journal.",
    },
    "contextAcronyms": {
      "en_US": "JoC"
    },
    "defaultGenre": "Article Text",
    "contextPath": "citations",
  },
});
