<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Commit;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The commits that link to a GitHub repository, or to anything in it like an issue or a file, newest first.
 */
final readonly class RepositoryCommitsQuery
{
    public function builder(string $owner, string $repository, ?User $user): CommitBuilder
    {
        return $this->mentioning($owner, $repository)
            ->visibleTo($user)
            ->withoutMutedAuthorsFor($user)
            ->withVisibleReplyCount($user)
            ->withReplyPreviews($user)
            ->withCardDataFor($user)
            ->with([
                'parent' => function (BelongsTo $parent) use ($user): void {
                    $parent->getQuery()->withTrashed()->visibleTo($user)->with('user');
                },
            ])
            ->newestFirst();
    }

    /**
     * @return array{commits: int, people: int}
     */
    public function stats(string $owner, string $repository, ?User $user): array
    {
        $commits = $this->mentioning($owner, $repository)->visibleTo($user)->withoutMutedAuthorsFor($user);

        return ['commits' => $commits->count(), 'people' => $commits->distinct()->count('user_id')];
    }

    /**
     * The link must be to github.com itself, and the repository's name must end where the link's does, so
     * "laravel/framework" doesn't match "laravel/framework-docs", while a ".git" clone URL, a sentence's closing period,
     * a path, or an issue number after it still count. The trigram index on the body keeps this from reading every commit.
     */
    private function mentioning(string $owner, string $repository): CommitBuilder
    {
        $pattern = '(^|[^A-Za-z0-9.-])github\.com/'.preg_quote($owner).'/'.preg_quote($repository).'(\.git)?(?![A-Za-z0-9_-]|\.[A-Za-z0-9_-])';

        return Commit::query()->whereRaw('commits.body ~* ?', [$pattern]);
    }
}
