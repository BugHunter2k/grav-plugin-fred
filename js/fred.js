;(function($, document, window, undefined) {
    "use strict";

    // holds the ContentTools Editor Object
    var editor = null;
    // Holds the fred methods
    var fred = {
        init: function() {
            // Init the ContentTools Editor on all elements
            // with data-editable attribute
            editor = ContentTools.EditorApp.get();
            editor.init('*[data-editable]', 'data-name');
            editor.bind('save', this.save);
        },
        save: function(regions) {
            // callback for ContentTools for sending changes
            console.log("saving regions:", regions);
            // TODO convert ALL parts into markdown
            // NOTE Check for changes is done by contenttools
            var markdown = toMarkdown(regions.blog_item)
            // Send changes to Server
            $.ajax( {
                url: '/fredsave.json',
                data: {
                    blog_item:markdown,
                    uri: window.location.href
                },
                method: 'POST',
                dataType: 'json',
                success: function( data ) {
                    if (data.status == "success") {
                        // on usccess flash an checkmark on the screen ;)
                        new ContentTools.FlashUI('ok');
                    } else {
                        // on error flash an X on the screen ;)
                        new ContentTools.FlashUI('no');
                        console.log(data);
                    }
                },
                error: function( data ) {
                    new ContentTools.FlashUI('no');
                    console.log(data);               
                }
            } );
        }
    }
    
    // Init fred on document-read
    $(document).on("ready", function() {
      fred.init();  
    });
    
    // Bind fred to window content
    window.fred = fred;
}(jQuery, document, window));