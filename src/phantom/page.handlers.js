'use strict';

var webPage = require('webpage');
var pages = {};


var generatePageId = function(){
    return ("0000" + (Math.random()*Math.pow(36,4) << 0).toString(36)).slice(-4);
};

var createPage = function(pageId, options){
    if(!pageId){
        do{
            pageId = generatePageId();
        }while(pages[pageId]);
    }

    if(pages[pageId]){
        return false;
    }else{
        var page = webPage.create();
        pages[pageId] = page;
    }

    return pageId;
};

module.exports = {
    handlers: {
        "pageCreate": function (message, resolve, reject, phantomPhp) {
            var pageId = createPage(null, message.data ? message.data.pageId : null);

            if(false === pageId){
                reject('Page with id ' + pageId + ' was already created', 'pageIdAlreadyExists');
            }else{
                resolve({pageId: pageId});
            }
        },

        "pageNavigate": function (message, resolve, reject, phantomPhp) {

            console.log(JSON.stringify(message));

            var pageId = message.data.pageId;
            var url = message.data.url;

            if(!pages[pageId]){
                reject('Page with id ' + pageId + ' does not exist', 'pageDoesNotExist');
            }else{
                resolve('ok');
            }

        }
    }
};