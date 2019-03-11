
# **Manual Install / Upgrade**
- Locate the /app/code directory which should be under the magento root installation.
- If the code folder is not there, create it.
- Remove the "Emarsys" directory from "MAGENTO_ROOT/app/code" directory if exists
- Download the latest files from master branch and extract the contents
- Copy all files from the downloaded codebase to the "MAGENTO_ROOT/app/code/Emarsys/Emarsys" directory on you Magento instance


### Magento Setup
- Make sure you have the correct file and folder permissions set on your magento installation so that the magnento store can install the app.
- Refer to the Magento 2 documentation for full instructions on how to install an app, the commands should be similar to the following:
```php bin/magento setup:upgrade - This tells magento to install the app.```
```php bin/magento setup:static-content:deploy - This tells magento to deploy static files.```
```php bin/magento cache:flush - This flushes the cache so the app appears in the admin area correctly.```


### Composer Setup
1. ```composer config repositories.emartech-3rd-party-integrations-magento-m2 git git@github.com:emartech/3rd-party-integrations-magento-M2```
2. ```composer require emarsys/emarsys:">=1.0.8"```
3. ```php bin/magento module:enable Emarsys_Emarsys```
4. ```php bin/magento setup:upgrade```
5. ```php bin/magento setup:di:compile```
6. ```php bin/magento setup:static-content:deploy```
7. ```php bin/magento cache:flush```


# Release Notes

## v1.0.12
