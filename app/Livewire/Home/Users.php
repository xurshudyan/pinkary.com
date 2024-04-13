<?php

declare(strict_types=1);

namespace App\Livewire\Home;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

final class Users extends Component
{
    /**
     * The component's search query.
     */
    #[Url(as: 'q')]
    public string $query = '';

    /**
     * Indicates if the search input should be focused.
     */
    public bool $focusInput = false;

    /**
     * Renders the component.
     */
    public function render(): View
    {
        return view('livewire.home.users', [
            'users' => $this->query !== ''
                ? $this->usersByQuery()
                : $this->usersWithMostAnswers(),
        ]);
    }

    /**
     * Returns the users by query, ordered by the number of questions received.
     *
     * @return Collection<int, User>
     */
    private function usersByQuery(): Collection
    {
        return User::query()
            ->with('links')
            ->whereAny(['name', 'username'], 'like', "%{$this->query}%")
            ->withCount(['questionsReceived as answered_questions_count' => function (Builder $query): void {
                $query->whereNotNull('answer');
            }])
            ->orderBy('answered_questions_count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Returns the users with the most questions received.
     *
     * @return Collection<int, User>
     */
    private function usersWithMostAnswers(): Collection
    {
        return User::query()
            ->whereHas('links', function (Builder $query): void {
                $query->where('url', 'like', '%twitter.com%')
                    ->orWhere('url', 'like', '%github.com%');
            })
            ->with('links')
            ->withCount(['questionsReceived as answered_questions_count' => function (Builder $query): void {
                $query->whereNotNull('answer');
            }])
            ->orderBy('answered_questions_count', 'desc')
            ->limit(10)
            ->get();
    }
}