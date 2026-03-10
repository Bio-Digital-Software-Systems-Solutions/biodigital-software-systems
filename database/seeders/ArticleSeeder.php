<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereHas('roles', function ($query): void {
            $query->whereIn('name', ['admin', 'writer', 'super-admin']);
        })->get();

        if ($users->isEmpty()) {
            throw new \Exception('Users with writer or admin roles must be seeded before articles');
        }

        $categories = Category::all();
        if ($categories->isEmpty()) {
            $this->call(CategorySeeder::class);
            $categories = Category::all();
        }

        $tags = Tag::all();
        if ($tags->isEmpty()) {
            $this->call(TagSeeder::class);
            $tags = Tag::all();
        }

        $articles = [
            [
                'title' => 'Getting Started with Laravel 11: A Complete Guide',
                'content' => 'Laravel has revolutionized PHP development with its elegant syntax and powerful features. In this comprehensive guide, we\'ll explore the latest features in Laravel 11 and how to build modern web applications. We\'ll cover routing, middleware, controllers, models, and much more. Laravel\'s ecosystem provides tools for every aspect of web development, from authentication to real-time broadcasting.',
                'is_featured' => true,
                'published_at' => Carbon::now()->subDays(5),
                'category' => 'Programming',
                'tags' => ['Laravel', 'PHP', 'Tutorial'],
            ],
            [
                'title' => 'Building Reactive UIs with React and TypeScript',
                'content' => 'React with TypeScript offers a powerful combination for building type-safe, scalable user interfaces. This article covers component design patterns, hooks usage, state management with Context API, and best practices for organizing large React applications. We\'ll also explore testing strategies and performance optimization techniques.',
                'is_featured' => true,
                'published_at' => Carbon::now()->subDays(10),
                'category' => 'Web Development',
                'tags' => ['React', 'JavaScript', 'Tutorial', 'Best Practices'],
            ],
            [
                'title' => 'Database Design Best Practices for Modern Applications',
                'content' => 'Effective database design is crucial for application performance and scalability. This article explores normalization principles, indexing strategies, query optimization, and when to use NoSQL vs SQL databases. We\'ll also cover migration strategies and data modeling for complex business requirements.',
                'is_featured' => false,
                'published_at' => Carbon::now()->subDays(15),
                'category' => 'Technology',
                'tags' => ['Database', 'Best Practices', 'Performance'],
            ],
            [
                'title' => 'Implementing CI/CD Pipelines with Docker and GitHub Actions',
                'content' => 'Continuous Integration and Deployment are essential for modern software development. Learn how to set up automated testing, building, and deployment pipelines using Docker containers and GitHub Actions. We\'ll cover environment management, security considerations, and monitoring deployed applications.',
                'is_featured' => true,
                'published_at' => Carbon::now()->subDays(7),
                'category' => 'DevOps',
                'tags' => ['DevOps', 'Tutorial', 'Best Practices'],
            ],
            [
                'title' => 'API Security: Protecting Your REST APIs',
                'content' => 'API security is paramount in today\'s interconnected world. This comprehensive guide covers authentication strategies, rate limiting, input validation, CORS configuration, and monitoring API usage. We\'ll explore JWT tokens, OAuth2, and implementing proper error handling without exposing sensitive information.',
                'is_featured' => false,
                'published_at' => Carbon::now()->subDays(12),
                'category' => 'Technology',
                'tags' => ['API', 'Security', 'Best Practices'],
            ],
            [
                'title' => 'Modern JavaScript: ES2024 Features and Beyond',
                'content' => 'JavaScript continues to evolve with new features that make development more efficient and code more readable. Explore the latest ECMAScript features including optional chaining, nullish coalescing, top-level await, and new array methods. Learn how to use these features in your projects today.',
                'is_featured' => false,
                'published_at' => Carbon::now()->subDays(20),
                'category' => 'Web Development',
                'tags' => ['JavaScript', 'Programming', 'Tutorial'],
            ],
            [
                'title' => 'Testing Strategies for Laravel Applications',
                'content' => 'Comprehensive testing ensures your Laravel applications work correctly and remain maintainable. This guide covers unit testing, feature testing, database testing, and API testing using PHPUnit and Laravel\'s testing tools. Learn about test-driven development, mocking, and creating reliable test suites.',
                'is_featured' => true,
                'published_at' => Carbon::now()->subDays(3),
                'category' => 'Programming',
                'tags' => ['Laravel', 'Testing', 'Best Practices', 'PHP'],
            ],
            [
                'title' => 'User Experience Design Principles for Developers',
                'content' => 'Understanding UX principles helps developers create more intuitive and user-friendly applications. This article covers usability heuristics, accessibility guidelines, responsive design principles, and user research methods. Learn how to think like a user and design interfaces that delight.',
                'is_featured' => false,
                'published_at' => Carbon::now()->subDays(25),
                'category' => 'Design',
                'tags' => ['UI/UX', 'Best Practices'],
            ],
            [
                'title' => 'Performance Optimization for Web Applications',
                'content' => 'Website performance directly impacts user experience and business metrics. Explore techniques for optimizing loading times, reducing bundle sizes, implementing caching strategies, and monitoring performance metrics. We\'ll cover both frontend and backend optimization techniques.',
                'is_featured' => true,
                'published_at' => Carbon::now()->subDays(8),
                'category' => 'Technology',
                'tags' => ['Performance', 'Best Practices', 'Tutorial'],
            ],
            [
                'title' => 'Mobile-First Development with Progressive Web Apps',
                'content' => 'Progressive Web Apps combine the best of web and mobile applications. Learn how to build PWAs that work offline, send push notifications, and provide native-like experiences across all devices. We\'ll explore service workers, manifest files, and responsive design strategies.',
                'is_featured' => false,
                'published_at' => Carbon::now()->subDays(18),
                'category' => 'Web Development',
                'tags' => ['Mobile', 'JavaScript', 'Tutorial'],
            ],
        ];

        foreach ($articles as $articleData) {
            $category = $categories->where('name', $articleData['category'])->first();
            $articleTags = $articleData['tags'];

            unset($articleData['category'], $articleData['tags']);

            $articleData['slug'] = Str::slug($articleData['title']);
            $articleData['user_id'] = $users->random()->id;
            $articleData['category_id'] = $category->id;

            $article = Article::create($articleData);

            // Attach tags
            foreach ($articleTags as $tagName) {
                $tag = $tags->where('name', $tagName)->first();
                if ($tag) {
                    $article->tags()->attach($tag->id);
                }
            }
        }
    }
}
