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
        $existingNotes = DB::table('care_services')
            ->whereNotNull('pastor_notes')
            ->where('pastor_notes', '!=', '')
            ->get(['id', 'pastor_notes', 'updated_at']);

        foreach ($existingNotes as $record) {
            // Convert existing text to JSON array with timestamp
            $jsonNotes = json_encode([[
                'content' => $record->pastor_notes,
                'created_at' => $record->updated_at ?? now()->toISOString(),
            ]]);

            DB::table('care_services')
                ->where('id', $record->id)
                ->update(['pastor_notes' => $jsonNotes]);
        }

        // Change column type to JSON
        Schema::table('care_services', function (Blueprint $table): void {
            $table->json('pastor_notes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all records with JSON notes
        $records = DB::table('care_services')
            ->whereNotNull('pastor_notes')
            ->get(['id', 'pastor_notes']);

        foreach ($records as $record) {
            $notes = json_decode((string) $record->pastor_notes, true);
            if (is_array($notes) && $notes !== []) {
                // Combine all notes into text
                $textNotes = collect($notes)
                    ->map(fn ($note): mixed => $note['content'] ?? '')
                    ->filter()
                    ->join("\n\n---\n\n");

                DB::table('care_services')
                    ->where('id', $record->id)
                    ->update(['pastor_notes' => $textNotes]);
            }
        }

        // Change column back to text
        Schema::table('care_services', function (Blueprint $table): void {
            $table->text('pastor_notes')->nullable()->change();
        });
    }
};
