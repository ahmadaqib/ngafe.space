<?php

namespace Tests\Support;

use App\Domain\Identity\Models\User;
use Illuminate\Testing\TestResponse;

trait AssertsNoPii
{
    public function assertNoPii(TestResponse $response, User ...$users): void
    {
        $body = $response->getContent();
        foreach ($users as $user) {
            $this->assertStringNotContainsString((string) $user->email, $body);
            if (filled($user->google_sub)) {
                $this->assertStringNotContainsString((string) $user->google_sub, $body);
            }
            $this->assertStringNotContainsString('"user_id":'.$user->id, $body);
        }
    }
}
