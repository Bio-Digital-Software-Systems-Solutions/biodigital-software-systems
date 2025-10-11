<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\Library;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure categories exist
        $categories = [
            'Programming',
            'Web Development',
            'Data Science',
            'DevOps',
            'Project Management',
            'Design',
            'Business',
            'Technology',
        ];

        foreach ($categories as $categoryName) {
            Category::firstOrCreate(['name' => $categoryName]);
        }

        $libraries = Library::all();
        if ($libraries->isEmpty()) {
            $this->call(LibrarySeeder::class);
            $libraries = Library::all();
        }

        $books = [
            [
                'title' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
                'author' => 'Robert C. Martin',
                'isbn' => '9780132350884',
                'description' => 'A guide to writing clean, maintainable code with practical examples and best practices.',
                'rental_price' => 15.99,
                'max_rental_days' => 30,
                'stock_quantity' => 5,
                'category' => 'Programming',
            ],
            [
                'title' => 'The Pragmatic Programmer',
                'author' => 'David Thomas, Andrew Hunt',
                'isbn' => '9780201616224',
                'description' => 'Essential reading for software developers, covering practical programming techniques.',
                'rental_price' => 12.99,
                'max_rental_days' => 30,
                'stock_quantity' => 3,
                'category' => 'Programming',
            ],
            [
                'title' => 'JavaScript: The Good Parts',
                'author' => 'Douglas Crockford',
                'isbn' => '9780596517748',
                'description' => 'A concise guide to the best features of JavaScript programming language.',
                'rental_price' => 11.99,
                'max_rental_days' => 21,
                'stock_quantity' => 4,
                'category' => 'Web Development',
            ],
            [
                'title' => 'Learning React: Modern Patterns for Developing React Apps',
                'author' => 'Alex Banks, Eve Porcello',
                'isbn' => '9781492051718',
                'description' => 'Comprehensive guide to building modern React applications.',
                'rental_price' => 18.99,
                'max_rental_days' => 30,
                'stock_quantity' => 6,
                'category' => 'Web Development',
            ],
            [
                'title' => 'Python for Data Analysis',
                'author' => 'Wes McKinney',
                'isbn' => '9781491957660',
                'description' => 'Data wrangling with pandas, NumPy, and IPython.',
                'rental_price' => 16.99,
                'max_rental_days' => 30,
                'stock_quantity' => 4,
                'category' => 'Data Science',
            ],
            [
                'title' => 'The DevOps Handbook',
                'author' => 'Gene Kim, Jez Humble, Patrick Debois, John Willis',
                'isbn' => '9781942788003',
                'description' => 'A guide to implementing DevOps practices in technology organizations.',
                'rental_price' => 17.99,
                'max_rental_days' => 45,
                'stock_quantity' => 3,
                'category' => 'DevOps',
            ],
            [
                'title' => 'Scrum: The Art of Doing Twice the Work in Half the Time',
                'author' => 'Jeff Sutherland',
                'isbn' => '9781847941107',
                'description' => 'Introduction to the Scrum framework for agile project management.',
                'rental_price' => 13.99,
                'max_rental_days' => 30,
                'stock_quantity' => 5,
                'category' => 'Project Management',
            ],
            [
                'title' => 'Don\'t Make Me Think',
                'author' => 'Steve Krug',
                'isbn' => '9780321965516',
                'description' => 'A common sense approach to web usability and user experience design.',
                'rental_price' => 14.99,
                'max_rental_days' => 21,
                'stock_quantity' => 4,
                'category' => 'Design',
            ],
            [
                'title' => 'The Lean Startup',
                'author' => 'Eric Ries',
                'isbn' => '9780307887894',
                'description' => 'How today\'s entrepreneurs use continuous innovation to create radically successful businesses.',
                'rental_price' => 15.99,
                'max_rental_days' => 30,
                'stock_quantity' => 6,
                'category' => 'Business',
            ],
            [
                'title' => 'Designing Data-Intensive Applications',
                'author' => 'Martin Kleppmann',
                'isbn' => '9781449373320',
                'description' => 'The big ideas behind reliable, scalable, and maintainable systems.',
                'rental_price' => 22.99,
                'max_rental_days' => 45,
                'stock_quantity' => 3,
                'category' => 'Technology',
            ],
            [
                'title' => 'You Don\'t Know JS: Scope & Closures',
                'author' => 'Kyle Simpson',
                'isbn' => '9781449335588',
                'description' => 'Deep dive into JavaScript scope and closures concepts.',
                'rental_price' => 10.99,
                'max_rental_days' => 21,
                'stock_quantity' => 4,
                'category' => 'Web Development',
            ],
            [
                'title' => 'The Phoenix Project',
                'author' => 'Gene Kim, Kevin Behr, George Spafford',
                'isbn' => '9780988262508',
                'description' => 'A novel about IT, DevOps, and helping your business win.',
                'rental_price' => 16.99,
                'max_rental_days' => 30,
                'stock_quantity' => 5,
                'category' => 'DevOps',
            ],
        ];

        foreach ($books as $bookData) {
            $category = Category::where('name', $bookData['category'])->first();
            unset($bookData['category']);

            $bookData['category_id'] = $category->id;

            Book::create($bookData);
        }
    }
}
