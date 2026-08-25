# Testing MLang from Web Requests

## The Issue (FIXED)

Previously, when calling MLang facade methods from controllers:
```php
MLang::forModel(Category::class)->migrate();
```

You would get:
```
CommandNotFoundException: The command "mlang:migrate" does not exist.
```

**Root Cause**: Commands were only registered when `$app->runningInConsole()` was true.

## The Fix

Updated `MlangServiceProvider.php` to always register commands:

```php
protected function registerCommands(): void
{
    // Always register commands so they can be called via Artisan::call()
    // from both console and HTTP contexts
    $this->commands([
        \Upon\Mlang\Console\MLangMigrateCommand::class,
        \Upon\Mlang\Console\MLangGenerateCommand::class,
    ]);
}
```

## Test It

### 1. From a Controller

Create a test route in `routes/web.php`:

```php
use Upon\Mlang\Facades\MLang;
use App\Models\Category;

Route::get('/test-mlang', function() {
    try {
        // Test forModel
        $result = MLang::forModel(Category::class)
            ->migrate()
            ->generate();

        return response()->json([
            'success' => true,
            'message' => 'MLang operations completed successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});
```

### 2. From Tinker

```bash
php artisan tinker
```

```php
use Upon\Mlang\Facades\MLang;
use App\Models\Category;

// This should work now
MLang::forModel(Category::class)->migrate();

// Test other methods
$stats = MLang::forModel(Category::class)->getStats();
print_r($stats);
```

### 3. From Your Controller

In `app/Http/Controllers/CategoryController.php`:

```php
use Upon\Mlang\Facades\MLang;

public function index()
{
    // This should work now without errors
    MLang::forModel(Category::class)
        ->migrate()
        ->generate();

    $categories = Category::all();
    return view('categories.index', compact('categories'));
}
```

## Important Notes

1. **Performance Warning**: Running migrations from web requests is NOT recommended for production. This should only be used for:
   - Development/testing
   - Admin panels with proper authentication
   - Background jobs/queues

2. **Better Approach for Production**:
   ```php
   // Instead of running migrations from controllers, use:
   // Command line:
   php artisan mlang:migrate

   // Or from jobs:
   use Illuminate\Support\Facades\Artisan;
   Artisan::queue('mlang:migrate');
   ```

3. **Facade Methods That DON'T Run Commands** (Safe for Controllers):
   ```php
   // These are safe to use in controllers/web requests:
   MLang::forModel(Category::class)->getStats();
   MLang::forModel(Category::class)->getCoverage();
   MLang::forModel(Category::class)->getAllTranslations(1);
   MLang::forModel(Category::class)->createMultiLanguage([...]);
   ```

## After Making Changes

Always run:
```bash
composer dump-autoload
php artisan clear-compiled
php artisan cache:clear
php artisan config:clear
```

## Verification Checklist

- [x] Commands appear in `php artisan list | grep mlang`
- [x] Commands can be called from CLI: `php artisan mlang:migrate --help`
- [x] Commands can be called via Artisan::call() from web requests
- [x] Facade methods work from controllers
- [x] No "CommandNotFoundException" errors
