<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class HandleGoogleCallback
{
    public function handle(SocialiteUser $googleUser): User
    {
        $googleSub = (string) $googleUser->getId();

        $user = User::query()->firstOrNew(['google_sub' => $googleSub]);
        $isNewUser = ! $user->exists;
        $user->fill([
            'email' => $googleUser->getEmail(),
            'role' => 'user',
            'status' => 'active',
        ]);

        if ($isNewUser) {
            $user->display_alias_seed = Str::random(64);
        }

        $user->saveOrFail();

        return $user;
    }
}
