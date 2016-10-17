'use strict';

var webPage = require('webpage');

var generatePageId = function () {
    return ("000000" + (Math.random()*Math.pow(36,6) << 0).toString(36)).slice(-6);
};

var PageManager = function (phantom) {
    this.pages = {};
    this.pagesResources = {};
};

PageManager.prototype = {

    createPage : function (pageId) {
        if (!pageId) {
            do {
                pageId = generatePageId();
            } while (this.pages[pageId]);
        }

        if (this.pages[pageId]) {
            return false;
        } else {
            this.preparePage(pageId);
        }

        return pageId;
    },

    getPage: function (pageId) {
        return this.pages[pageId];
    },

    /**
     * Instantiate the page and create listeners
     * @param pageId
     */
    preparePage: function (pageId) {
        var page = webPage.create();
        this.pages[pageId] = page;
        this.pagesResources[pageId] = {
            error: null,
            headers: null,
            statusCode: null
        };

        var self = this;

        page.onResourceError = function (resourceError) {
            self.pagesResources[pageId].error = resourceError.errorString;
        };

        page.onResourceReceived = function (resource) {
            if (page.url == resource.url) {
                self.pagesResources[pageId].statusCode = resource.status;
                self.pagesResources[pageId].headers = resource.headers;
            }
        };

        page.onNavigationRequested = function () {
            self.pagesResources[pageId] = {
                error: null,
                headers: null,
                statusCode: null
            };
        }
    },

    getPageLastError: function (pageId) {
        var resources = self.pagesResources[pageId];
        return resources ? resources.error : null;
    }

};

module.exports = PageManager;
