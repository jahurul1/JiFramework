# QueryBuilder Component

The QueryBuilder component provides a fluent interface for database operations, making it easy to build and execute SQL queries in an object-oriented way. It supports various database operations like select, insert, update, delete, and transactions.

## Basic Usage

```php
// Access via the App container
$db = $app->db;

// Or create a new instance directly
$db = new \JiFramework\Core\Database\QueryBuilder();

// Execute a simple query
$users = $db->table('users')
            ->where('status', 'active')
            ->orderBy('created_at', 'DESC')
            ->get();
```

## Available Methods

### Selecting Data

```php
// Select all columns
$users = $db->table('users')->get();

// Select specific columns
$names = $db->table('users')
            ->select('id', 'name', 'email')
            ->get();

// Get the first record
$user = $db->table('users')
           ->where('id', 1)
           ->first();

// Count records
$count = $db->table('users')
            ->where('status', 'active')
            ->count();
```

### Filtering Data

```php
// Basic where clause
$users = $db->table('users')
            ->where('status', 'active')
            ->get();

// Where with custom operator
$users = $db->table('users')
            ->where('age', '>', 18)
            ->get();

// Multiple where clauses (AND)
$users = $db->table('users')
            ->where('status', 'active')
            ->where('age', '>', 18)
            ->get();

// OR where clause
$users = $db->table('users')
            ->where('status', 'active')
            ->orWhere('status', 'pending')
            ->get();

// Where IN clause
$users = $db->table('users')
            ->whereIn('id', [1, 2, 3])
            ->get();

// Where NULL
$users = $db->table('users')
            ->whereNull('deleted_at')
            ->get();
```

### Inserting Data

```php
// Insert a single record
$success = $db->table('users')
              ->insert([
                  'name' => 'John Doe',
                  'email' => 'john@example.com',
                  'created_at' => date('Y-m-d H:i:s')
              ]);

// Get the last inserted ID
$id = $db->lastInsertId();
```

### Updating Data

```php
// Update records
$success = $db->table('users')
              ->where('id', 1)
              ->update([
                  'name' => 'Jane Doe',
                  'updated_at' => date('Y-m-d H:i:s')
              ]);
```

### Deleting Data

```php
// Delete records
$success = $db->table('users')
              ->where('id', 1)
              ->delete();

// Delete all records from a table
$success = $db->table('logs')->delete();
```

### Transactions

```php
// Start a transaction
$db->beginTransaction();

try {
    // Perform multiple operations
    $db->table('users')->insert(['name' => 'User 1']);
    $db->table('profiles')->insert(['user_id' => $db->lastInsertId()]);
    
    // Commit the transaction
    $db->commit();
} catch (Exception $e) {
    // Rollback the transaction on error
    $db->rollBack();
    throw $e;
}
```

### Raw Queries

```php
// Execute a raw query
$result = $db->query("SELECT * FROM users WHERE id = ?", [1]);

// Execute a raw non-SELECT query
$success = $db->exec("UPDATE users SET status = 'inactive' WHERE last_login < ?", 
                     [date('Y-m-d', strtotime('-6 months'))]);
```

## Advanced Features

### Joins

```php
$posts = $db->table('posts')
            ->join('users', 'users.id', '=', 'posts.user_id')
            ->select('posts.*', 'users.name as author')
            ->get();
```

### Grouping and Having

```php
$counts = $db->table('posts')
             ->select('category_id', 'COUNT(*) as post_count')
             ->groupBy('category_id')
             ->having('post_count', '>', 3)
             ->get();
```

### Ordering and Limiting

```php
$recentPosts = $db->table('posts')
                  ->orderBy('created_at', 'DESC')
                  ->limit(10)
                  ->get();

// Skip and take (pagination)
$page2 = $db->table('posts')
            ->orderBy('created_at', 'DESC')
            ->skip(10)  // Skip first 10
            ->take(10)  // Take next 10
            ->get();
```

## Connection Management

```php
// Create with specific connection
$config = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'myapp',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
];

$db = new \JiFramework\Core\Database\QueryBuilder($config);

// Switch connection at runtime
$db->setConnection($newConnectionConfig);
``` 