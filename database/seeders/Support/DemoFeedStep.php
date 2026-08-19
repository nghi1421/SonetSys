<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Core\Auth\Domain\Models\User;
use App\Modules\Feed\Application\DTOs\CreatePostData;
use App\Modules\Feed\Application\Services\PostService;
use App\Modules\Feed\Domain\Enums\MediaType;
use App\Modules\Feed\Domain\Enums\PostVisibility;
use App\Modules\Feed\Domain\Enums\StickerKey;
use App\Modules\Feed\Domain\Models\Post;
use App\Modules\Group\Domain\Models\Group;
use App\Modules\Group\Domain\Models\GroupMember;
use Illuminate\Support\Collection;

final class DemoFeedStep
{
    private const MIN_STANDALONE_POSTS = 2;

    private const MAX_STANDALONE_POSTS = 5;

    private const MIN_GROUP_POSTS = 5;

    private const MAX_GROUP_POSTS = 10;

    private const SHARE_COUNT = 14;

    /** @var list<string> */
    private array $imagePool = [];

    /** @var list<string> */
    private array $videoPool = [];

    public function __construct(
        private readonly PostService $posts,
        private readonly DemoPostEngagement $engagement,
        private readonly DemoMediaLibrary $media,
    ) {}

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Group>  $groups
     */
    public function run(Collection $users, Collection $groups): void
    {
        $this->imagePool = $this->media->feedImages();
        $this->videoPool = $this->media->videos();

        $standalone = $this->createStandalonePosts($users);
        $this->createGroupPosts($groups, $users);
        $this->createShares($users, $standalone);

        $allPosts = Post::query()->whereNull('shared_post_id')->where('is_reel', false)->get();

        foreach ($allPosts as $post) {
            $this->engagement->addComments($post, $users);
            $this->engagement->addReactions($post, $users);
        }
    }

    /**
     * @param  Collection<int, User>  $users
     * @return Collection<int, Post>
     */
    private function createStandalonePosts(Collection $users): Collection
    {
        $created = collect();

        foreach ($users as $author) {
            $count = fake()->numberBetween(self::MIN_STANDALONE_POSTS, self::MAX_STANDALONE_POSTS);

            for ($i = 0; $i < $count; $i++) {
                $created->push($this->createOnePost($author, $users, null));
            }
        }

        return $created;
    }

    /**
     * @param  Collection<int, Group>  $groups
     * @param  Collection<int, User>  $users
     */
    private function createGroupPosts(Collection $groups, Collection $users): void
    {
        foreach ($groups as $group) {
            $memberIds = GroupMember::query()
                ->where('group_id', $group->id)
                ->where('status', 'approved')
                ->pluck('user_id');

            $members = $users->whereIn('id', $memberIds->all())->values();

            if ($members->isEmpty()) {
                continue;
            }

            $count = fake()->numberBetween(self::MIN_GROUP_POSTS, self::MAX_GROUP_POSTS);

            for ($i = 0; $i < $count; $i++) {
                $this->createOnePost($members->random(), $members, $group->id);
            }
        }
    }

    /**
     * @param  Collection<int, User>  $candidatePool
     */
    private function createOnePost(User $author, Collection $candidatePool, ?int $groupId): Post
    {
        $mentioned = $this->pickMentions($author, $candidatePool);
        $attachment = $this->pickAttachment($groupId);

        return $this->posts->create(new CreatePostData(
            body: $attachment === 'sticker' ? $this->stickerCaption() : $this->renderBody(),
            visibility: $groupId !== null ? PostVisibility::Public : $this->randomVisibility(),
            authorId: $author->id,
            groupId: $groupId,
            media: match ($attachment) {
                'image' => $this->media->asUploadedFile(fake()->randomElement($this->imagePool)),
                'video' => $this->media->asUploadedFile(fake()->randomElement($this->videoPool)),
                default => null,
            },
            mediaType: match ($attachment) {
                'sticker' => MediaType::Sticker,
                'image' => MediaType::Image,
                'video' => MediaType::Video,
                default => null,
            },
            stickerKey: $attachment === 'sticker' ? fake()->randomElement(StickerKey::cases())->value : null,
            mentionedUserIds: $mentioned,
        ));
    }

    /**
     * @return 'sticker'|'image'|'video'|null
     */
    private function pickAttachment(?int $groupId): ?string
    {
        // Stickers only make sense outside a group's more work-focused feed.
        if ($groupId === null && fake()->boolean(8)) {
            return 'sticker';
        }

        if ($this->imagePool !== [] && fake()->boolean(28)) {
            return 'image';
        }

        if ($this->videoPool !== [] && fake()->boolean(7)) {
            return 'video';
        }

        return null;
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Post>  $originals
     */
    private function createShares(Collection $users, Collection $originals): void
    {
        $eligible = $originals->filter(fn (Post $post) => $post->group_id === null
            && $post->visibility === PostVisibility::Public);

        if ($eligible->isEmpty()) {
            return;
        }

        $picks = $eligible->random(min(self::SHARE_COUNT, $eligible->count()));

        foreach ($picks as $original) {
            $sharer = $users->reject(fn (User $u) => $u->id === $original->author_id)->random();

            $this->posts->create(new CreatePostData(
                body: fake()->randomElement(DemoContent::shareCaptions()),
                visibility: PostVisibility::Public,
                authorId: $sharer->id,
                sharedPostId: $original->id,
            ));
        }
    }

    /**
     * @param  Collection<int, User>  $candidatePool
     * @return list<int>
     */
    private function pickMentions(User $author, Collection $candidatePool): array
    {
        if (! fake()->boolean(15)) {
            return [];
        }

        return $candidatePool
            ->reject(fn (User $u) => $u->id === $author->id)
            ->shuffle()
            ->take(fake()->numberBetween(1, 2))
            ->pluck('id')
            ->all();
    }

    private function randomVisibility(): PostVisibility
    {
        return fake()->randomElement([
            ...array_fill(0, 75, PostVisibility::Public),
            ...array_fill(0, 15, PostVisibility::Members),
            ...array_fill(0, 10, PostVisibility::Private),
        ]);
    }

    private function renderBody(): string
    {
        $templates = DemoContent::postTemplates();
        $category = fake()->randomElement(array_keys($templates));
        $template = fake()->randomElement($templates[$category]);
        $tags = fake()->randomElements(DemoContent::hashtagPools(), 2);

        return strtr($template, [
            '{tech}' => fake()->randomElement(DemoContent::technologies()),
            '{tool}' => fake()->randomElement(DemoContent::technologies()),
            '{company}' => fake()->randomElement(DemoContent::companies()),
            '{jobtitle}' => fake()->randomElement(DemoContent::jobTitles()),
            '{n}' => (string) fake()->numberBetween(2, 12),
            '{pct}' => (string) fake()->numberBetween(15, 70),
            '{tag1}' => $tags[0],
            '{tag2}' => $tags[1] ?? $tags[0],
        ]);
    }

    private function stickerCaption(): string
    {
        return fake()->randomElement([
            'Shipped it! 🚀',
            'What a week.',
            'Team hit the milestone.',
            "Couldn't have done it without this team.",
            'Small win, still counts.',
        ]);
    }
}
