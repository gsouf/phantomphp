'use strict';

var PhantomPhp = function (phantom) {
    this.phantom = phantom;
    var self = this;
    this.handlers = {};
};

PhantomPhp.prototype = {

    plugHandler: function (name, handler) {
        this.handlers[name] = handler;
    },

    /**
     * Returns a function that should be called to return the result for this message
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
     * Returns a function that should be called when an error happened during execution of action
     */
    createRejecter: function (message, writer) {
        var self = this;
        function reject(errorMessage, errorType)
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
                        errorType: errorType || 'failure'
                    }
                });
                message.done = true;
            }
        }
        return reject;
    },


    /**
     * called when an error happens in the global script
     */
    writeRuntimeError: function (error, message, writer, stack) {
        if (!stack) {
            try {
                throw new Error();
            } catch (err) {
                stack = err.stack;
            }
        }

        var data = {
            'status': 'error',
            'data': {
                'stack': stack,
                'errorType': 'runTimeError',
                'message': error
            }
        };

        if (message) {
            if (!message.done) {
                data.id = message.id;
            }
        }

        writer.writeMessage(data);

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
    },

    listenHttp: function (port) {
        var webserver = require('webserver');
        var server = webserver.create();
        var self = this;

        var started = server.listen(port, function (request, response) {

            var writer;
            var message;

            try {
                // Check the url path
                var urlParts = request.url.split(/\?(.+)?/, 2);
                if (urlParts.length >= 1) {
                    message = {};
                    if (urlParts[0] == '/runAction') {
                        if (request.method == 'POST') {
                            if (request.post) {
                                if (request.post.message) {
                                    message = JSON.parse(request.post.message);
                                } else {
                                    throw new Error('Query data must have a json encoded message');
                                }
                            }
                        } else if (urlParts.length == 2) { // else GET
                            // Parse the query
                            var queryParts = urlParts[1].split('&');
                            for (var i = 0; i < queryParts.length; i++) {
                                var queryItem = queryParts[i].split(/\=(.+)?/, 2);
                                if (queryItem.length == 2) {
                                    message[queryItem[0]] = queryItem[1];
                                }
                            }
                        }
                        var messageProcessed = false;


                        writer = {
                            writeMessage : function (message) {
                                if (!messageProcessed) {
                                    response.statusCode = 200;
                                    response.write(JSON.stringify(message));
                                    response.close();
                                    messageProcessed = true;
                                }
                            }
                        };

                        self.processMessage(message, writer);
                        return;
                    }
                }
            } catch (e) {
                try {
                    var errorMessage = e.message || 'Error while processing message';
                    var errorStack   = e.stack;
                    self.writeRuntimeError(errorMessage, message, writer, errorStack);
                } catch (e) {
                    console.error(e);
                }
                return;
            }

            response.close();

        });

        return started;
    }
};

module.exports = PhantomPhp;