<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$route = new Route('/{club}.ics');
$routes = new RouteCollection();
$routes->add('calendar', $route);

$context = new RequestContext('/');
$matcher = new UrlMatcher($routes, $context);

try {
    $parameters = $matcher->match($reqest->getPathInfo());

    if (!isset($parameters['club'])) {
        throw new NotFoundHttpException();
    }

    $generator = new \FupaCalendar\Generator();
    $calendar = $generator->getCalendar($parameters['club']);
    $response = new Response($calender->render(), 200, [
        'Content-Type' => 'text/calendar; charset=utf-8',
        'Content-Disposition' => 'attachment; filename="' . $parameters['club'] . '.ics"'
    ]);

    $calendar->render();
} catch (NotFoundHttpException $e) {
    $response = new Response('Nicht gefunden', 404);
} catch (ResourceNotFoundException $e) {
    $response = new Response('Nicht gefunden', 404);
}

$response->send();
