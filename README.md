# OntoWiki file resource extension

With this extension you can attach a file document on any resource managed by
OntoWiki as well as create new file resources from the scratch.

You can upload / download and delete attachments on resources and in
addition to that, a file manager lists all existing files and you can
(mass-)delete and upload from this manager too.

## Attachment Module

This module allows for uploading / downloading and deletion of attached
files on any resource. The module is visible only for resources of
certain types (config option: `typeExpression`).

![module status: download / deletion possible][download]

![module status: upload possible][upload]

## File Manager

The file manager is available via menu entry "Extras -> File Manager".
You can use it to upload files in the generic file namespace of your
installation (`OW/files/filename`) and to delete any file resource.

![file manager screenshot][filemanager]


[filemanager]: https://github.com/AKSW/files.ontowiki/raw/master/misc/filemanager.png
[upload]: https://github.com/AKSW/files.ontowiki/raw/master/misc/upload.png
[download]: https://github.com/AKSW/files.ontowiki/raw/master/misc/download.png

