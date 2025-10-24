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
     * Sert le fichier JSON de documentation (api-docs.json)
     */
    public function docs(Request $request)
    {
        $fileSystem = new Filesystem();
        $filePath = storage_path('api-docs/api-docs.json');

        // Regénérer la doc si nécessaire
        if (config('l5-swagger.generate_always')) {
            try {
                $generator = $this->generatorFactory->make('default');
                $generator->generateDocs();
            } catch (\Exception $e) {
                Log::error($e);
                abort(500, "Impossible de générer la documentation Swagger : " . $e->getMessage());
            }
        }

        if (! $fileSystem->exists($filePath)) {
            abort(404, "Fichier de documentation introuvable : $filePath");
        }

        $content = $fileSystem->get($filePath);

        return response($content, 200)
            ->header('Content-Type', 'application/json');
    }

    /**
     * Affiche Swagger UI
     */
    public function api(Request $request)
    {
        $documentation = 'default';

        // ✅ Force une URL HTTPS complète pour le JSON
        $urlToDocs = route('l5-swagger.docs', [], true);

        return view('l5-swagger::index', [
            'documentation' => $documentation,
            'secure' => true, // ✅ Forcer HTTPS
            'urlToDocs' => $urlToDocs,
            'operationsSorter' => config('l5-swagger.operations_sort'),
            'configUrl' => config('l5-swagger.additional_config_url') ?? null,
            'validatorUrl' => config('l5-swagger.validator_url'),
            'useAbsolutePath' => config('l5-swagger.documentations.default.paths.use_absolute_path', true),
        ]);
    }

    /**
     * Affiche la page de redirection OAuth2 (facultatif)
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
