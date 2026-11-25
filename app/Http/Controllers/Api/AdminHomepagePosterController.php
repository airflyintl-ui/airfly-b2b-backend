<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomepagePoster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminHomepagePosterController extends Controller
{
    private function cloudinaryConfig()
    {
        return [
            'cloud_name' => config('services.cloudinary.cloud_name') ?: env('CLOUDINARY_CLOUD_NAME'),
            'api_key' => config('services.cloudinary.api_key') ?: env('CLOUDINARY_API_KEY'),
            'api_secret' => config('services.cloudinary.api_secret') ?: env('CLOUDINARY_API_SECRET'),
        ];
    }

    private function cloudinarySignature(array $params, string $secret): string
    {
        ksort($params);

        $parts = [];

        foreach ($params as $key => $value) {
            $parts[] = $key . '=' . $value;
        }

        return sha1(implode('&', $parts) . $secret);
    }

    private function uploadToCloudinary($file): array
    {
        $config = $this->cloudinaryConfig();

        if (empty($config['cloud_name']) || empty($config['api_key']) || empty($config['api_secret'])) {
            throw new \RuntimeException('Cloudinary configuration is missing.');
        }

        $timestamp = time();

        $params = [
            'folder' => 'airfly/homepage-posters',
            'timestamp' => $timestamp,
        ];

        $signature = $this->cloudinarySignature(
            $params,
            $config['api_secret']
        );

        $response = Http::timeout(60)
            ->attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )
            ->post(
                'https://api.cloudinary.com/v1_1/' .
                $config['cloud_name'] .
                '/image/upload',
                [
                    'api_key' => $config['api_key'],
                    'timestamp' => $timestamp,
                    'folder' => $params['folder'],
                    'signature' => $signature,
                ]
            );

        if (!$response->successful()) {
            throw new \RuntimeException('Cloudinary upload failed.');
        }

        $data = $response->json();

        if (empty($data['secure_url']) || empty($data['public_id'])) {
            throw new \RuntimeException('Invalid Cloudinary upload response.');
        }

        return [
            'image_url' => $data['secure_url'],
            'public_id' => $data['public_id'],
        ];
    }

    private function deleteFromCloudinary(?string $publicId): void
    {
        if (!$publicId) {
            return;
        }

        $config = $this->cloudinaryConfig();

        if (empty($config['cloud_name']) || empty($config['api_key']) || empty($config['api_secret'])) {
            throw new \RuntimeException('Cloudinary configuration is missing.');
        }

        $timestamp = time();

        $params = [
            'public_id' => $publicId,
            'timestamp' => $timestamp,
        ];

        $signature = $this->cloudinarySignature(
            $params,
            $config['api_secret']
        );

        $response = Http::asForm()
            ->timeout(30)
            ->post(
                'https://api.cloudinary.com/v1_1/' .
                $config['cloud_name'] .
                '/image/destroy',
                [
                    'public_id' => $publicId,
                    'api_key' => $config['api_key'],
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                ]
            );

        if (!$response->successful()) {
            throw new \RuntimeException('Cloudinary delete request failed.');
        }
    }

    public function index()
    {
        $posters = HomepagePoster::orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'posters' => $posters,
        ]);
    }

    public function publicIndex()
    {
        $posters = HomepagePoster::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'posters' => $posters,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        try {
            $uploaded = $this->uploadToCloudinary($request->file('image'));

            $poster = HomepagePoster::create([
                'title' => $validated['title'] ?? null,
                'subtitle' => $validated['subtitle'] ?? null,
                'description' => $validated['description'] ?? null,
                'image_url' => $uploaded['image_url'],
                'cloudinary_public_id' => $uploaded['public_id'],
                'is_active' => $validated['is_active'] ?? true,
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Poster uploaded successfully.',
                'poster' => $poster,
            ], 201);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Poster upload failed. Please check Cloudinary configuration.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $poster = HomepagePoster::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|nullable|string|max:255',
            'subtitle' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string|max:2000',
            'image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        try {
            $oldPublicId = $poster->cloudinary_public_id;

            if ($request->hasFile('image')) {
                $uploaded = $this->uploadToCloudinary($request->file('image'));

                $poster->image_url = $uploaded['image_url'];
                $poster->cloudinary_public_id = $uploaded['public_id'];
            }

            foreach (['title', 'subtitle', 'description', 'is_active', 'sort_order'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $poster->{$field} = $validated[$field];
                }
            }

            $poster->save();

            if ($request->hasFile('image') && $oldPublicId) {
                try {
                    $this->deleteFromCloudinary($oldPublicId);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Poster updated successfully.',
                'poster' => $poster,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Poster update failed.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $poster = HomepagePoster::findOrFail($id);

        try {
            $this->deleteFromCloudinary($poster->cloudinary_public_id);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Poster could not be deleted from image storage.',
            ], 500);
        }

        $poster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Poster deleted successfully.',
        ]);
    }
}
