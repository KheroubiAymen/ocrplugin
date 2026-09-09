<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\UserRoute;
use App\Services\ResourceService;
use App\Services\UserRoutes\UserRouteService;
use SwissDidata\Ocr\OcrPackage;

return new class extends Migration
{
    public function up(): void
    {
        $routeData = [
            'name'       => 'ocr_extract_text',
            'type'       => 'POST',
            'is_enabled' => true,
            'is_public'  => false,
            'sub_path'   => 'ocr_extract_text',
            'code'       => OcrPackage::routeCode(),
        ];

        // `migrate` disables permission checks app-wide (App\Listeners\CommandStartingListener),
        // so UserRouteService's authorize() calls pass here even though nobody is logged in.
        $existing = UserRoute::where('name', $routeData['name'])->first();

        $service = $existing
            ? app()->make(UserRouteService::class, [
                'data'             => $routeData,
                'operation'        => ResourceService::UPDATE_OPERATION,
                'resourceToUpdate' => $existing,
            ])
            : app()->make(UserRouteService::class, ['data' => $routeData]);

        $service->consume();
    }

    public function down(): void
    {
        UserRoute::where('name', 'ocr_extract_text')->delete();
    }
};
