<?php

declare(strict_types=1);

namespace App\Queries;

use App\Services\GitHub;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Cache;

/**
 * @phpstan-type Repository array{name: string, description: string|null, stars: int}
 */
final readonly class GitHubRepositoryQuery
{
    private const int Ttl = 86400;

    private const int FailureTtl = 300;

    public function __construct(private GitHub $github)
    {
        //
    }

    /**
     * A public repository's name as GitHub writes it, its description, and its stars, cached for a day. Null when it is
     * private, gone, or GitHub didn't answer, which is remembered for a few minutes so an outage doesn't call GitHub
     * again on every view.
     *
     * @return Repository|null
     */
    public function get(string $owner, string $repository): ?array
    {
        $key = 'github-repository:'.mb_strtolower("{$owner}/{$repository}");

        /** @var Repository|false|null $found */
        $found = Cache::get($key);

        if ($found === null) {
            try {
                $response = $this->github->request()->get("repos/{$owner}/{$repository}");
                $found = $response->notFound() ? false : [
                    'name' => $response->throw()->fluent()->string('full_name')->value(),
                    'description' => $response->fluent()->get('description'),
                    'stars' => $response->fluent()->integer('stargazers_count'),
                ];
            } catch (HttpClientException) {
                Cache::put($key, false, self::FailureTtl);

                return null;
            }

            Cache::put($key, $found, self::Ttl);
        }

        return $found === false ? null : $found;
    }
}
