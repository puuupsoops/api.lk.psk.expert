<?php
namespace API\v1\Middleware;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';


/**
 * Установки для политики CORS
 */
class CORSMiddleware
{
    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Server\RequestHandlerInterface $handler
    ): \Psr\Http\Message\ResponseInterface {
/*
        if ($request->isOptions()
            && $request->hasHeader('Origin')
            && $request->hasHeader('Access-Control-Request-Method')) {
            return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', '*')
                ->withHeader("Access-Control-Allow-Methods", '*');
        } else {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Expose-Headers', '*');
            return $next($request, $response);
        }
        */

        if ($request->getMethod() === 'OPTION'
            && $request->hasHeader('Origin')
            && $request->hasHeader('Access-Control-Request-Method')
        ) {
            $response = $handler->handle($request);
            return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', '*')
                ->withHeader("Access-Control-Allow-Methods", '*');
        }else{
            $response = $handler->handle($request);
            return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Expose-Headers', '*');
        }

        /*
                 $response = $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin','*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
         */
    }
}