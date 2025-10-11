<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Maize\Markable\Models\Bookmark;
use Maize\Markable\Models\Like;

class MarkController extends Controller
{
    /**
     * Toggle like on an article.
     */
    public function toggleLike(Article $article)
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        Like::toggle($article, $user);

        return response()->json([
            'isLiked' => Like::has($article, $user),
            'likesCount' => Like::count($article),
        ]);
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
