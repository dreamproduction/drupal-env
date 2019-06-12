# drupal-env
Provides environment-based files mapping.

# How to use
## 1. Install the package
`$ composer require dreamproduction/drupal-env`

## 2. Add/Change composer scripts
First we define a new entry under the **extra** key of the root **composer.json** file named **drupal-env** then add a new entry for each file that needs to be replaced. For example:

**composer.json**
```javascript
"extra": {
   ...
   "drupal-env": {
      ".htaccess": {
         "dev": ".htaccess.dev",
         "stage": ".htaccess.stage",
         "master": ".htaccess.master",
         ...
         "<branch_name>: "<source_file>",
       }
    }
    ....
 }
 ....
 ```

When bundling with composer-boilerplate project you have to modify the **/vendor/dreamproduction/composer-boilerplate/composer.settings.json**  file as such:

**vendor/dreamproduction/composer-boilerplate/composer.settings.json**
```javascript
"scripts": {
    ...
    "post-install-cmd": [
       "@composer run-script drupal-scaffold",
       "@composer run-script drupal-env"
    ],
    ...
    "drupal-env": [
        "DreamProduction\\Composer\\DrupalEnv::postUpdate"
    ],
    ...
```
## 3. Create a GIT branch-based file
You need to create a git branch-based file mapping which will replace the target file as defined in the **extra** area of your **composer.json** root file.