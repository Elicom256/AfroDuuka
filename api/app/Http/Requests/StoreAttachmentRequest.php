<?php

namespace App\Http\Requests;

use App\Rules\RealFileContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240',

                // What the browser said it is.
                'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt',

                // What the bytes actually are.
                //
                // mimes: alone reads the MIME type from the client's own multipart
                // headers, which the uploader chooses. A PHP script announced as
                // image/png passed it and was stored, then served back by
                // AttachmentController. This rule reads the file's magic bytes
                // instead, so a mislabelled upload is refused at the door rather than
                // discovered later.
                new RealFileContent,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'That file type is not supported.',
        ];
    }
}
