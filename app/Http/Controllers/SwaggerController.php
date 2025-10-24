<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use L5Swagger\Exceptions\L5SwaggerException;
use L5Swagger\GeneratorFactory;

class SwaggerController extends Controller
{
    protected $generatorFactory;

    public function __construct(GeneratorFactory $generatorFactory)
    {
        $this->generatorFactory = $generatorFactory;
    }

    /**
     * Sert le fichier JSON de documentation.
     */
    public function docs(Request $request)
    {
        $fileSystem = new Filesystem();
        $filePath = storage_path('api-docs/api-docs.json');

        if (config('l5-swagger.generate_always')) {
            $generator = $this->generatorFactory->make('default');
            try {
                $generator->generateDocs();
            } catch (\Exception $e) {
                Log::error($e);
                abort(500, "Impossible de générer la documentation Swagger : " . $e->getMessage());
            }
        }

        if (! $fileSystem->exists($filePath)) {
            abort(404, "Impossible de localiser le fichier de documentation : $filePath");
        }

        $content = $fileSystem->get($filePath);

        return response($content, 200)
            ->header('Content-Type', 'application/json');
    }

    /**
     * Affiche la page Swagger UI.
     */
    public function api(Request $request)
    {
        $documentation = 'default';

        // ✅ Force le HTTPS absolu même sur Render
        $urlToDocs = route('l5-swagger.docs', [], true);

        return view('l5-swagger::index', [
            'documentation' => $documentation,
            'secure' => true, 
            'urlToDocs' => $urlToDocs,
            'operationsSorter' => config('l5-swagger.operations_sort'),
            'configUrl' => config('l5-swagger.additional_config_url') ?? null,
            'validatorUrl' => config('l5-swagger.validator_url'),
            'useAbsolutePath' => config('l5-swagger.documentations.default.paths.use_absolute_path', true),
        ]);
    }

    /**
     * Page de redirection OAuth2 (Swagger).
     */
    public function oauth2Callback(Request $request)
    {
        $fileSystem = new Filesystem();
        $documentation = 'default';
        $filePath = swagger_ui_dist_path($documentation, 'oauth2-redirect.html');

        if (! $fileSystem->exists($filePath)) {
            abort(404, "Fichier oauth2-redirect introuvable : $filePath");
        }

        return response($fileSystem->get($filePath), 200)
            ->header('Content-Type', 'text/html');
    }
}
