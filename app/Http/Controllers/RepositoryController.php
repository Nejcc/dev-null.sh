<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Queries\GitHubRepositoryQuery;
use App\Queries\RepositoryCommitsQuery;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every commit about a GitHub repository on one page, with the repository as GitHub describes it.
 */
final readonly class RepositoryController
{
    public function show(string $owner, string $repository, #[CurrentUser] ?User $user, RepositoryCommitsQuery $commits, GitHubRepositoryQuery $github): Response
    {
        return Inertia::render('repository/show', [
            'owner' => $owner,
            'repo' => $repository,
            'github' => $github->get($owner, $repository),
            'stats' => $commits->stats($owner, $repository, $user),
            'commits' => Inertia::scroll(fn () => $commits->builder($owner, $repository, $user)->cursorPaginate(20)),
        ]);
    }
}
