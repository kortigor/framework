const bs = require('browser-sync');

module.exports = function php(done) {
    bs.reload();
    done();
}