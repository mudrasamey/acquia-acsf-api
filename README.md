# Acquia ACSF API Calls

This repository contains some general calls to interact with the Acquia ACSF API. It's primary focus is on migrating large multisites into the Acquia Cloud Site Factory.

# Getting Started

1. Clone the repository
2. Create an api-config.php:

```
<?php

// Generate API key/secret - https://docs.acquia.com/acquia-cloud/develop/api/auth/
$key_id = '';
$secret = '';
```

3. Verify connection to API

```
php api-call.php --api_call=verify --docroot=[APPLICATION_ID].prod --realm=ace
```
# Example Commands

*Cron - Add New Scheduled Jobs*

```
php ./api-call.php --api_call=crons-add --docroot=docroot.prod --realm=ace --file=/Users/someuser/Downloads/scheduledjobs.csv

CSV format example (command,schedule,name,server_id):

drush @docroot.prod -dv -l https://www.example.com cron &>> /var/log/sites/${AH_SITE_NAME}/logs/$(hostname -s)/drush-cron.log,* * * * *,cronexample,NULL

```

*Database - Add*

```
php ./api-call.php --api_call=databases --docroot=docroot.prod --realm=ace --file=/Users/someuser/Downloads/database-list.txt

Database List File example (database name - 1 per line)
```

*Domains - Add*

```
php ./api-call.php --api_call=domains-add --docroot=docroot.prod --realm=ace --file=/Users/someuser/Downloads/domain-list.txt

Domain List File example (domain - 1 per line)
```

*Domains - Delete*

```
php ./api-call.php --api_call=domains-delete --docroot=docroot.prod --realm=ace --file=/Users/someuser/Downloads/domain-list.txt

Domain List File example (domain - 1 per line)
```