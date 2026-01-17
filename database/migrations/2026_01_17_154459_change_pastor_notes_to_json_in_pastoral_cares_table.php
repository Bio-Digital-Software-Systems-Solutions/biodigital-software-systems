<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, convert existing text notes to JSON format with timestamp
        $existingNotes = DB::table('pastoral_cares')
            ->whereNotNull('pastor_notes')
            ->where('pastor_notes', '!=', '')
            ->get(['id', 'pastor_notes', 'updated_at']);

        foreach ($existingNotes as $record) {
            // Convert existing text to JSON array with timestamp
            $jsonNotes = json_encode([[
                'content' => $record->pastor_notes,
                'created_at' => $record->updated_at ?? now()->toISOString(),
            ]]);

            DB::table('pastoral_cares')
                ->where('id', $record->id)
                ->update(['pastor_notes' => $jsonNotes]);
        }

        // Change column type to JSON
        Schema::table('pastoral_cares', function (Blueprint $table) {
            $table->json('pastor_notes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all records with JSON notes
        $records = DB::table('pastoral_cares')
            ->whereNotNull('pastor_notes')
            ->get(['id', 'pastor_notes']);

        foreach ($records as $record) {
            $notes = json_decode($record->pastor_notes, true);
            if (is_array($notes) && ! empty($notes)) {
                // Combine all notes into text
                $textNotes = collect($notes)
                    ->map(fn ($note) => $note['content'] ?? '')
                    ->filter()
                    ->join("\n\n---\n\n");

                DB::table('pastoral_cares')
                    ->where('id', $record->id)
                    ->update(['pastor_notes' => $textNotes]);
            }
        }

        // Change column back to text
        Schema::table('pastoral_cares', function (Blueprint $table) {
            $table->text('pastor_notes')->nullable()->change();
        });
    }
};
