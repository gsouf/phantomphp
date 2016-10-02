module.exports = {
    handlers: {
        "foo": function (message, resolve, reject, phantomPhp) {
            resolve('foobar');
        }
    }
};