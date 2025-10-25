<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use App\Models\AcademicYear;

class AcademicYearMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $user = $request->getAttribute('user');

        if ($user) {
            $academicYearModel = new AcademicYear();
            $currentYear = $academicYearModel->getCurrentYear($user->id);

            // Inject current academic year into request attributes
            $request = $request->withAttribute('current_academic_year', $currentYear);
        }

        return $handler->handle($request);
    }
}
