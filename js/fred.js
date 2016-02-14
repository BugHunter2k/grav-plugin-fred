;(function($, document, window, undefined) {
    "use strict";

    // holds the ContentTools Editor Object
    var editor = null;
    
    // Blueprint from contentTools tutorial 
    var ImageUploader = function (dialog) {
        var image, xhr, xhrComplete, xhrProgress;
        dialog.bind('imageUploader.cancelUpload', function () {
            // Cancel the current upload

            // Stop the upload
            if (xhr) {
                xhr.upload.removeEventListener('progress', xhrProgress);
                xhr.removeEventListener('readystatechange', xhrComplete);
                xhr.abort();
            }

            // Set the dialog to empty
            dialog.state('empty');
        });
        dialog.bind("imageUploader.clear", function() {
            // Clear the current image
            dialog.clear();
            image = null;
        }); 
        dialog.bind('imageUploader.fileReady', function (file) {
            // Upload a file to the server
            var formData;
            // TODO Check the admin plugin file upload and try to (re)use that 
            //      would make things much easier

            // Define functions to handle upload progress and completion
            xhrProgress = function (ev) {
                // Set the progress for the upload
                dialog.progress((ev.loaded / ev.total) * 100);
            }

            xhrComplete = function (ev) {
                var response;

                // Check the request is complete
                if (ev.target.readyState != 4) {
                    return;
                }

                // Clear the request
                xhr = null
                xhrProgress = null
                xhrComplete = null

                // Handle the result of the upload
                if (parseInt(ev.target.status) == 200) {
                    // Unpack the response (from JSON)
                    response = JSON.parse(ev.target.responseText);

                    // Store the image details
                    image = {
                        size: response.size,
                        url: response.url
                        };

                    // Populate the dialog
                    dialog.populate(image.url, image.size);

                } else {
                    // The request failed, notify the user
                    new ContentTools.FlashUI('no');
                }
            }

            // Set the dialog state to uploading and reset the progress bar to 0
            dialog.state('uploading');
            dialog.progress(0);

            // Build the form data to post to the server
            formData = new FormData();
            formData.append('file', file);
            formData.append('uri', window.location.href);

            // Make the request
            xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', xhrProgress);
            xhr.addEventListener('readystatechange', xhrComplete);
            xhr.open('POST', '/upload-image.json', true);
            xhr.send(formData);
        });
        
        // While grav media is capable to rotat images, all we have to 
        // do here is append or replace the rotate parameter to the imageurl
        function rotateImage(rotation) {
            // Check if rotation is set 
            if (image.url.indexOf("rotate") > 0) {
                // Get current rotation 
                var regex = /(?:\?|&)rotate=(-?\d+)(?:&|$)/i;
                var match = regex.exec(image.url);
                rotation = +match[1]+rotation;
                // Correct angel 
                rotation %= 360;
                // replace parameter
                image.url = image.url.replace(/(rotate=).*?(&|$)/,'$1' + rotation + '$2');
            } else {
                // get divider 
                var divider = image.url.indexOf("?") > 0?'&':'?';
                // append rotation parameter
                image.url += divider+"rotate="+rotation; 
            }
            // swap size values
            image.size = [image.size[1],image.size[0]];

            // Populate the dialog
            dialog.populate(image.url, image.size);
        }

        dialog.bind('imageUploader.rotateCCW', function () {
            rotateImage(90);
        });

        dialog.bind('imageUploader.rotateCW', function () {
            rotateImage(-90);
        });
        
        dialog.bind('imageUploader.save', function () {
            // TODO implement Cropping
            var crop, cropRegion, formData;
            // Just set the Parameters for
            // image to be inserted.
                    dialog.save(
                        image.url,
                        image.size,
                        {
                            'alt': image.alt,
                            'data-ce-max-width': image.size[0]
                        });

            // Set the dialog to busy while the save is performed
            dialog.busy(true);

            // Check if a crop region has been defined by the user
            if (dialog.cropRegion()) {
                // TODO
                formData.append('crop', dialog.cropRegion());
            }
        });
    }
        
        
    // Holds the fred methods
    var fred = {
        init: function() {
            // Init the ContentTools Editor on all elements
            // with data-editable attribute
            editor = ContentTools.EditorApp.get();
            editor.init('*[data-editable]', 'data-name');
            editor.bind('save', this.save);
            ContentTools.IMAGE_UPLOADER = ImageUploader;
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
        },
        getEditor: function () { return editor; }
//         imageUploader.cancelUpload: function() {},
    }
    
    // Init fred on document-read
    $(document).on("ready", function() {
      fred.init();  
    });
    
    // Bind fred to window content
    window.fred = fred;
}(jQuery, document, window));