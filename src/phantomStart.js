'use strict';


var system = require('system');
var PhantomPhp = require(phantom.libraryPath + '/phantom/PhantomPhp.js');
var args = system.args;

var mode = args[1] || 'stream';
var plugins = [];
if (args.length>2) {
    for (var i=2; i < args.length; i++) {
        plugins.push(args[i]); // TODO all other args for many plugins?
    }
}

var phantomPhp = new PhantomPhp(phantom);

// Plug user args before to make default one not overridable
for (var j = 0; j<plugins.length; j++) {
    var pluginData = require(plugins[j]);
    if (pluginData.handlers) {
        for (var actionName in pluginData.handlers) {
            phantomPhp.plugHandler(actionName, pluginData.handlers[actionName]);
        }
    }
}

phantomPhp.plugHandler("ping", function (message, resolve) {
    resolve('pong');
});

phantomPhp.plugHandler("exit", function (message, resolve) {
    resolve();
    setTimeout(phantom.exit, 0);
});

phantomPhp.plugHandler("run", function (message, resolve, reject, phantomPhp) {
    phantomPhp.run(message, resolve, reject);
});


phantomPhp.plugHandler("plug", function (message, resolve, reject, phantomPhp) {
    if (!message.data) {
        reject('Invalid plugin data');
    } else if (!message.data.handler) {
        reject('No handler to plug');
    } else if (!message.data.action) {
        reject('No action name for handler');
    } else {
        console.log(message.data.handler);
        var data = eval(message.data.handler);
        resolve(data);
    }
});

phantomPhp.streamIO(system.stdin, system.stdout);