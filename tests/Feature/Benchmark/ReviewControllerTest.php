<?php

namespace Tests\Feature\Benchmark;

use App\Benchmark\RunStore;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;

final class ReviewControllerTest extends TestCase
{
    private string $runsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->detectEnvironment(fn (): string => 'local');
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('benchmark.review_enabled', true);
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->runsPath = sys_get_temp_dir().'/laravel-pdf-review-'.bin2hex(random_bytes(8));
        config()->set('benchmark.paths.runs', $this->runsPath);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->runsPath);

        parent::tearDown();
    }

    public function test_review_entry_can_be_saved_without_a_redirect(): void
    {
        $run = 'local-review';
        app(RunStore::class)->write($run, 'fidelity-review.json', [
            'schema_version' => 1,
            'run_id' => $run,
            'updated_at' => null,
            'entries' => [[
                'key' => 'bladepdf__local-assets__native-path__vite-css',
                'status' => null,
                'problem' => null,
                'note' => null,
                'reviewed_at' => null,
            ]],
        ]);

        $response = $this->postJson("/benchmark-review/{$run}", [
            'key' => 'bladepdf__local-assets__native-path__vite-css',
            'status' => 'pass',
            'problem' => null,
            'note' => 'CSS loaded with text/css.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('key', 'bladepdf__local-assets__native-path__vite-css');

        $review = json_decode(
            (string) file_get_contents($this->runsPath."/{$run}/fidelity-review.json"),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('pass', $review['entries'][0]['status']);
        $this->assertSame('CSS loaded with text/css.', $review['entries'][0]['note']);
        $this->assertNotNull($review['entries'][0]['reviewed_at']);
        $this->assertSame($review['entries'][0]['reviewed_at'], $review['updated_at']);
    }
}
