'use strict';



function findPageOrReject(pageId, reject, phantomPhp)
{
    var page = phantomPhp.pageManager.getPage(pageId);
    if (!page) {
        reject('Page with id ' + pageId + ' does not exist', 'pageDoesNotExist');
    } else {
        return page;
    }
}


module.exports = {
    handlers: {
        "pageCreate": function (message, resolve, reject, phantomPhp) {
            var pageId = phantomPhp.pageManager.createPage(message.data ? message.data.pageId : null);

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

            if (page = findPageOrReject(pageId, reject, phantomPhp)) {
                page.open(url, {}, function (status) {
                    if (status !== 'success') {
                        reject('Could not fetch the page for the url: "' + url + '". Reason: ' + phantomPhp.pageManager.getPageLastError(pageId), 'CannotNavigateToUrl');
                    } else {
                        resolve({url: page.url});
                    }
                });
            }
        },

        "pageGetDom": function (message, resolve, reject, phantomPhp) {
            var pageId = message.data.pageId;
            var page;

            if (page = findPageOrReject(pageId, reject, phantomPhp)) {
                resolve({DOM: page.content});
            }
        },

        "pageList": function (message, resolve, reject, phantomPhp) {
            var list = [];
            for (var i in phantomPhp.pageManager.pages) {
                list.push({'id': i, url: phantomPhp.pageManager.pages[i].url});
            }
            resolve(list);
        },

        "pageRunScript": function (message, resolve, reject, phantomPhp) {
            var pageId = message.data.pageId;
            var script = message.data.script;
            var page;

            if (page = findPageOrReject(pageId, reject, phantomPhp)) {
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
