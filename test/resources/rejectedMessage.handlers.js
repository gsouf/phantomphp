module.exports = {
    handlers: {
        "rejectedMessage": function(message, resolve, reject, phantomPhp){
            reject('whoops :(');
        }
    }
};