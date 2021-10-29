const bs = require('browser-sync');

module.exports = function bs_files() {
    bs.init({
        browser: ['chrome', 'firefox'],
        watch: true,
        notify: false,
        open: false,
        // open: 'external',
        host: 'site.local',
        proxy: 'http://site.local',
        logLevel: 'info',
        logPrefix: 'BS-FILES:',
        logConnections: true,
        logFileChanges: true,
    });
}