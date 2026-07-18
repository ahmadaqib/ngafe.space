<?php

namespace Tests\Feature;

use App\Domain\Review\Exceptions\ReviewLimitExceeded;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    public function test_domain_exception_is_a_safe_json_response_with_request_id(): void
    {
        Route::get('/_test/domain-error', fn () => throw new ReviewLimitExceeded())->name('test.domain-error');
        $this->getJson('/_test/domain-error')->assertUnprocessable()->assertJson(['message' => 'Kamu sudah pernah mengulas cafe ini.'])->assertHeader('X-Request-Id');
    }

    public function test_non_domain_exception_has_a_generic_json_message(): void
    {
        Route::get('/_test/unexpected-error', fn () => throw new \RuntimeException('private failure'));
        $this->getJson('/_test/unexpected-error')->assertStatus(500)->assertJson(['message' => 'Ada yang error di kami, bukan di kamu…'])->assertDontSee('private failure');
    }
}
