'use strict';


var system = require('system');
var PhantomPhp = require(phantom.libraryPath + '/phantom/PhantomPhp.js');
var args = system.args;

var parsedArgs;
if (args.length > 1) {
    parsedArgs = JSON.parse(args[1]);
} else {
    parsedArgs = {};
}

if (typeof parsedArgs != 'object') {
    console.error('Invalid input arg: ' . args[1]);
    phantom.exit();
} else {
    var mode = parsedArgs.mode || 'stream';
    var plugins = parsedArgs.plugins || [];

    var phantomPhp = new PhantomPhp(phantom);

    // Plug user args and override them with default mandatory handlers
    for (var j = 0; j < plugins.length; j++) {
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

    phantomPhp.streamIO(system.stdin, system.stdout);
}