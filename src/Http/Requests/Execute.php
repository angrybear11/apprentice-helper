<?php

namespace Voronoi\Apprentice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Execute extends FormRequest
{
    public function rules()
    {
        return [
            'command' => "required|string|max:1024",
        ];
    }
}
