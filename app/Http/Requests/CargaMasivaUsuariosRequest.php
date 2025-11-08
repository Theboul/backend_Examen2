<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CargaMasivaUsuariosRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Verificar que el usuario tiene permisos de Usuario_Create y Carga_Masiva
        // TODO: Implementar lógica de permisos cuando esté disponible
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'archivo' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx,xls',
                'max:10240', // 10MB máximo
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo.required' => 'Debe proporcionar un archivo CSV o Excel para la carga masiva.',
            'archivo.file' => 'El archivo proporcionado no es válido.',
            'archivo.mimes' => 'El archivo debe ser de tipo CSV (.csv, .txt) o Excel (.xlsx, .xls).',
            'archivo.max' => 'El archivo no debe superar los 10MB.',
        ];
    }
}
