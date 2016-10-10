'use strict';

var webPage = require('webpage');
var pages = {};
var pagesResources = {};


var generatePageId = function () {
    return ("0000" + (Math.random()*Math.pow(36,4) << 0).toString(36)).slice(-4);
};

/**
 * Instantiate the page and create listeners
 * @param pageId
 */
var preparePage = function (pageId) {
    var page = webPage.create();
    pages[pageId] = page;
    pagesResources[pageId] = {
        error: null,
        headers: null,
        statusCode: null
    };
    page.onResourceError = function (resourceError) {
        pagesResources[pageId].error = resourceError.errorString;
    };

    page.onResourceReceived = function (resource) {
        if (page.url == resource.url) {
            pagesResources[pageId].statusCode = resource.status;
            pagesResources[pageId].headers = resource.headers;
        }
    };
};

var createPage = function (pageId) {
    if (!pageId) {
        do {
            pageId = generatePageId();
        } while (pages[pageId]);
    }

    if (pages[pageId]) {
        return false;
    } else {
        preparePage(pageId);
    }

    return pageId;
};


function findPageOrReject(pageId, reject)
{
    if (!pages[pageId]) {
        reject('Page with id ' + pageId + ' does not exist', 'pageDoesNotExist');
    } else {
        return pages[pageId];
    }
}


module.exports = {
    handlers: {
        "pageCreate": function (message, resolve, reject, phantomPhp) {
            var pageId = createPage(message.data ? message.data.pageId : null);

            if (false === pageId) {
                reject('Page with id ' + pageId + ' was already created', 'pageIdAlreadyExists');
            } else {
                resolve({pageId: pageId});
            }
        },

        "pageNavigate": function (message, resolve, reject, phantomPhp) {
            var pageId = message.data.pageId;
            var url = message.data.url;
            var page;

            if (page = findPageOrReject(pageId, reject)) {
                pagesResources[pageId] = {
                    error: null,
                    headers: null,
                    statusCode: null
                };

                page.open(url, {}, function (status) {
                    if (status !== 'success') {
                        reject('Could not fetch the page for the url: "' + url + '". Reason: ' + pagesResources[pageId].error, 'CannotNavigateToUrl');
                    } else {
                        resolve({url: page.url});
                    }
                });
            }
        },

        "pageGetDom": function (message, resolve, reject, phantomPhp) {
            var pageId = message.data.pageId;
            var page;

            if (page = findPageOrReject(pageId, reject)) {
                resolve({DOM: page.content});
            }
        },

        "pageList": function (message, resolve, reject, phantomPhp) {
            var list = [];
            for (var i in pages) {
                list.push({'id': i, url: pages[i].url});
            }
            resolve(list);
        },

        "pageRunScript": function (message, resolve, reject, phantomPhp) {
            var pageId = message.data.pageId;
            var script = message.data.script;
            var page;

            if (page = findPageOrReject(pageId, reject)) {
                if (!script) {
                    reject('No script to run', 'NoScriptToRun');
                } else {
                    // @codingStandardsIgnoreStart
                    var handler = Function(script);
                    // @codingStandardsIgnoreEnd
                    resolve(page.evaluate(handler));
                }
            }
        }


    }
};