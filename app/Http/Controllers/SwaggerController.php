<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;

class SwaggerController extends Controller
{
    /**
     * Retourne le JSON de la documentation Swagger
     */
    public function docs(Request $request)
    {
        $fileSystem = new Filesystem();
        $filePath = storage_path('api-docs/api-docs.json');

        if (! $fileSystem->exists($filePath)) {
            abort(404, 'Fichier de documentation introuvable.');
        }

        $content = $fileSystem->get($filePath);

        return response()->json(json_decode($content, true));
    }

    /**
     * Affiche Swagger UI
     */
    public function api(Request $request)
    {
        // URL dynamique vers le JSON
        $urlToDocs = route('l5-swagger.docs');

        return view('l5-swagger::index', [
            'documentation' => 'default',
            'secure' => $request->secure(),
            'urlToDocs' => $urlToDocs,
            'operationsSorter' => null,
            'configUrl' => null,
            'validatorUrl' => null,
            'useAbsolutePath' => true,
        ]);
    }
}
