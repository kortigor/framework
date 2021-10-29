<?php

use Illuminate\Database\{
    Capsule\Manager as Capsule,
    Migrations\Migration,
    Schema\Blueprint
};

require_once '_boot.php';

$tableName = 'settings';

Capsule::schema()->dropIfExists($tableName);

// Settings
Capsule::schema()->create($tableName, function (Blueprint $table) {
    $table->increments('id');
    $table->string('name', 50)->unique()->comment('Name');
    $table->text('value')->comment('Current value');
    $table->text('default')->comment('Default value');
    $table->string('type', 20)->comment('Value data type');
    $table->string('input', 20)->comment('Input type');
    $table->string('label', 255)->comment('Input label');
    $table->string('hint', 255)->comment('Input hint');
    $table->string('group', 50)->comment('Group');
    $table->smallInteger('order')->unsigned();
    $table->tinyInteger('status')->unsigned();
    $table->timestamps();
});

echo sprintf('Table "%s" created successfully', $tableName) . "\n";

\customer\entities\Setting::insert(
    [
        [
            'name' => 'site_title',
            'value' => 'Сайт компании ...',
            'default' => 'Компания',
            'label' => 'Заголовок сайта (title)',
            'type' => 'string',
            'input' => \customer\entities\Setting::INPUT_TEXT,
            'group' => 'main',
            'order' => 1,
            'status' => \customer\entities\Status::STATUS_ACTIVE,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ],
        [
            'name' => 'site_keywords',
            'value' => 'Компания,приборы,измерения,весы',
            'default' => 'приборы,измерения,весы',
            'label' => 'Ключевые слова (keywords)',
            'type' => 'string',
            'input' => \customer\entities\Setting::INPUT_TEXT,
            'group' => 'main',
            'order' => 2,
            'status' => \customer\entities\Status::STATUS_ACTIVE,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ],
        [
            'name' => 'site_description',
            'value' => 'Сайт компании ..., контактная информация и каталог продукции',
            'default' => 'Сайт компании ..., контактная информация и каталог продукции',
            'label' => 'Описание (description)',
            'type' => 'string',
            'input' => \customer\entities\Setting::INPUT_TEXT,
            'group' => 'main',
            'order' => 3,
            'status' => \customer\entities\Status::STATUS_ACTIVE,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ],
        [
            'name' => 'site_email',
            'value' => 'info@site.ru',
            'default' => 'info@site.ru',
            'label' => 'Email для обратной связи',
            'type' => 'string',
            'input' => \customer\entities\Setting::INPUT_TEXT,
            'group' => 'main',
            'order' => 4,
            'status' => \customer\entities\Status::STATUS_ACTIVE,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ],
        [
            'name' => 'rub_usd_percent',
            'value' => 2,
            'default' => 2,
            'label' => 'RUB → USD',
            'type' => 'float',
            'input' => \customer\entities\Setting::INPUT_TEXT,
            'group' => 'currency',
            'order' => 1,
            'status' => \customer\entities\Status::STATUS_ACTIVE,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ],
        [
            'name' => 'rub_eur_percent',
            'value' => 2,
            'default' => 2,
            'label' => 'RUB → EUR',
            'type' => 'float',
            'input' => \customer\entities\Setting::INPUT_TEXT,
            'group' => 'currency',
            'order' => 2,
            'status' => \customer\entities\Status::STATUS_ACTIVE,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ],
    ]
);

