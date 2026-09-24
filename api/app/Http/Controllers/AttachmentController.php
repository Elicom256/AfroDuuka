<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Product images
    |--------------------------------------------------------------------------
    */

    public function storeProduct(Product $product, StoreAttachmentRequest $request)
    {
        $this->authorize('update', $product);

        return response()->json([
            'message' => 'Image uploaded successfully',
            'attachment' => $this->store($product, $request),
        ], 201);
    }

    public function indexProduct(Product $product)
    {
        $this->authorize('view', $product);

        return response()->json([
            'message' => 'Attachments fetched',
            'attachments' => $product->attachments()->orderByDesc('id')->get(),
        ], 200);
    }

    public function destroyProduct(Product $product, Attachment $attachment)
    {
        $this->authorize('update', $product);
        $this->destroy($product, $attachment);

        return response()->json(['message' => 'Attachment deleted'], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Customer documents
    |--------------------------------------------------------------------------
    */

    public function storeCustomer(Customer $customer, StoreAttachmentRequest $request)
    {
        return response()->json([
            'message' => 'Document uploaded successfully',
            'attachment' => $this->store($customer, $request),
        ], 201);
    }

    public function indexCustomer(Customer $customer)
    {
        return response()->json([
            'message' => 'Attachments fetched',
            'attachments' => $customer->attachments()->orderByDesc('id')->get(),
        ], 200);
    }

    public function destroyCustomer(Customer $customer, Attachment $attachment)
    {
        $this->destroy($customer, $attachment);

        return response()->json(['message' => 'Attachment deleted'], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Supplier documents
    |--------------------------------------------------------------------------
    */

    public function storeSupplier(Supplier $supplier, StoreAttachmentRequest $request)
    {
        return response()->json([
            'message' => 'Document uploaded successfully',
            'attachment' => $this->store($supplier, $request),
        ], 201);
    }

    public function indexSupplier(Supplier $supplier)
    {
        return response()->json([
            'message' => 'Attachments fetched',
            'attachments' => $supplier->attachments()->orderByDesc('id')->get(),
        ], 200);
    }

    public function destroySupplier(Supplier $supplier, Attachment $attachment)
    {
        $this->destroy($supplier, $attachment);

        return response()->json(['message' => 'Attachment deleted'], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    private function store(Product|Customer|Supplier $parent, StoreAttachmentRequest $request): Attachment
    {
        $file = $request->file('file');
        $kind = str_starts_with($file->getMimeType() ?? '', 'image/') ? 'image' : 'document';
        $modelType = $parent instanceof Product ? 'products' : ($parent instanceof Customer ? 'customers' : 'suppliers');

        $path = Storage::disk('public')->putFile(
            "attachments/{$modelType}/{$parent->business_id}",
            $file,
        );

        return $parent->attachments()->create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'kind' => $kind,
        ]);
    }

    private function destroy(Product|Customer|Supplier $parent, Attachment $attachment): void
    {
        if (
            $attachment->attachable_type !== $parent::class
            || $attachment->attachable_id !== $parent->id
        ) {
            abort(404, 'Attachment not found for this record');
        }

        Storage::disk($attachment->disk ?: 'public')->delete($attachment->path);
        $attachment->delete();
    }
}