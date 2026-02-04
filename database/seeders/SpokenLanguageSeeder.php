<?php

namespace Database\Seeders;

use App\Models\SpokenLanguage;
use Illuminate\Database\Seeder;

class SpokenLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            // Major European languages
            ['name' => 'French', 'code' => 'fr', 'native_name' => 'Français'],
            ['name' => 'English', 'code' => 'en', 'native_name' => 'English'],
            ['name' => 'German', 'code' => 'de', 'native_name' => 'Deutsch'],
            ['name' => 'Spanish', 'code' => 'es', 'native_name' => 'Español'],
            ['name' => 'Italian', 'code' => 'it', 'native_name' => 'Italiano'],
            ['name' => 'Portuguese', 'code' => 'pt', 'native_name' => 'Português'],
            ['name' => 'Dutch', 'code' => 'nl', 'native_name' => 'Nederlands'],
            ['name' => 'Polish', 'code' => 'pl', 'native_name' => 'Polski'],
            ['name' => 'Romanian', 'code' => 'ro', 'native_name' => 'Română'],
            ['name' => 'Greek', 'code' => 'el', 'native_name' => 'Ελληνικά'],
            ['name' => 'Hungarian', 'code' => 'hu', 'native_name' => 'Magyar'],
            ['name' => 'Czech', 'code' => 'cs', 'native_name' => 'Čeština'],
            ['name' => 'Swedish', 'code' => 'sv', 'native_name' => 'Svenska'],
            ['name' => 'Danish', 'code' => 'da', 'native_name' => 'Dansk'],
            ['name' => 'Finnish', 'code' => 'fi', 'native_name' => 'Suomi'],
            ['name' => 'Norwegian', 'code' => 'no', 'native_name' => 'Norsk'],

            // African languages
            ['name' => 'Swahili', 'code' => 'sw', 'native_name' => 'Kiswahili'],
            ['name' => 'Lingala', 'code' => 'ln', 'native_name' => 'Lingála'],
            ['name' => 'Kikongo', 'code' => 'kg', 'native_name' => 'Kikongo'],
            ['name' => 'Tshiluba', 'code' => 'lu', 'native_name' => 'Tshiluba'],
            ['name' => 'Wolof', 'code' => 'wo', 'native_name' => 'Wolof'],
            ['name' => 'Yoruba', 'code' => 'yo', 'native_name' => 'Yorùbá'],
            ['name' => 'Hausa', 'code' => 'ha', 'native_name' => 'Hausa'],
            ['name' => 'Igbo', 'code' => 'ig', 'native_name' => 'Igbo'],
            ['name' => 'Amharic', 'code' => 'am', 'native_name' => 'አማርኛ'],
            ['name' => 'Zulu', 'code' => 'zu', 'native_name' => 'isiZulu'],
            ['name' => 'Afrikaans', 'code' => 'af', 'native_name' => 'Afrikaans'],
            ['name' => 'Bambara', 'code' => 'bm', 'native_name' => 'Bamanankan'],
            ['name' => 'Fula', 'code' => 'ff', 'native_name' => 'Fulfulde'],
            ['name' => 'Twi', 'code' => 'tw', 'native_name' => 'Twi'],
            ['name' => 'Ewe', 'code' => 'ee', 'native_name' => 'Eʋegbe'],
            ['name' => 'Shona', 'code' => 'sn', 'native_name' => 'chiShona'],
            ['name' => 'Kinyarwanda', 'code' => 'rw', 'native_name' => 'Kinyarwanda'],
            ['name' => 'Kirundi', 'code' => 'rn', 'native_name' => 'Ikirundi'],
            ['name' => 'Somali', 'code' => 'so', 'native_name' => 'Soomaaliga'],
            ['name' => 'Tigrinya', 'code' => 'ti', 'native_name' => 'ትግርኛ'],
            ['name' => 'Oromo', 'code' => 'om', 'native_name' => 'Oromoo'],

            // Asian languages
            ['name' => 'Chinese (Mandarin)', 'code' => 'zh', 'native_name' => '中文'],
            ['name' => 'Japanese', 'code' => 'ja', 'native_name' => '日本語'],
            ['name' => 'Korean', 'code' => 'ko', 'native_name' => '한국어'],
            ['name' => 'Vietnamese', 'code' => 'vi', 'native_name' => 'Tiếng Việt'],
            ['name' => 'Thai', 'code' => 'th', 'native_name' => 'ภาษาไทย'],
            ['name' => 'Hindi', 'code' => 'hi', 'native_name' => 'हिन्दी'],
            ['name' => 'Bengali', 'code' => 'bn', 'native_name' => 'বাংলা'],
            ['name' => 'Urdu', 'code' => 'ur', 'native_name' => 'اردو'],
            ['name' => 'Tamil', 'code' => 'ta', 'native_name' => 'தமிழ்'],
            ['name' => 'Telugu', 'code' => 'te', 'native_name' => 'తెలుగు'],
            ['name' => 'Tagalog', 'code' => 'tl', 'native_name' => 'Tagalog'],
            ['name' => 'Indonesian', 'code' => 'id', 'native_name' => 'Bahasa Indonesia'],
            ['name' => 'Malay', 'code' => 'ms', 'native_name' => 'Bahasa Melayu'],

            // Middle Eastern languages
            ['name' => 'Arabic', 'code' => 'ar', 'native_name' => 'العربية'],
            ['name' => 'Hebrew', 'code' => 'he', 'native_name' => 'עברית'],
            ['name' => 'Persian', 'code' => 'fa', 'native_name' => 'فارسی'],
            ['name' => 'Turkish', 'code' => 'tr', 'native_name' => 'Türkçe'],
            ['name' => 'Kurdish', 'code' => 'ku', 'native_name' => 'Kurdî'],

            // Slavic languages
            ['name' => 'Russian', 'code' => 'ru', 'native_name' => 'Русский'],
            ['name' => 'Ukrainian', 'code' => 'uk', 'native_name' => 'Українська'],
            ['name' => 'Serbian', 'code' => 'sr', 'native_name' => 'Српски'],
            ['name' => 'Croatian', 'code' => 'hr', 'native_name' => 'Hrvatski'],
            ['name' => 'Bulgarian', 'code' => 'bg', 'native_name' => 'Български'],
            ['name' => 'Slovak', 'code' => 'sk', 'native_name' => 'Slovenčina'],
            ['name' => 'Slovenian', 'code' => 'sl', 'native_name' => 'Slovenščina'],

            // Other languages
            ['name' => 'Haitian Creole', 'code' => 'ht', 'native_name' => 'Kreyòl Ayisyen'],
            ['name' => 'Latin', 'code' => 'la', 'native_name' => 'Latina'],
        ];

        foreach ($languages as $language) {
            SpokenLanguage::firstOrCreate(
                ['code' => $language['code']],
                $language
            );
        }
    }
}
