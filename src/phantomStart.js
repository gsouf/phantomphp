'use strict';

var system = require('system');
var PhantomPhp = require(phantom.libraryPath + '/phantom/PhantomPhp.js');
var args = system.args;

// Fix error stream: https://github.com/ariya/phantomjs/issues/10150
console.error = function () {
    var date = new Date();
    var hour = date.getHours();
    hour = hour < 10 ? '0' + hour : hour;
    var minute = date.getMinutes();
    minute = minute < 10 ? '0' + minute : minute;
    var secondes = date.getSeconds();
    secondes = secondes < 10 ? '0' + secondes : secondes;
    var day = date.getDate();
    day = day < 10 ? '0' + day : day;
    var month = date.getMonth() + 1;
    month = month < 10 ? '0' + month : month;
    var dataStr = date.getUTCFullYear() + "-"
        + month + '-'
        + day  + ' '
        + hour + ':'
        + minute + ':'
        + secondes;


    system.stderr.write('[' + dataStr + '] ' + Array.prototype.join.call(arguments, ' ') + '\n');
};

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

    plugins.push(phantom.libraryPath + '/phantom/page.handlers.js');

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
        if (phantomPhp.beforeResolveClose) {
            phantomPhp.beforeResolveClose();
        }

        resolve('exit');

        if (phantomPhp.beforeClose) {
            phantomPhp.beforeClose();
        }

        setTimeout(function () {
            console.log('exit');
            phantom.exit()
        }, 0);
    });

    var listening = false;

    switch (mode) {
        case 'stream':
            phantomPhp.streamIO(system.stdin, system.stdout);
            listening = true;
            break;
        case 'http':
            var host = parsedArgs.httpPort || 8080;
            if (parsedArgs.httpHost) {
                host = parsedArgs.httpHost + ":"+ host;
            }
            listening = phantomPhp.listenHttp(host);
            if (!listening) {
                console.error('Unable to start phantom server');
            } else {
                console.log('listening message on: ' + host);
            }
            break;
        default:
            console.error('Invalid communication mode: ' . mode);
            phantom.exit();
            break;
    }

    if (listening) {
        console.log('ok');
    } else {
        console.log('error');
        setTimeout(phantom.exit, 0);
    }

    // Notice the caller that the script is started and ready to accept incoming messages
}