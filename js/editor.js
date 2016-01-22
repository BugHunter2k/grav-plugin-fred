window.addEventListener('load', function() {
    var editor;


     editor = ContentTools.EditorApp.get();
     editor.init('*[data-editable]', 'data-name');
});

