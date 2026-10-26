<?php

declare(strict_types=1);

use App\Models\Commit;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::fake(['api.github.com/repos/laravel/framework' => Http::response([
        'full_name' => 'laravel/framework',
        'description' => 'The Laravel Framework.',
        'stargazers_count' => 34981,
    ])]);
});

it('lists the commits linking to a repository or anything in it, newest first', function (): void {
    $old = Commit::factory()->create(['body' => 'Look: https://github.com/laravel/framework.', 'created_at' => now()->subDay()]);
    $issue = Commit::factory()->create(['body' => 'https://github.com/Laravel/Framework/issues/1', 'created_at' => now()->subHour()]);
    $clone = Commit::factory()->create(['body' => 'git clone https://github.com/laravel/framework.git', 'created_at' => now()->subHours(2)]);
    Commit::factory()->create(['body' => 'https://notgithub.com/laravel/framework']);
    $reply = Commit::factory()->create(['body' => 'See github.com/laravel/framework/pull/2', 'parent_id' => $old->id]);
    Commit::factory()->create(['body' => 'https://github.com/laravel/framework-docs']);
    Commit::factory()->create(['body' => 'https://github.com/laravel/framework.js']);
    Commit::factory()->create(['body' => 'laravel/framework without a link']);

    $this->get(route('repository.show', ['laravel', 'framework']))->assertOk()->assertInertia(fn ($page) => $page
        ->component('repository/show')
        ->where('owner', 'laravel')
        ->where('repo', 'framework')
        ->where('github', ['name' => 'laravel/framework', 'description' => 'The Laravel Framework.', 'stars' => 34981])
        ->where('stats', ['commits' => 4, 'people' => 4])
        ->has('commits.data', 4)
        ->where('commits.data.0.id', $reply->id)
        ->where('commits.data.1.id', $issue->id)
        ->where('commits.data.2.id', $clone->id)
        ->where('commits.data.3.id', $old->id));
});

it('hides the commits the viewer may not see from the repository page', function (): void {
    $viewer = User::factory()->create();
    $blocked = User::factory()->create();
    $muted = User::factory()->create();
    $viewer->blocking()->attach($blocked);
    $viewer->muting()->attach($muted);
    $body = 'https://github.com/laravel/framework';
    $visible = Commit::factory()->create(['body' => $body, 'created_at' => now()->subMinute()]);
    Commit::factory()->for($blocked)->create(['body' => $body]);
    Commit::factory()->for($muted)->create(['body' => $body]);
    Commit::factory()->pending()->create(['body' => $body]);
    $own = Commit::factory()->pending()->for($viewer)->create(['body' => $body]);

    $this->actingAs($viewer)->get(route('repository.show', ['laravel', 'framework']))->assertOk()->assertInertia(fn ($page) => $page
        ->where('stats', ['commits' => 2, 'people' => 2])
        ->has('commits.data', 2)
        ->where('commits.data.0.id', $own->id)
        ->where('commits.data.1.id', $visible->id));
});

it('shows the commits without GitHub details when the repository is private or gone', function (): void {
    Http::fake(['api.github.com/repos/someone/secret' => Http::response(status: 404)]);
    Commit::factory()->create(['body' => 'https://github.com/someone/secret']);

    $this->get(route('repository.show', ['someone', 'secret']))->assertOk()->assertInertia(fn ($page) => $page
        ->where('github', null)
        ->has('commits.data', 1));

    $this->get(route('repository.show', ['someone', 'secret']))->assertOk();

    Http::assertSentCount(1);
});

it('remembers for a few minutes that GitHub did not answer', function (): void {
    Http::fake(['api.github.com/repos/down/repo' => Http::response(status: 500)]);

    $this->get(route('repository.show', ['down', 'repo']))->assertOk()->assertInertia(fn ($page) => $page
        ->where('github', null)
        ->where('stats', ['commits' => 0, 'people' => 0])
        ->has('commits.data', 0));

    $this->get(route('repository.show', ['down', 'repo']))->assertOk();

    Http::assertSentCount(1);
});

it('only takes names GitHub allows', function (): void {
    $this->get('/github/laravel/framework%20docs')->assertNotFound();
    $this->get('/github/laravel/..')->assertNotFound();
    $this->get('/github/laravel/.')->assertNotFound();
});
