<?php

namespace Upon\Mlang\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Upon\Mlang\Facades\MLang;
use Upon\Mlang\Tests\Fixtures\Product;
use Upon\Mlang\Tests\TestCase;

class DoctorCommandTest extends TestCase
{
    public function test_reports_missing_columns_before_migrate(): void
    {
        $this->artisan('mlang:doctor')
            ->expectsOutputToContain('missing the row_id/iso columns')
            ->assertExitCode(1);
    }

    public function test_healthy_report_after_migrate(): void
    {
        Artisan::call('mlang:migrate');
        MLang::forModel(Product::class)->createMultiLanguage(
            attributes: ['price' => 1],
            languages: ['en', 'fr', 'de'],
            translatedAttributes: ['en' => ['name' => 'A'], 'fr' => ['name' => 'B'], 'de' => ['name' => 'C']]
        );

        $report = MLang::doctor();

        $this->assertTrue($report['healthy']);
        $this->assertSame(100.0, $report['models'][Product::class]['coverage']);
        $this->artisan('mlang:doctor')->expectsOutputToContain('All checks passed.')->assertExitCode(0);
    }

    public function test_detects_orphans_unknown_iso_duplicates_and_fixes_orphans(): void
    {
        Artisan::call('mlang:migrate');
        DB::table('products')->insert([
            ['name' => 'orphan', 'iso' => 'en', 'row_id' => null],
            ['name' => 'bad iso', 'iso' => 'xx', 'row_id' => 10],
            ['name' => 'dup1', 'iso' => 'fr', 'row_id' => 20],
            ['name' => 'dup2', 'iso' => 'fr', 'row_id' => 20],
        ]);

        $report = MLang::doctor();
        $problems = implode("\n", $report['models'][Product::class]['problems']);

        $this->assertFalse($report['healthy']);
        $this->assertStringContainsString('1 rows have no row_id', $problems);
        $this->assertStringContainsString('1 rows have an iso', $problems);
        $this->assertStringContainsString('1 (row_id, iso) pairs', $problems);

        $fixed = MLang::doctor(fix: true);
        $this->assertSame(1, $fixed['models'][Product::class]['orphans_fixed']);
        $this->assertSame(0, DB::table('products')->whereNull('row_id')->count());
    }

    public function test_reports_unknown_class(): void
    {
        config()->set('mlang.models', ['App\\Models\\Nope']);

        $this->assertSame(['Class App\\Models\\Nope does not exist'], MLang::doctor()['models']['App\\Models\\Nope']['problems']);
    }
}
