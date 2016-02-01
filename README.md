# fred
Frontend editing for Grav using contenttools or prosemirror

Developement Enviroment: http://new.judo-rietberg.de
Login as "testuser" PW "Test12345" (/user-login/)

TODOs
- save changes 
 - get right page(object) for submitted data / from uri
- decide which editor is the best
- invesitgate
 - make sure only markdown content is edited? Or what about twig content?
 - how to edit multi-language pages
 - how to edit modular pages
 - how to best/automaticaly integrate into all themes
 - how to add site.editor access to users
 - how to best login users on frontend

Other Things
- Possible related: https://github.com/getgrav/grav-plugin-admin/issues/346
 
 
Done
- use login-Plugin and access:site.editor for user authentification
- warp javascript in a closure function and autoload on jQuery-pageready 



Thanks to:
https://github.com/domchristie/to-markdown
http://getcontenttools.com/
https://github.com/getgrav/grav