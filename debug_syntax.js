const fs = require('fs');
const vm = require('vm');
const html = fs.readFileSync('test_browser.html', 'utf8');
const match = html.match(/<script>\s*jQuery\(document\)\.ready\(function\(\$\)\s*\{(.*?)<\/script>/s);
const scriptBody = match[1];
const fullScript = 'jQuery(document).ready(function($) {' + scriptBody;

try {
    new vm.Script(fullScript, { filename: 'test_script.js' });
    console.log("SUCCESS");
} catch (e) {
    console.log("ERROR:", e.stack);
}
