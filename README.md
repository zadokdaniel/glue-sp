# glue-sp
A simple library for connecting PHP and SQL servers, with an ORM approach and a multiple DB connections caching mechanism.

## Warning 
This library is far from being perfect and was developed adhoc.

## Files Structure

### Database.php
The Database class sets database connection and initializes a PDO connection.

### DBManager.php
The DBManager (or DBM in short) needs to be initialized with an array collecting all databases which are going to be in use.
The array should be similar to the following example:

``` 
const DBS =     [self::DB_EMS         =>        ["localhost",
                                                 "ems",
                                                 "root",
                                                 ""],
                 self::DB_EXAMINERS   =>        ["localhost",
                                                 "ems_employees",
                                                 "root",
                                                 ""],
                 self::DB_FLIGHTS     =>        ["localhost",
                                                 "ems_flights",
                                                 "root",
                                                 ""]
                ];
```
The PDO instence associated with the DB can be called by calling the connection method with the name which was assigned to the DB as arg.

### Model.php
Inherite for each of the nessecery tables. The model folder has some examples.
