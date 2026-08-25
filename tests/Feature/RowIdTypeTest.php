<?php

namespace Upon\Mlang\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Upon\Mlang\Columns\AddRowIdColumn;
use Upon\Mlang\Facades\MLang;
use Upon\Mlang\Helpers\RowIdHelper;
use Upon\Mlang\Tests\Fixtures\Product;
use Upon\Mlang\Tests\TestCase;

/**
 * Covers the opt-in ulid/uuid row ids, the (row_id, iso) unique index, and
 * the upgrade path from a v2-shaped table with integer row ids.
 */
class RowIdTypeTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->middleware(\Illuminate\Routing\Middleware\SubstituteBindings::class)
            ->get('/products/{product}', fn (Product $product) => $product->name);
    }

    /** Shape the table the way a v2 install left it: integer row_id, no index. */
    private function seedLegacyTable(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('row_id')->nullable()->index();
            $table->string('iso')->nullable();
        });

        DB::table('products')->insert([
            ['id' => 1, 'name' => 'Chair',  'iso' => 'en', 'row_id' => 1, 'price' => 5],
            ['id' => 2, 'name' => 'Chaise', 'iso' => 'fr', 'row_id' => 1, 'price' => 5],
            ['id' => 3, 'name' => 'Table',  'iso' => 'en', 'row_id' => 3, 'price' => 9],
        ]);
    }

    public function test_default_type_is_id_and_migrate_adds_unique_index(): void
    {
        Artisan::call('mlang:migrate');

        $this->assertSame('id', RowIdHelper::type());
        $this->assertTrue(AddRowIdColumn::isIntegerColumn('products', 'row_id'));
        $this->assertContains(AddRowIdColumn::UNIQUE_INDEX, array_column(Schema::getIndexes('products'), 'name'));
    }

    public function test_migrate_skips_unique_index_while_duplicates_exist(): void
    {
        $this->seedLegacyTable();
        DB::table('products')->insert(['id' => 4, 'name' => 'dup', 'iso' => 'fr', 'row_id' => 1]);

        $this->artisan('mlang:migrate')
            ->expectsOutputToContain('Skipped the (row_id, iso) unique index')
            ->assertExitCode(0);

        $this->assertNotContains(AddRowIdColumn::UNIQUE_INDEX, array_column(Schema::getIndexes('products'), 'name'));
        $this->assertTrue(MLang::doctor()['healthy'] === false);
    }

    public function test_ulid_type_generates_row_id_before_insert(): void
    {
        config()->set('mlang.row_id_type', 'ulid');
        Artisan::call('mlang:migrate');

        $this->assertFalse(AddRowIdColumn::isIntegerColumn('products', 'row_id'));

        $product = Product::create(['name' => 'Chair', 'price' => 5]);

        $this->assertMatchesRegularExpression('/^[0-9A-Z]{26}$/', $product->row_id);
        $this->assertNotEquals($product->getRawOriginal('id'), $product->row_id);

        MLang::forModel(Product::class)->createMultiLanguage(
            attributes: ['price' => 1],
            languages: ['en', 'fr'],
            translatedAttributes: ['en' => ['name' => 'A'], 'fr' => ['name' => 'B']]
        );
        $rowIds = Product::where('name', '!=', 'Chair')->pluck('row_id')->unique();
        $this->assertCount(1, $rowIds);
        $this->assertMatchesRegularExpression('/^[0-9A-Z]{26}$/', $rowIds->first());

        $this->assertSame('B', Product::trFind($rowIds->first(), 'fr')->name);
        $this->assertSame('A', Product::whereRow($rowIds->first(), 'en')->first()->name);
    }

    public function test_uuid_type(): void
    {
        config()->set('mlang.row_id_type', 'uuid');
        Artisan::call('mlang:migrate');

        $product = Product::create(['name' => 'Chair', 'price' => 5]);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $product->row_id);
    }

    public function test_upgrade_path_converts_legacy_integers_and_keeps_old_lookups_working(): void
    {
        $this->seedLegacyTable();

        // 3.0 -> 3.1 without changing config: nothing changes for the user
        Artisan::call('mlang:migrate');
        $this->assertTrue(AddRowIdColumn::isIntegerColumn('products', 'row_id'));
        $this->assertTrue(MLang::doctor()['healthy']);

        // Opt in to ulids: doctor tells you the column is out of date
        config()->set('mlang.row_id_type', 'ulid');
        $this->assertStringContainsString('--convert-row-id', implode(' ', MLang::doctor()['models'][Product::class]['problems']));

        $this->artisan('mlang:migrate', ['--convert-row-id' => true])
            ->expectsOutputToContain('converted 2 record groups')
            ->assertExitCode(0);

        // Schema: new row_id, integers preserved in legacy_row_id
        $this->assertFalse(AddRowIdColumn::isIntegerColumn('products', 'row_id'));
        $this->assertTrue(Schema::hasColumn('products', 'legacy_row_id'));
        $this->assertContains(AddRowIdColumn::UNIQUE_INDEX, array_column(Schema::getIndexes('products'), 'name'));

        // Data: groups intact, one ulid per group
        $chair = Product::where('name', 'Chair')->first();
        $chaise = Product::where('name', 'Chaise')->first();
        $this->assertSame($chair->row_id, $chaise->row_id);
        $this->assertMatchesRegularExpression('/^[0-9A-Z]{26}$/', $chair->row_id);
        $this->assertSame(1, (int) $chair->legacy_row_id);
        $this->assertNotSame($chair->row_id, Product::where('name', 'Table')->first()->row_id);

        // Old integer references and URLs still resolve
        $this->assertSame('Chaise', Product::trFind(1, 'fr')->name);
        $this->assertSame('Chaise', Product::trFind('1', 'fr')->name);
        $this->assertSame('Chaise', Product::trFind($chair->row_id, 'fr')->name);
        $this->get('/products/1')->assertOk()->assertSee('Chair');
        $this->get("/products/{$chair->row_id}")->assertOk()->assertSee('Chair');

        // Doctor is happy and reports the legacy count; running convert again is a no-op
        $report = MLang::doctor();
        $this->assertTrue($report['healthy']);
        $this->assertSame(3, $report['models'][Product::class]['legacy_rows']);
        $this->artisan('mlang:migrate', ['--convert-row-id' => true])->expectsOutputToContain('already converted');

        // New records get ulids and the translations relation still groups correctly
        $new = Product::create(['name' => 'Lamp', 'price' => 3]);
        $this->assertMatchesRegularExpression('/^[0-9A-Z]{26}$/', $new->row_id);
        $this->assertNull($new->legacy_row_id);
        $this->assertCount(2, $chair->translations);
    }

    public function test_convert_refuses_without_config(): void
    {
        Artisan::call('mlang:migrate');
        $this->artisan('mlang:migrate', ['--convert-row-id' => true])
            ->expectsOutputToContain("Set 'row_id_type'")
            ->assertExitCode(1);
    }

    public function test_doctor_fix_gives_generated_orphans_their_own_row_id(): void
    {
        config()->set('mlang.row_id_type', 'ulid');
        Artisan::call('mlang:migrate');
        DB::table('products')->insert([['name' => 'a', 'iso' => 'en'], ['name' => 'b', 'iso' => 'en']]);

        $report = MLang::doctor(fix: true);

        $this->assertSame(2, $report['models'][Product::class]['orphans_fixed']);
        $this->assertCount(2, Product::pluck('row_id')->unique());
    }
}
