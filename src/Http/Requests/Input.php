<?php

namespace Voronoi\Apprentice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Input extends FormRequest
{
    public function rules()
    {
        return [
            'data' => "required|string|max:1024"
        ];
    }
}