/*
Migrations documentation https://laravel.com/docs/8.x/migrations

Available Column Types
See https://laravel.com/docs/8.x/migrations#creating-columns

The schema builder contains a variety of column types that you may specify when building your tables:

Command	Description
$table->id();	Alias of $table->bigIncrements('id').
$table->foreignId('user_id');	Alias of $table->unsignedBigInteger('user_id').
$table->bigIncrements('id');	Auto-incrementing UNSIGNED BIGINT (primary key) equivalent column.
$table->bigInteger('votes');	BIGINT equivalent column.
$table->binary('data');	BLOB equivalent column.
$table->boolean('confirmed');	BOOLEAN equivalent column.
$table->char('name', 100);	CHAR equivalent column with a length.
$table->date('created_at');	DATE equivalent column.
$table->dateTime('created_at', 0);	DATETIME equivalent column with precision (total digits).
$table->dateTimeTz('created_at', 0);	DATETIME (with timezone) equivalent column with precision (total digits).
$table->decimal('amount', 8, 2);	DECIMAL equivalent column with precision (total digits) and scale (decimal digits).
$table->double('amount', 8, 2);	DOUBLE equivalent column with precision (total digits) and scale (decimal digits).
$table->enum('level', ['easy', 'hard']);	ENUM equivalent column.
$table->float('amount', 8, 2);	FLOAT equivalent column with a precision (total digits) and scale (decimal digits).
$table->geometry('positions');	GEOMETRY equivalent column.
$table->geometryCollection('positions');	GEOMETRYCOLLECTION equivalent column.
$table->increments('id');	Auto-incrementing UNSIGNED INTEGER (primary key) equivalent column.
$table->integer('votes');	INTEGER equivalent column.
$table->ipAddress('visitor');	IP address equivalent column.
$table->json('options');	JSON equivalent column.
$table->jsonb('options');	JSONB equivalent column.
$table->lineString('positions');	LINESTRING equivalent column.
$table->longText('description');	LONGTEXT equivalent column.
$table->macAddress('device');	MAC address equivalent column.
$table->mediumIncrements('id');	Auto-incrementing UNSIGNED MEDIUMINT (primary key) equivalent column.
$table->mediumInteger('votes');	MEDIUMINT equivalent column.
$table->mediumText('description');	MEDIUMTEXT equivalent column.
$table->morphs('taggable');	Adds taggable_id UNSIGNED BIGINT and taggable_type VARCHAR equivalent columns.
$table->uuidMorphs('taggable');	Adds taggable_id CHAR(36) and taggable_type VARCHAR(255) UUID equivalent columns.
$table->multiLineString('positions');	MULTILINESTRING equivalent column.
$table->multiPoint('positions');	MULTIPOINT equivalent column.
$table->multiPolygon('positions');	MULTIPOLYGON equivalent column.
$table->nullableMorphs('taggable');	Adds nullable versions of morphs() columns.
$table->nullableUuidMorphs('taggable');	Adds nullable versions of uuidMorphs() columns.
$table->nullableTimestamps(0);	Alias of timestamps() method.
$table->point('position');	POINT equivalent column.
$table->polygon('positions');	POLYGON equivalent column.
$table->rememberToken();	Adds a nullable remember_token VARCHAR(100) equivalent column.
$table->set('flavors', ['strawberry', 'vanilla']);	SET equivalent column.
$table->smallIncrements('id');	Auto-incrementing UNSIGNED SMALLINT (primary key) equivalent column.
$table->smallInteger('votes');	SMALLINT equivalent column.
$table->softDeletes('deleted_at', 0);	Adds a nullable deleted_at TIMESTAMP equivalent column for soft deletes with precision (total digits).
$table->softDeletesTz('deleted_at', 0);	Adds a nullable deleted_at TIMESTAMP (with timezone) equivalent column for soft deletes with precision (total digits).
$table->string('name', 100);	VARCHAR equivalent column with a length.
$table->text('description');	TEXT equivalent column.
$table->time('sunrise', 0);	TIME equivalent column with precision (total digits).
$table->timeTz('sunrise', 0);	TIME (with timezone) equivalent column with precision (total digits).
$table->timestamp('added_on', 0);	TIMESTAMP equivalent column with precision (total digits).
$table->timestampTz('added_on', 0);	TIMESTAMP (with timezone) equivalent column with precision (total digits).
$table->timestamps(0);	Adds nullable created_at and updated_at TIMESTAMP equivalent columns with precision (total digits).
$table->timestampsTz(0);	Adds nullable created_at and updated_at TIMESTAMP (with timezone) equivalent columns with precision (total digits).
$table->tinyIncrements('id');	Auto-incrementing UNSIGNED TINYINT (primary key) equivalent column.
$table->tinyInteger('votes');	TINYINT equivalent column.
$table->unsignedBigInteger('votes');	UNSIGNED BIGINT equivalent column.
$table->unsignedDecimal('amount', 8, 2);	UNSIGNED DECIMAL equivalent column with a precision (total digits) and scale (decimal digits).
$table->unsignedInteger('votes');	UNSIGNED INTEGER equivalent column.
$table->unsignedMediumInteger('votes');	UNSIGNED MEDIUMINT equivalent column.
$table->unsignedSmallInteger('votes');	UNSIGNED SMALLINT equivalent column.
$table->unsignedTinyInteger('votes');	UNSIGNED TINYINT equivalent column.
$table->uuid('id');	UUID equivalent column.
$table->year('birth_year');	YEAR equivalent column.

=========================================

Column Modifiers
see https://laravel.com/docs/8.x/migrations#column-modifiers

In addition to the column types listed above, there are several column "modifiers" you may use while adding a column to a database table.
For example, to make the column "nullable", you may use the nullable method:

Schema::table('users', function (Blueprint $table) {
    $table->string('email')->nullable();
});
The following list contains all available column modifiers. This list does not include the index modifiers:

Modifier	Description
->after('column')	Place the column "after" another column (MySQL)
->autoIncrement()	Set INTEGER columns as auto-increment (primary key)
->charset('utf8mb4')	Specify a character set for the column (MySQL)
->collation('utf8mb4_unicode_ci')	Specify a collation for the column (MySQL/PostgreSQL/SQL Server)
->comment('my comment')	Add a comment to a column (MySQL/PostgreSQL)
->default($value)	Specify a "default" value for the column
->first()	Place the column "first" in the table (MySQL)
->nullable($value = true)	Allows (by default) NULL values to be inserted into the column
->storedAs($expression)	Create a stored generated column (MySQL)
->unsigned()	Set INTEGER columns as UNSIGNED (MySQL)
->useCurrent()	Set TIMESTAMP columns to use CURRENT_TIMESTAMP as default value
->virtualAs($expression)	Create a virtual generated column (MySQL)
->generatedAs($expression)	Create an identity column with specified sequence options (PostgreSQL)
->always()	Defines the precedence of sequence values over input for an identity column (PostgreSQL)

=========================================

Available Index Types
see https://laravel.com/docs/8.x/migrations#creating-indexes

Each index method accepts an optional second argument to specify the name of the index.
If omitted, the name will be derived from the names of the table and column(s) used for the index, as well as the index type.

Command	Description
$table->primary('id');	Adds a primary key.
$table->primary(['id', 'subscribe_id']);	Adds composite keys.
$table->unique('email');	Adds a unique index.
$table->index('state');	Adds a plain index.
$table->spatialIndex('location');	Adds a spatial index. (except SQLite)

=========================================

Foreign Key Constraints
see https://laravel.com/docs/8.x/migrations#foreign-key-constraints

Laravel also provides support for creating foreign key constraints, which are used to force referential integrity at the database level.
For example, let's define a user_id column on the posts table that references the id column on a users table:

Schema::table('posts', function (Blueprint $table) {
    $table->unsignedBigInteger('user_id');

    $table->foreign('user_id')->references('id')->on('users');
});
Since this syntax is rather verbose, Laravel provides additional, terser methods that use convention to provide a better developer experience.
The example above could be written like so:

Schema::table('posts', function (Blueprint $table) {
    $table->foreignId('user_id')->constrained();
});
The foreignId method is an alias for unsignedBigInteger while the constrained method will use convention to determine the table and column name being referenced.
If your table name does not match the convention, you may specify the table name by passing it as an argument to the constrained method:

Schema::table('posts', function (Blueprint $table) {
    $table->foreignId('user_id')->constrained('users');
});
You may also specify the desired action for the "on delete" and "on update" properties of the constraint:

$table->foreignId('user_id')
      ->constrained()
      ->onDelete('cascade');
Any additional column modifiers must be called before constrained:

$table->foreignId('user_id')
      ->nullable()
      ->constrained();
To drop a foreign key, you may use the dropForeign method, passing the foreign key constraint to be deleted as an argument.
Foreign key constraints use the same naming convention as indexes, based on the table name and the columns in the constraint, followed by a "_foreign" suffix:

$table->dropForeign('posts_user_id_foreign');
Alternatively, you may pass an array containing the column name that holds the foreign key to the dropForeign method.
The array will be automatically converted using the constraint name convention used by Laravel's schema builder:

$table->dropForeign(['user_id']);
You may enable or disable foreign key constraints within your migrations by using the following methods:

Schema::enableForeignKeyConstraints();

Schema::disableForeignKeyConstraints();
*/