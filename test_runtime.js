const fs = require('fs');
const html = fs.readFileSync('test_browser.html', 'utf8');

const match = html.match(/<script>\s*jQuery\(document\)\.ready\(function\(\$\)\s*\{(.*?)<\/script>/s);
const scriptBody = match[1];

let loggedError = null;

const mockJQuery = function(selector) {
    if (selector === document) return mockJQuery;
    return {
        on: function() {},
        click: function() {},
        hide: function() {},
        show: function() {},
        css: function() {},
        addClass: function() {},
        removeClass: function() {},
        data: function() { return 1; },
        val: function() { return "[]"; },
        html: function() {},
        empty: function() {},
        append: function() {},
        each: function() {},
        prop: function() { return this; },
        text: function() { return this; },
        filter: function(fn) { return []; },
        find: function() { return mockJQuery(); },
        closest: function() { return mockJQuery(); }
    };
};
mockJQuery.ready = function(fn) {
    try {
        fn(mockJQuery);
    } catch (e) {
        console.error("CAUGHT RUNTIME ERROR:", e);
        loggedError = e;
    }
};
mockJQuery.post = function() { return { fail: function(){} }; };

global.jQuery = mockJQuery;
global.$ = mockJQuery;
global.document = {};
global.window = {};
global.ajaxurl = 'mock';
global.alert = function(){};
global.confirm = function(){ return true; };

try {
    eval('jQuery(document).ready(function($) {' + scriptBody + '});');
    if (!loggedError) {
        console.log("No runtime error detected in $(document).ready!");
    }
} catch (e) {
    console.error("CAUGHT PARSE/EVAL ERROR:", e);
}
