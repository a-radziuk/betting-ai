<?php

namespace App\Support;

use Illuminate\Http\Request;

final class TelegramStartUpdate
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'update_id' => ['required', 'integer'],
            'message' => ['required', 'array'],
            'message.message_id' => ['required', 'integer'],
            'message.from' => ['required', 'array'],
            'message.from.id' => ['required', 'integer', 'min:1'],
            'message.from.is_bot' => ['sometimes', 'boolean'],
            'message.from.first_name' => ['sometimes', 'string'],
            'message.from.last_name' => ['sometimes', 'string', 'nullable'],
            'message.from.username' => ['sometimes', 'string', 'nullable'],
            'message.from.language_code' => ['sometimes', 'string', 'nullable'],
            'message.chat' => ['sometimes', 'array'],
            'message.date' => ['sometimes', 'integer'],
            'message.text' => ['sometimes', 'string', 'nullable'],
        ];
    }

    public static function telegramUserId(Request $request): int
    {
        $validated = $request->validate(self::rules());

        return (int) data_get($validated, 'message.from.id');
    }
}
