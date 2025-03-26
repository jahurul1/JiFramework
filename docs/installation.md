# Installation

Setting up JiFramework is straightforward. Follow the steps below to install the framework and get started with your application.

## Prerequisites

* **PHP 7.4** or higher
* **Composer** installed globally on your system

## Installation Options

### Option 1: Install via Composer (Recommended)

1. **Create a New Project Directory (If Needed)**

   If you're starting a new project:

   ```bash
   mkdir my_project
   cd my_project
   ```

2. **Initialize Composer**

   Run the following command to initialize Composer in your project directory:

   ```bash
   composer init
   ```

   Follow the prompts to generate a `composer.json` file for your project.

3. **Require JiFramework via Composer**

   Add JiFramework to your project by running:

   ```bash
   composer require jahurul1/ji-framework
   ```

   This command will install JiFramework and its dependencies into the `vendor` directory.

4. **Include the Autoloader in Your Project**

   In your application's entry point (e.g., `index.php`), include Composer's autoloader:

   ```php
   require __DIR__ . '/vendor/autoload.php';
   ```

   This line ensures that all JiFramework classes and any other dependencies are properly loaded.

### Option 2: Manual Installation

1. **Download the Framework**

   Download the latest version of JiFramework from the [GitHub repository](https://github.com/jiframework/jiframework).

2. **Extract the Files**

   Extract the downloaded ZIP file to a directory in your project.

3. **Include the Autoloader**

   In your application's entry point (e.g., `index.php`), include JiFramework's autoloader:

   ```php
   require_once 'path/to/jiframework/autoload.php';
   ```

   Replace `'path/to/jiframework'` with the actual path to your JiFramework installation.

## Verifying Installation

To verify that JiFramework is installed correctly, create a simple test file named `test.php` with the following code:

```php
<?php
require __DIR__ . '/vendor/autoload.php';  // Adjust path if using manual installation

use JIFramework\Core\App\App;

// Initialize the app
$app = new App();

// If no errors occur, JiFramework is installed correctly
echo "JiFramework is installed successfully!";
```

Run this file from your command line or web browser to check that no errors appear.

## Creating a Basic Project Structure

While JiFramework doesn't enforce a strict directory structure, we recommend the following organization for your project:

```
my_project/
├── config/                 # Configuration files
├── public/                 # Publicly accessible files
│   └── index.php           # Main entry point
├── src/                    # Application source code
│   ├── Controllers/        # Controller classes
│   ├── Models/             # Model classes
│   └── Views/              # View templates
├── vendor/                 # Dependencies (managed by Composer)
└── composer.json           # Composer configuration
```

Create a simple `public/index.php` file:

```php
<?php
// Define the application root directory
define('APP_ROOT', dirname(__DIR__));

// Require the Composer autoloader
require APP_ROOT . '/vendor/autoload.php';

// Initialize the app
$app = new JIFramework\Core\App\App();

// Your application code starts here
// ...

// Example route handling
$uri = $_SERVER['REQUEST_URI'];

if ($uri === '/') {
    echo "Welcome to my JiFramework application!";
} else {
    echo "Page not found!";
}
```

## Next Steps

Now that you have installed JiFramework, proceed to the [Configuration](configuration.md) section to learn how to configure the framework for your application's needs. 

```php
// Initialize the framework
require 'vendor/autoload.php';

// Create application instance
$app = new JiFramework\Core\App\App();

// Your application code here
``` 
