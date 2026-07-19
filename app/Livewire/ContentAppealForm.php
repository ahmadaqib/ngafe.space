<?php

namespace App\Livewire;

use App\Domain\Moderation\Actions\SubmitContentAppeal;
use App\Domain\Review\Models\Review;
use Livewire\Component;

final class ContentAppealForm extends Component
{
    public Review $review;

    public string $name = '';

    public string $email = '';

    public string $reason = '';

    public bool $submitted = false;

    public function submit(SubmitContentAppeal $submitContentAppeal): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'reason' => ['required', 'string', 'min:30', 'max:5000'],
        ]);
        $submitContentAppeal->handle($this->review, $this->name, $this->email, $this->reason);
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.content-appeal-form')->layout('components.layout.app', ['title' => 'Keberatan atas Konten']);
    }
}
