<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

/**
 * Curated word banks and templates for the demo dataset — kept separate from
 * the seeding steps so the steps stay focused on orchestration, not prose.
 */
final class DemoContent
{
    /**
     * @return list<array{name: string, description: string, visibility: string}>
     */
    public static function groups(): array
    {
        return [
            [
                'name' => 'Laravel Vietnam Developers',
                'description' => 'A community for Vietnamese developers building with Laravel — share packages, discuss upgrades, and get code review from fellow backend engineers.',
                'visibility' => 'public',
            ],
            [
                'name' => 'PostgreSQL Performance Guild',
                'description' => 'Query plans, indexing strategy, and war stories from running Postgres at scale. Bring your EXPLAIN ANALYZE output.',
                'visibility' => 'public',
            ],
            [
                'name' => 'Vue.js & TypeScript Builders',
                'description' => 'Composition API patterns, component architecture, and keeping large Vue codebases maintainable with strict TypeScript.',
                'visibility' => 'public',
            ],
            [
                'name' => 'DevOps & Cloud Infrastructure',
                'description' => 'CI/CD pipelines, container orchestration, observability, and the never-ending quest to make deploys boring.',
                'visibility' => 'public',
            ],
            [
                'name' => 'System Design Study Group',
                'description' => 'Weekly discussion of distributed systems fundamentals — from load balancing to consensus algorithms. Mock interviews welcome.',
                'visibility' => 'public',
            ],
            [
                'name' => 'Open Source Maintainers Circle',
                'description' => 'For people who maintain (or want to maintain) open source projects — triage, releases, contributor onboarding, burnout prevention.',
                'visibility' => 'private',
            ],
            [
                'name' => 'Remote Engineering Careers',
                'description' => 'Salary negotiation, async communication, time-zone-friendly teams, and everything else about working remote as an engineer.',
                'visibility' => 'public',
            ],
            [
                'name' => 'Founding Engineers Roundtable',
                'description' => 'A closed group for early engineering hires at startups — architecture trade-offs, hiring your first team, technical debt triage.',
                'visibility' => 'private',
            ],
            [
                'name' => 'API Design & Documentation',
                'description' => 'REST, GraphQL, versioning strategy, and writing docs developers actually want to read.',
                'visibility' => 'public',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function technologies(): array
    {
        return [
            'Laravel', 'PostgreSQL', 'Vue.js', 'TypeScript', 'Redis', 'Docker', 'Kubernetes',
            'GraphQL', 'Tailwind CSS', 'PHP 8.4', 'Vite', 'Pinia', 'RabbitMQ', 'Elasticsearch',
            'gRPC', 'Terraform', 'GitHub Actions', 'Sanctum', 'Pest', 'Livewire',
        ];
    }

    /**
     * @return list<string>
     */
    public static function hashtagPools(): array
    {
        return [
            'laravel', 'php', 'postgresql', 'vuejs', 'typescript', 'opensource',
            'softwareengineering', 'backenddev', 'frontenddev', 'devops', 'cloudcomputing',
            'systemdesign', 'cleancode', 'codereview', 'remotework', 'careergrowth',
            'restapi', 'microservices', 'cicd', 'webperformance', 'database', 'testing',
        ];
    }

    /**
     * @return list<string>
     */
    public static function jobTitles(): array
    {
        return [
            'Senior Backend Engineer', 'Full-Stack Developer', 'DevOps Engineer',
            'Engineering Manager', 'Frontend Developer', 'Solutions Architect',
            'Site Reliability Engineer', 'Database Administrator', 'QA Automation Engineer',
            'Technical Lead', 'Founding Engineer', 'Product Engineer',
        ];
    }

    /**
     * @return list<string>
     */
    public static function companies(): array
    {
        return [
            'a fintech startup', 'an e-commerce platform', 'a logistics company',
            'a healthtech scale-up', 'a SaaS analytics tool', 'a remote-first agency',
            'an edtech product', 'a marketplace startup', 'a devtools company',
        ];
    }

    /**
     * Body templates grouped by category — {tech}/{tech2}/{tool}/{company}/{n}/{pct}
     * are replaced by the caller with faker-driven values, hashtags stay literal
     * so HashtagService::extractAndAttach() picks them up naturally.
     *
     * @return array<string, list<string>>
     */
    public static function postTemplates(): array
    {
        return [
            'tip' => [
                "Small {tech} tip that saved us hours this week: don't reach for a queue job until you've profiled the request first. Half the time the N+1 query was the real problem. #{tag1} #{tag2}",
                "PSA for anyone using {tech}: read the changelog before upgrading a minor version. We caught a breaking change in {tool} that wasn't obvious from the diff alone. #{tag1}",
                "If your {tech} test suite takes longer than your coffee break, it's time to look at test isolation. Parallelizing ours cut CI time by {pct}%. #{tag1} #{tag2}",
                'Underrated {tech} feature: composite indexes. Added one covering our two most common WHERE clauses and p95 query time dropped from 400ms to 40ms. #{tag1}',
                "Code review tip: leave comments on the 'why', not the 'what'. If the diff needs a comment explaining what it does, the code probably needs a rename instead. #{tag1} #{tag2}",
            ],
            'milestone' => [
                'Shipped {tool} v{n}.0 today after three months of rewrites. Migrated the whole ingestion pipeline to {tech} and cut infra costs by {pct}%. Huge thanks to the team. 🚀 #{tag1} #{tag2}',
                'We just crossed {n},000 requests/sec on the new {tech} gateway without falling over. Load testing paid off. #{tag1}',
                'Today marks {n} years since I wrote my first line of {tech}. Still learning something new every week. #{tag1} #{tag2}',
                'Closed out the quarter having reduced our {tech} deploy time from 25 minutes to under {n} minutes. CI/CD investment always pays off eventually. #{tag1}',
                "Our open source {tool} package just hit {n} stars. Started as an internal tool at {company}, now it's used by teams we've never even talked to. #{tag1} #{tag2}",
            ],
            'question' => [
                'Curious how other teams handle {tech} schema migrations in production with zero downtime — expand/contract, or something else? #{tag1}',
                "What's your team's stance on {tech} monorepos vs. polyrepos? We're revisiting this decision and I'd love outside perspective. #{tag1} #{tag2}",
                'Anyone running {tool} at scale willing to share their alerting thresholds? Trying to cut down on false-positive pages. #{tag1}',
                'How do you structure {tech} authorization when a single resource has five different visibility levels? Policies are getting messy. #{tag1}',
                'Genuinely asking: is anyone still hand-writing {tech} API docs, or has OpenAPI generation fully replaced that for your team? #{tag1} #{tag2}',
            ],
            'career' => [
                "Two years ago I was a {jobtitle} scared to touch production. Today I approved a migration for {n} million rows without blinking. Growth is quiet until it isn't. #{tag1} #{tag2}",
                'Best career advice I got as a junior dev: ship the boring solution first, then optimize once you have real usage data. Still true. #{tag1}',
                "Switched from a {jobtitle} role at {company} to leading a small platform team. The hardest part wasn't the tech — it was learning to delegate. #{tag1}",
                'Reminder to anyone job hunting right now: a messy GitHub history is fine. Reviewers care more about how you think through trade-offs. #{tag1} #{tag2}',
                "Mentoring a junior engineer this quarter reminded me how much of {tech} I've internalized without noticing. Teaching is the best way to find your own gaps. #{tag1}",
            ],
            'opinion' => [
                'Hot take: {tech} is still the most productive stack for small teams shipping fast, even in {n}. Boring technology wins. #{tag1} #{tag2}',
                "Unpopular opinion — 100% test coverage is a vanity metric. I'd rather have 70% coverage on the code paths that actually break. #{tag1}",
                "The best {tech} architecture decision we ever made was the one we didn't over-engineer for scale we didn't have yet. #{tag1} #{tag2}",
                "Feature flags aren't optional past a certain team size. Deploying and releasing are two different verbs and treating them the same causes incidents. #{tag1}",
            ],
            'incident' => [
                "Postmortem summary: a missing index on a {tech} join caused today's slowdown, not the {tool} upgrade everyone suspected. Wrote it up for the team wiki. #{tag1} #{tag2}",
                "Today's incident taught us that our {tech} retry logic didn't have a backoff cap. Fixed, documented, added an alert. Onward. #{tag1}",
                "Rare one today: a {tech} connection pool exhaustion under load we hadn't tested for. Runbook updated, load test added to the pipeline. #{tag1}",
            ],
            'hiring' => [
                "We're hiring a {jobtitle} to help us scale the {tech} platform. Remote-friendly, async-first team. DM if curious. #{tag1} #{tag2}",
                'Our engineering team at {company} is growing — looking for someone strong in {tech} who enjoys mentoring juniors as much as writing code. #{tag1}',
            ],
            'conference' => [
                'Just got back from a great talk on {tech} internals — taking notes on how they handle backpressure, applying it to our pipeline this sprint. #{tag1} #{tag2}',
                'Gave a lightning talk today on migrating {n} services from monolith to {tech} microservices. Slides up soon. #{tag1}',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function commentTemplates(): array
    {
        return [
            'Great write-up — bookmarking this for our next architecture review.',
            'We hit the exact same issue last quarter. Ended up with a similar fix.',
            'Congrats on shipping this! 🎉 What was the trickiest part?',
            'This matches what we saw in production too. Good to have it confirmed.',
            'Curious what your rollback plan looked like for this one.',
            'Solid point. We moved to a similar approach and never looked back.',
            'How long did the migration take end to end?',
            "Saving this thread, we're about to hit this exact problem.",
            'Appreciate you sharing the numbers, not enough people post real metrics.',
            'This is why I follow this group, genuinely useful.',
            'Did you consider the managed service instead of rolling your own?',
            'Strong agree. Boring technology really does win long-term.',
            '100% this. Took us way too long to learn the same lesson.',
            'What monitoring stack are you using to catch that kind of thing?',
            'Nice numbers! What was the baseline before the change?',
            'Following up — did this hold up under real traffic?',
            'We tried something similar and hit a wall with connection pooling. Any tips?',
            "This deserves way more attention than it's getting.",
            'Well said. Sending this to my team.',
            'Love seeing real postmortems shared publicly, more of this please.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function reelCaptions(): array
    {
        return [
            'Quick behind-the-scenes from today. 🎬',
            'Standup recap in 15 seconds.',
            'This is what shipping day looks like.',
            'Conference floor is buzzing this year.',
            'Whiteboard session that turned into the actual architecture.',
            'Office setup tour, finally finished it.',
            'Demo day nerves, but we pulled it off.',
            'Coffee, laptop, deploy. Repeat.',
            'Onsite recap — good to see the team in person.',
            'Rubber duck debugging, live footage.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function storyCaptions(): array
    {
        return [
            'On-site today.',
            'Good morning ☕',
            'Team offsite.',
            'Late one tonight.',
            'Conference badge collection growing.',
            'New desk setup.',
            'Deploy went smooth 🎉',
            'Whiteboarding session.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function shareCaptions(): array
    {
        return [
            'This is worth a read for anyone on a backend team right now.',
            'Sharing this with my team — exactly the discussion we needed.',
            'Bookmarking and sharing, too good not to pass along.',
            '100% agree with this take.',
            'Adding this to our onboarding docs.',
            '',
            '',
        ];
    }

    /**
     * @return list<string>
     */
    public static function chatLines(): array
    {
        return [
            'Hey, saw your post earlier — really solid breakdown.',
            'Do you have a few minutes this week to compare notes on our setups?',
            'Thanks for the follow! Looking forward to more of your posts.',
            'Quick question — what did you end up using for the retry logic?',
            'That group thread was great, learned a lot from the replies.',
            "Let's grab a call sometime, would love to hear more about your stack.",
            'Appreciate the detailed write-up, sending it to my team.',
            'Congrats again on the launch, well deserved.',
            'Have you tried the new version yet? Curious if it fixed the issue.',
            'Sure, happy to share more details whenever works for you.',
            'That makes sense, thanks for clarifying.',
            'No worries, take your time — this isn\'t urgent.',
            'Sounds good, talk soon!',
            'Sending over the doc now, let me know what you think.',
            'Good catch, I missed that in the review.',
        ];
    }
}
