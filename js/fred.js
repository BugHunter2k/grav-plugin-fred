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
            formData.append('image', file);

            // Make the request
            xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', xhrProgress);
            xhr.addEventListener('readystatechange', xhrComplete);
            xhr.open('POST', '/upload-image.json', true);
            xhr.send(formData);
        });
        
        // TODO Check if usage of medium-functions is propable here to 
        //      so we dont't need to create our own functions for that 
        function rotateImage(direction) {
            // Request a rotated version of the image from the server
            var formData;

            // Define a function to handle the request completion
            xhrComplete = function (ev) {
                var response;

                // Check the request is complete
                if (ev.target.readyState != 4) {
                    return;
                }

                // Clear the request
                xhr = null
                xhrComplete = null

                // Free the dialog from its busy state
                dialog.busy(false);

                // Handle the result of the rotation
                if (parseInt(ev.target.status) == 200) {
                    // Unpack the response (from JSON)
                    response = JSON.parse(ev.target.responseText);

                    // Store the image details (use fake param to force refresh)
                    image = {
                        size: response.size,
                        url: response.url + '?_ignore=' + Date.now()
                        };

                    // Populate the dialog
                    dialog.populate(image.url, image.size);

                } else {
                    // The request failed, notify the user
                    new ContentTools.FlashUI('no');
                }
            }

            // Set the dialog to busy while the rotate is performed
            dialog.busy(true);

            // Build the form data to post to the server
            formData = new FormData();
            formData.append('url', image.url);
            formData.append('direction', direction);

            // Make the request
            xhr = new XMLHttpRequest();
            xhr.addEventListener('readystatechange', xhrComplete);
            xhr.open('POST', '/rotate-image', true);
            xhr.send(formData);
        }
        
        dialog.bind('imageUploader.rotateCCW', function () {
            // TODO my be only a new image location has to be returned with rotate=90
            rotateImage('CCW');
        });

        dialog.bind('imageUploader.rotateCW', function () {
            // TODO my be only a new image location has to be returned with rotate=-90
            rotateImage('CW');
        });
        dialog.bind('imageUploader.save', function () {
            var crop, cropRegion, formData;

            // Define a function to handle the request completion
            xhrComplete = function (ev) {
                // Check the request is complete
                if (ev.target.readyState !== 4) {
                    return;
                }

                // Clear the request
                xhr = null
                xhrComplete = null

                // Free the dialog from its busy state
                dialog.busy(false);

                // Handle the result of the rotation
                if (parseInt(ev.target.status) === 200) {
                    // Unpack the response (from JSON)
                    var response = JSON.parse(ev.target.responseText);

                    // Trigger the save event against the dialog with details of the
                    // image to be inserted.
                    dialog.save(
                        response.url,
                        response.size,
                        {
                            'alt': response.alt,
                            'data-ce-max-width': image.size[0]
                        });

                } else {
                    // The request failed, notify the user
                    new ContentTools.FlashUI('no');
                }
            }

            // Set the dialog to busy while the rotate is performed
            dialog.busy(true);

            // Build the form data to post to the server
            formData = new FormData();
            formData.append('url', image.url);

            // Set the width of the image when it's inserted, this is a default
            // the user will be able to resize the image afterwards.
            formData.append('width', 600);

            // Check if a crop region has been defined by the user
            if (dialog.cropRegion()) {
                formData.append('crop', dialog.cropRegion());
            }

            // Make the request
            xhr = new XMLHttpRequest();
            xhr.addEventListener('readystatechange', xhrComplete);
            xhr.open('POST', '/insert-image', true);
            xhr.send(formData);
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