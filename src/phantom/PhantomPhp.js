'use strict';

var webpage = require("webpage");

var PhantomPhp = function (phantom) {
    this.pages = {};
    this.phantom = phantom;
    var self = this;
    this.handlers = {};
};

PhantomPhp.prototype = {
    run: function (message, resolve, reject) {
        try {
            this.evalSrc(message.data.src, resolve, reject);
        } catch (err) {
            reject(err);
        }
    },

    plugHandler: function (name, handler) {
        this.handlers[name] = handler;
    },

    evalSrc: function (src, resolve, reject) {
        var handler = eval(src); // eslint-disable-line
        handler(resolve, reject);
    },


    /**
     * Returns a function that should be called to return the result for this message.
     */
    createResolver: function (message, writer) {
        var self = this;
        function resolve(data)
        {
            writer.writeMessage({
                status: "success",
                id: message.done ? null : message.id,
                data: data
            });
            message.done = true;
        }
        return resolve;
    },



    /**
     * Returns a function that should be called when an error happened.
     */
    createRejecter: function (message, writer) {
        var self = this;
        function reject(errorMessage)
        {
            try {
                throw new Error(errorMessage ? errorMessage || "Error" : "Error");
            } catch (err) {
                writer.writeMessage({
                    id: message.done ? null : message.id,
                    status: "error",
                    data: {
                        message: err.message,
                        stack: err.stack,
                        errorType: 'failure'
                    }
                });
                message.done = true;
            }
        }
        return reject;
    },


    /**
     * called when an error happens in the gobal script ()
     */
    writeRuntimeError: function (error, message, writer) {
        if (!message) {
            writer.writeMessage({
                'status': 'error',
                'error': error,
                'data': {
                    'errorType': 'runTimeError'
                }
            });
        } else {
            writer.writeMessage({
                'id': message.done ? null: message.id,
                'status': 'error',
                'data': {
                    'errorType': 'runTimeError',
                    'message': error
                },
            });
        }
    },


    /**
     * Get a page or creates it if it does not exist yet
     */
    getPage: function (pageId) {
        var page = this.pages[pageId];
        if (!page) {
            pages[pageId] = page = webpage.create();
        }
        return page;
    },

    processMessage: function (message, writer) {
        if (!message.action) {
            this.writeRuntimeError("No action in the message", message, writer);
        } else {
            var handler = this.handlers[message.action];
            if (!handler) {
                this.writeRuntimeError("Unknown action '" + message.action + "'", message, writer);
            } else {
                handler(message, this.createResolver(message, writer), this.createRejecter(message, writer),  this);
            }
        }
    },

    /**
     * Start a process that uses given streams to process input and output
     */
    streamIO: function (input, output) {
        var self = this;

        var writer = {
            writeMessage : function (message) {
                output.writeLine(JSON.stringify(message));
            }
        };

        var loop = function () {
            // stdin.readLine() is sync and halts until a whole line is read
            var line = input.readLine();
            var message;
            try {
                message = JSON.parse(line);
            } catch (e) {
                self.writeRuntimeError('Unable to parse json input', null, writer);
                message = null;
            }

            if (message) {
                self.processMessage(message, writer);
            }

            setTimeout(loop, 0);
        };

        setTimeout(loop, 0);
    }
};

module.exports = PhantomPhp;