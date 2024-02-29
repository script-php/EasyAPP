# Framework change log

## [v1.5.1] (Release date: 29.02.2024)

#### Changes

* Cleaning
* Changelog added. We didnt had this until now (Oupsy!)
* Added db_schema in system/framework.php to help development process of projects.
* How you use it? Simple!
* Just place these 2 in your app config and add your tables in /db_schema.php

```		
$config['debug'] = true;
$config['dev_db_schema'] = true;
```

* This will create & update you database structure on the go, so you dont have to open database everytime you add a new table or a new col to your database.
* You can copy & paste it in your installer, so you can create and/or update the database more easily.
* Also we have /tests folder. We gonna play there testing functions of the framework. Using the example of tests folder you can make an admin for your project or an api which it should be separated from the project itself.
* Just this for now. 