<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Maize\Markable\Models\Bookmark;
use Maize\Markable\Models\Like;

class MarkController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Toggle like on an article.
     */
    public function toggleLike(Article $article)
    {
        $user = auth()->user();

        // This check is now redundant due to auth middleware, but keeping for safety
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            Like::toggle($article, $user);

            return response()->json([
                'isLiked' => Like::has($article, $user),
                'likesCount' => Like::count($article),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error toggling like on article', [
                'article_id' => $article->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la modification du like'
            ], 500);
        }
    }

    /**
     * Toggle bookmark on an article.
     */
    public function toggleBookmark(Article $article)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        Bookmark::toggle($article, $user);

        return response()->json([
            'isBookmarked' => Bookmark::has($article, $user),
        ]);
    }
}
