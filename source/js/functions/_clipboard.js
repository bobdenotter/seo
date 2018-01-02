//((=== Copy Code To Clipboard  ===))//

const Clipboard = require("clipboard");  

const copy = 
new Clipboard('.copy-code', {
    target: function(trigger) {
        return trigger.parentNode.nextElementSibling;
    }
});

copy.on('success', function(e) {
    const trigger = e.trigger;
    trigger.setAttribute("aria-label","Copied!");
    setTimeout(function(){trigger.setAttribute("aria-label","Copy Code")}, 1000);
    e.clearSelection();
});          