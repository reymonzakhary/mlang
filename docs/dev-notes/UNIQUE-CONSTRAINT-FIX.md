# Unique Constraint Handling - Fixed

## Problem

When running `php artisan mlang:generate Category nl`, you encountered:

```
SQLSTATE[23505]: Unique violation: 7 ERROR: duplicate key value violates unique constraint "categories_slug_unique"
DETAIL: Key (slug)=(yposnxpfbf) already exists.
```

The system was trying to insert records with the same unique values (like `slug`) that already existed in the database.

## Root Cause

The `MLangGenerateCommand` had its own `getUniqueIndexes()` method that:
1. Detected unique columns correctly for PostgreSQL
2. BUT only added a random 3-character suffix (`_` + random)
3. **Did NOT verify** if the new value was also unique
4. Could generate duplicate slugs like `test_abc`, `test_abc` (same random chars)

## Solution Implemented

### 1. Use TranslationHelper for Unique Handling

Updated `MLangGenerateCommand.php` to use the centralized `TranslationHelper`:

```php
// OLD (lines 94-99):
foreach ($uniqueIndexesFound as $key) {
    if (isset($row[$key])) {
        $row[$key] = $row[$key] . '_' . Str::random(3);  // Not verified!
    }
}

// NEW (line 93):
$row = TranslationHelper::handleUniqueConstraints($model, $row, $language);
```

### 2. Improved handleUniqueConstraints() Logic

Enhanced `TranslationHelper::handleUniqueConstraints()` to:

#### Smart Suffix Strategy:
1. **First attempt**: Append language code (e.g., `slug` → `slug-nl`)
2. **If exists**: Add incremental number (e.g., `slug-nl-1`, `slug-nl-2`, etc.)
3. **After 100 attempts**: Use random string as last resort (`slug-nl-abc12`)

#### Verification Loop:
```php
while ($attempt < $maxAttempts) {
    // Check if value exists in database
    $query = DB::table($table)->where($column, $newValue);

    if (!$query->exists()) {
        // Value is unique! Use it.
        break;
    }

    // Try next variation
    $newValue = $originalValue . '-' . $language . ($attempt > 0 ? '-' . $attempt : '');
    $attempt++;
}
```

## How It Works Now

### Example 1: First Translation
```php
// English record exists:
slug: "my-category"

// Generate Dutch translation:
php artisan mlang:generate Category nl

// Result:
slug: "my-category-nl"  ✅ (language suffix added)
```

### Example 2: Multiple Translations
```php
// Existing records:
slug: "test" (en)
slug: "test-nl" (nl) - already exists!

// Generate Dutch translation again:
php artisan mlang:generate Category nl

// Result:
slug: "test-nl-1"  ✅ (incremented because test-nl exists)
```

### Example 3: Many Duplicates
```php
// If test-nl, test-nl-1, test-nl-2... test-nl-99 all exist:

// Result:
slug: "test-nl-a7f3b"  ✅ (random suffix as last resort)
```

## Database Compatibility

Works with both MySQL and PostgreSQL unique index detection:

### PostgreSQL Query:
```sql
SELECT a.attname, i.relname
FROM pg_index i
JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
WHERE i.indrelid = 'categories'::regclass
AND i.indisunique AND NOT i.indisprimary
```

### MySQL Query:
```sql
SHOW INDEXES FROM `categories` WHERE Non_unique = 0
```

## Testing

### Test Case 1: Basic Generation
```bash
# Create a category in English
php artisan tinker
>>> Category::create(['name' => 'Test', 'slug' => 'test', 'iso' => 'en', 'row_id' => 1])

# Generate Dutch translation
php artisan mlang:generate Category nl

# Expected result:
# Dutch record created with slug: "test-nl"
```

### Test Case 2: Duplicate Handling
```bash
# Already have: slug = "test" (en) and "test-nl" (nl)
php artisan mlang:generate Category nl

# Expected result:
# Skips existing nl translation OR creates "test-nl-1" if needed
```

### Test Case 3: Verify in Database
```sql
-- PostgreSQL
SELECT id, name, slug, iso, row_id FROM categories ORDER BY row_id, iso;

-- You should see:
-- row_id=1, iso=en, slug=test
-- row_id=1, iso=nl, slug=test-nl
```

## Files Changed

1. **MLangGenerateCommand.php** (/Users/reymonzakhary/Projects/mlang/packages/src/Console/MLangGenerateCommand.php:12,93)
   - Added `use TranslationHelper`
   - Replaced unique handling logic with `TranslationHelper::handleUniqueConstraints()`
   - Removed duplicate `getUniqueIndexes()` method

2. **TranslationHelper.php** (/Users/reymonzakhary/Projects/mlang/packages/src/Helpers/TranslationHelper.php:165-226)
   - Completely rewrote `handleUniqueConstraints()` method
   - Added verification loop with smart suffix strategy
   - Handles composite unique indexes correctly

## Benefits

✅ **No More Duplicate Key Errors**: Verifies uniqueness before inserting
✅ **Predictable Suffixes**: Uses language codes (`-nl`, `-fr`) instead of random
✅ **User-Friendly**: Easy to identify which records need proper translation
✅ **Scalable**: Handles up to 100 variations before using random suffixes
✅ **Database Agnostic**: Works with MySQL, PostgreSQL, and others

## Important Notes

### Temporary Suffixes
The suffixes like `-nl` or `-nl-1` are **temporary placeholders**. They indicate:
- The record needs proper translation by a human
- The system auto-generated a unique value to avoid errors

### User Action Required
After running `mlang:generate`, you should:
1. Review records with suffixes in the database
2. Update them with proper translated values:
```php
Category::where('slug', 'like', '%-nl%')->get()->each(function($cat) {
    $cat->update(['slug' => 'proper-dutch-slug']);
});
```

## Troubleshooting

### Still Getting Unique Errors?
1. Clear cache: `php artisan cache:clear`
2. Regenerate autoloader: `composer dump-autoload`
3. Check if unique index includes `iso` column (should skip it)
4. Verify TranslationHelper is being used:
```bash
php artisan tinker
>>> TranslationHelper::getUniqueIndexes('categories')
# Should return array of unique column names
```

### Verify the Fix
```bash
# Before: Would fail with duplicate key error
php artisan mlang:generate Category nl

# After: Should succeed with suffixed values
php artisan mlang:generate Category nl
# Output: "X translations have been generated for categories successfully."
```
