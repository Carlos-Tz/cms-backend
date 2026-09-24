<?php

/*
|--------------------------------------------------------------------------
| Spanish validation messages
|--------------------------------------------------------------------------
|
| Only the rules this API actually uses. Anything missing falls back to the
| framework's English strings (`app.fallback_locale`), so add a key here when
| you introduce a new rule.
|
*/

return [
    'array' => 'El campo :attribute debe ser una lista.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'gt' => [
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
    ],
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El valor seleccionado en :attribute no es válido.',
    'max' => [
        'file' => 'El campo :attribute no debe pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe tener más de :max caracteres.',
    ],
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'numeric' => 'El campo :attribute debe ser un número.',
    'present' => 'El campo :attribute debe estar presente.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'uploaded' => 'El campo :attribute no se pudo subir. Intenta de nuevo o usa un archivo más ligero.',
    'url' => 'El campo :attribute debe ser una URL válida.',

    'custom' => [],

    'attributes' => [],
];
