<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view articles');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Article $article): bool
    {
        // Les articles publiés sont visibles par tous
        if ($article->published_at) {
            return $user->can('view articles');
        }

        // Les brouillons sont visibles par l'auteur ou ceux qui peuvent éditer
        return $user->id === $article->user_id || $user->can('edit articles');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create articles');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Article $article): bool
    {
        // Seul l'auteur ou quelqu'un avec la permission d'éditer peut modifier
        return $user->id === $article->user_id
            || $user->can('edit articles')
            || $user->hasRole(['admin', 'SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Article $article): bool
    {
        // Seul l'auteur ou quelqu'un avec la permission de supprimer peut supprimer
        return $user->id === $article->user_id
            || $user->can('delete articles')
            || $user->hasRole(['admin', 'SuperAdmin', 'Admin']);
    }

    /**
     * Determine whether the user can publish the article.
     */
    public function publish(User $user, Article $article): bool
    {
        // Seul l'auteur avec permission ou quelqu'un qui peut publier
        return ($user->id === $article->user_id && $user->can('publish articles'))
            || $user->can('publish articles');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Article $article): bool
    {
        return $user->can('delete articles');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Article $article): bool
    {
        return $user->can('delete articles');
    }
}
