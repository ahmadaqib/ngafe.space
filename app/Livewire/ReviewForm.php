<?php

namespace App\Livewire;

use App\Domain\Cafe\Models\Cafe;
use App\Domain\Cafe\Models\Category;
use App\Domain\Review\Actions\AttachPhotos;
use App\Domain\Review\Actions\EditReview;
use App\Domain\Review\Actions\SubmitReview;
use App\Domain\Review\Models\Review;
use App\Domain\Review\Support\ReviewGuards;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

final class ReviewForm extends Component
{
    use WithFileUploads;

    public Cafe $cafe;

    public int $step = 1;

    public ?int $rating = null;

    /** @var list<int|string> */
    public array $tagIds = [];

    public string $body = '';

    /** @var array<int, mixed> */
    public array $photos = [];

    public string $website = '';

    public bool $submitted = false;

    public ?string $submittedReviewId = null;

    public function mount(Cafe $cafe): void
    {
        $this->cafe = $cafe;
        $existing = Auth::user()?->reviews()->where('cafe_id', $cafe->id)->with('tags')->first();
        if ($existing) {
            $this->rating = $existing->rating;
            $this->body = $existing->body;
            $this->tagIds = $existing->tags->modelKeys();
        }
    }

    public function nextStep(): void
    {
        $this->validate($this->step === 1
            ? ['rating' => ['required', 'integer', 'between:1,5'], 'tagIds' => ['array', 'max:12']]
            : ['body' => ['required', 'string', 'min:30', 'max:5000']]);
        $this->step = min(3, $this->step + 1);
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function submit(
        SubmitReview $submitReview,
        EditReview $editReview,
        AttachPhotos $attachPhotos,
        ReviewGuards $guards,
    ): void {
        abort_unless(Auth::check(), 401);
        $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'min:30', 'max:5000'],
            'tagIds' => ['array', 'max:12'],
            'tagIds.*' => ['integer', 'exists:categories,id'],
            'photos' => ['array', 'max:4'],
            'photos.*' => ['file', 'max:10240'],
            'website' => ['nullable', 'max:0'],
        ]);
        $guards->assertHoneypotEmpty($this->website);

        $user = Auth::user();
        $existing = Review::query()->where('user_id', $user->id)->where('cafe_id', $this->cafe->id)->first();
        $review = $existing
            ? $editReview->handle($user, $existing, $this->rating, $this->body, $this->tagIds)
            : $submitReview->handle($user, $this->cafe, $this->rating, $this->body, $this->tagIds);

        if ($this->photos !== []) {
            $attachPhotos->handle($user, $review, $this->photos);
        }

        $this->submitted = true;
        $this->submittedReviewId = $review->id;
        $this->photos = [];
        $this->dispatch('review-submitted', cafeId: $this->cafe->id);
    }

    public function render()
    {
        return view('livewire.review-form', [
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'existingReview' => Auth::user()?->reviews()->where('cafe_id', $this->cafe->id)->first(),
            'submittedReview' => $this->submittedReviewId
                ? Review::query()->whereKey($this->submittedReviewId)->first()
                : null,
        ]);
    }
}
