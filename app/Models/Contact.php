<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Contact extends Model
{
    public const FIELDS = [
        'name' => 'Name', 'company' => 'Firma', 'email' => 'E-Mail', 'phone' => 'Telefon',
        'address' => 'Anschrift', 'city' => 'Ort', 'postal_code' => 'PLZ', 'country' => 'Land',
        'category' => 'Branche', 'website' => 'Website',
    ];

    public const PERMISSIONS = ['unknown' => 'Keine Freigabe', 'allowed' => 'Freigegeben', 'blocked' => 'Abgemeldet / gesperrt'];

    protected $fillable = ['name', 'company', 'email', 'phone', 'address', 'city', 'postal_code', 'country', 'website', 'category', 'additional_data', 'mail_permission'];

    protected $casts = ['additional_data' => 'array'];

    public static function clean(array $input): array
    {
        $fields = Arr::only($input, array_keys(self::FIELDS));
        foreach ($fields as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $fields[$key] = $value === '' ? null : $value;
            }
        }
        $fields['email'] ??= null;
        if (is_string($fields['email'])) {
            $fields['email'] = strtolower($fields['email']);
        }

        return $fields;
    }

    public static function fieldRules(): array
    {
        $rules = array_fill_keys(array_keys(self::FIELDS), ['nullable', 'string', 'max:255']);
        $rules['name'] = 'required|string|max:255';
        $rules['email'] = 'nullable|email:rfc|max:254';
        $rules['website'] = 'nullable|url:http,https|max:255';

        return $rules;
    }

    public static function matchImported(array $fields)
    {
        if ($fields['email']) {
            return static::whereRaw('LOWER(email) = ?', [$fields['email']]);
        }
        $query = static::whereNull('email')->where('name', $fields['name']);
        foreach (['company', 'address', 'phone', 'city'] as $key) {
            $query->where($key, ($fields[$key] ?? '') !== '' ? $fields[$key] : null);
        }

        return $query;
    }
}
