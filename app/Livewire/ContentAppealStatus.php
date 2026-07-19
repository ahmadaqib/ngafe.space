<?php

namespace App\Livewire;

use App\Domain\Moderation\Actions\SubmitContentAppeal;
use App\Domain\Moderation\Models\ContentAppeal;
use Livewire\Component;

final class ContentAppealStatus extends Component
{
    public ContentAppeal $appeal;

    public string $email = '';

    public string $reason = '';

    public bool $submitted = false;

    public function appealOnce(SubmitContentAppeal $action): void
    {
        $this->validate(['email' => ['required', 'email:rfc'], 'reason' => ['required', 'string', 'min:30', 'max:5000']]);
        $action->appealOnce($this->appeal, $this->email, $this->reason);
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.content-appeal-status')->layout('components.layout.app', ['title' => 'Status Keberatan Konten']);
    }
}
