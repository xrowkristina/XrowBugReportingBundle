

#        Xrow Bug Reporting

### Description:
This Tool will generate a ZIP file with relevant __eZ Platform/Studio__ information.


What you will find inside the ZIP:
- __QA.txt__ file with common Q&A
- __phpinfo.html__ file with complete PHP information
- __SystemInformation.txt__ file with PHP version, PHP Accelerator, etc
- __InstalledComposer.json__ file with installed components
- __composer.json__ file with current composer
- __FolderPermissionRoot.txt__ file with permissions for folders in installed eZ-directory
- __FolderPermissionWeb.txt__ file with permissions for folders in /web directory
- __FolderPermissionApp.txt__ file with permissions for folders in /app directory
- __/config__ folde with all relevant .yml files from /app/config  directory
- __/logs__ folder with all relevant .log files from /app/logs  directory
- __/ezpublish_legacy/settings__ folder with all relevant settings for eZ Publish 5.4

### Usage:

```php
$ php ezpublish/console bugreporting:create --dest=destination-dir
```
