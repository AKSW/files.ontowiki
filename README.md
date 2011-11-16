# OntoWiki file resource extension

With this extension you can attach a file document on any resource you
want.

You can upload / download and delete these attachments and in
addition to that, a file manager lists all existing files and you can
(mass-)delete and upload from this manager too.

## File Manager

The file manager is available via menu entry "Extras -> File Manager".
You can use it to upload files in the generic file namespace of your
installation (`OW/files/filename`) and to delete any file resource.

![file manager screenshot][filemanager]

## Attachment Module

An additional module allows for uploading / downloading and deletion of
attached files on any resource.
The module is visible only for resources of certain types (config option:
`typeExpression`).

![module status: upload possible][upload]
![module status: download / deletion possible][download]


[filemanager]: https://github.com/AKSW/files.ontowiki/raw/master/misc/filemanager.png
[upload]: https://github.com/AKSW/files.ontowiki/raw/master/misc/upload.png
[download]: https://github.com/AKSW/files.ontowiki/raw/master/misc/download.png

