# small-swoole-db

small-swoole-db is a memory db based on OpenSwoole\Table.

The heavy advantage of OpenSwoole\Table is heavy speed access : you can read about 2 million records in one second. 

This package intend to allow you to easy manage OpenSwoole\Table.

## Registries

### Table registry

Table registry is your entry point to small-swoole-db

You can :
- create a table in memory and register it
- get a table already registered
- destroy a table in memory
- persist a table to :
  - Json file
  - Redis
  - Mysql
- Load a table previously persisted

#### Create a table

To create a table, use registry method :
```php
use \Small\SwooleDb\Registry\TableRegistry;

$testTable = TableRegistry::getInstance()->createTable('testTable', 128);
```

- The first param is the table name in registry. Use it to recall table from other parts of code.
- The second param is the max number of rows in the table

Once the table is created in registry, you can add columns :

Only these type are accepted :
- ColumnType::string
- ColumnType::float
- ColumnType::int

Note the size param is required, except for flot type

```php
use \Small\SwooleDb\Registry\TableRegistry;
use \Small\SwooleDb\Core\Column;
use \Small\SwooleDb\Core\Enum\ColumnType;

TableRegistry::getInstance()->getTable('testTable')
    ->addColumn(new Column('firstname', ColumnType::string, 256))
    ->addColumn(new Column('credit', ColumnType::float))
    ->addColumn(new Column('rank', ColumnType::int, 8))
```

Now we have added the columns, we can create in memory :
```php
use \Small\SwooleDb\Registry\TableRegistry;

$success = TableRegistry::getInstance()->getTable('testTable')->create();
```

The table is now ready to use as a OpenSwoole\Table object :
```php
use \Small\SwooleDb\Registry\TableRegistry;

$table = TableRegistry::getInstance()->getTable('testTable')
$table->set(0, ['franck', 12.5, 11]);
$table->set(1, ['joe', 55.2, 26]);
```

See [OpenSwoole documentation for Table](https://openswoole.com/docs/modules/swoole-table)

#### Destroy a table

Once you don't need anymore table, you can destroy it to free all associated memory.

```php
use \Small\SwooleDb\Registry\TableRegistry;

$table = TableRegistry::getInstance()->destroy('testTable');
```

This will destroy table and remove it from registry

#### Persistence

When you need persistence for table, you can store table to a json file.

Soon, I will develop redis and mysql persistence but for now, use default persistence :

```php
use \Small\SwooleDb\Registry\TableRegistry;

TableRegistry::getInstance()->persist('testTable');
```

This will store table definition in a json file stored in /var/lib/small-swoole-db/data/testTable.json

To reload table from disk (at server restart for example) :
```php
use \Small\SwooleDb\Registry\TableRegistry;

TableRegistry::getInstance()->loadFromChannel('testTable');
```

This will restore table in registry and memory with all data from last persist.

### ParamRegistry

If you want, you can use ParamRegistry to change :
- location of /var/lib direcoty :
```php
use Small\SwooleDb\Registry\ParamRegistry;
use Small\SwooleDb\Registry\Enum\ParamType;

ParamRegistry::getInstance()->set(ParamType::varLibDir, '/home/some-user');
```
- data dir name :
```php
use Small\SwooleDb\Registry\ParamRegistry;
use Small\SwooleDb\Registry\Enum\ParamType;

ParamRegistry::getInstance()->set(ParamType::dataDirName, 'persistence');
```

In this example, the testTable table will be stored in :
```
/home/some-user/testTable.json
```

## Testing

You must have docker installed to run tests.

To build unit-test container and run test suite, use command :
```bash
$ bin/test --build
```

Once the container is build, you can quickly run test using :
```bash
$ bin/test
```