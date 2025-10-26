<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Traits\ApiResponseTrait;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\UnauthorizedException;

class Handler extends ExceptionHandler
{
    use ApiResponseTrait;
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson()) {
            if ($exception instanceof NotFoundException) {
                return $this->errorResponse($exception->getMessage(), $exception->getCode(), null, $exception->getErrorCode());
            }
            if ($exception instanceof ValidationException) {
                return $this->errorResponse($exception->getMessage(), $exception->getCode(), null, $exception->getErrorCode());
            }
            if ($exception instanceof UnauthorizedException) {
                return $this->errorResponse($exception->getMessage(), $exception->getCode(), null, $exception->getErrorCode());
            }
        }

        return parent::render($request, $exception);
    }
}
