import { Head } from '@inertiajs/react';
import { Star } from 'lucide-react';
import { CommitFeed, EmptyFeed } from '@/components/commit-feed';
import { GitHubIcon } from '@/components/github-icon';
import AppLayout from '@/layouts/app-layout';
import type { Commit, CursorPaginated } from '@/types';

type Props = {
    owner: string;
    repo: string;
    github: { name: string; description: string | null; stars: number } | null;
    stats: { commits: number; people: number };
    commits: CursorPaginated<Commit>;
};

export default function Show({ owner, repo, github, stats, commits }: Props) {
    const name = github?.name ?? `${owner}/${repo}`;

    return (
        <>
            <Head title={name} />

            <header className="border-b px-4 py-4 sm:px-6">
                <h1 className="flex items-center gap-2 font-mono text-lg font-semibold wrap-anywhere">
                    <GitHubIcon aria-hidden className="size-5 shrink-0" />
                    <a
                        href={`https://github.com/${name}`}
                        target="_blank"
                        rel="noopener noreferrer nofollow"
                        className="hover:underline"
                    >
                        {name}
                    </a>
                </h1>

                {github?.description && (
                    <p className="mt-1 text-[15px] text-muted-foreground">
                        {github.description}
                    </p>
                )}

                <p className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                    {github && (
                        <span className="inline-flex items-center gap-1 tabular-nums">
                            <Star aria-hidden className="size-3.5" />
                            {github.stars.toLocaleString()}
                        </span>
                    )}
                    {stats.commits > 0 && (
                        <span>
                            {stats.commits} commit
                            {stats.commits === 1 ? '' : 's'} by{' '}
                            {stats.people === 1
                                ? '1 person'
                                : `${stats.people} people`}
                        </span>
                    )}
                </p>
            </header>

            <CommitFeed
                commits={commits}
                end="That's every commit about this repository."
                empty={
                    <EmptyFeed command={`fatal: no commits about ${name}`}>
                        Link to it in a commit and it shows up here.
                    </EmptyFeed>
                }
            />
        </>
    );
}

Show.layout = AppLayout;
