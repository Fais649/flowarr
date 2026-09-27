<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LibraryResource;
use App\Jobs\ScanLibrary;
use App\LibraryStatus;
use App\Models\Library;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LibrariesController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return LibraryResource::collection(Library::with('workers')->orderBy('id')->get());
    }

    public function show(Library $library): LibraryResource
    {
        return new LibraryResource($library->load('workers'));
    }

    public function scan(Library $library): JsonResponse
    {
        if ($library->status !== LibraryStatus::SCANNING) {
            $library->update(['status' => LibraryStatus::PENDING_SCAN]);
            ScanLibrary::dispatch($library->id);
        }

        return (new LibraryResource($library->refresh()->load('workers')))
            ->response()
            ->setStatusCode(202);
    }
}
