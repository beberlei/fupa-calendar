<?php

namespace FupaCalendar;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Buzz\Browser;
use DateTime;

class Generator
{
    public function getCalendar($club)
    {
        $client = new Browser();
        $url = 'http://www.fupa.net/vereine/%s/termine/%s';

        $now = new \DateTime('now');
        $collectedMonths = 0;

        $vCalendar = new Calendar('www.example.com');
        $headers = ['User-Agent: FupaCalendar Cralwer (https://github.com/beberlei/fupa-calendar)'];

        do {
            $response = $client->get(sprintf($url, $club, $now->format('Y-m')), $headers);

            if ($response->getStatusCode() === 404) {
                throw new NotFoundHttpException();
            }

            $crawler = new Crawler($response->getContent());

            $teamName = trim($crawler->filter('.head_line h1')->text());
            $rows = $crawler->filter('.rows .row');
            $utcZone = new \DateTimeZone('UTC');

            foreach ($rows as $element) {
                $crawler = new Crawler($element);
                try {
                    $time = str_replace(' Uhr', '', $crawler->filter('.time')->text());
                    $date = $crawler->filter('.datum')->text();
                    $homeTeam = $crawler->filter('.name_heim')->text();
                    $awayTeam = $crawler->filter('.name_gast')->text();
                    $isHomeGame = strpos($homeTeam, $teamName) !== false;

                    if (!$isHomeGame) {
                        continue;
                    }

                    if (strpos($date, ", ") === false) {
                        throw new \InvalidArgumentException("Cannot find the right date format.");
                    }

                    list ($day, $date) = explode(', ', $date);
                    $start = DateTime::createFromFormat('d.m.Y H:i', sprintf('%s %s', $date, $time));
                    $start->setTimeZone($utcZone);
                    $end = clone $start;
                    $end = $start->modify('+105 minute');
                    $end->setTimeZone($utcZone);

                    $vEvent = new Event();
                    $vEvent->setDtStart($start)->setDtEnd($end)->setSummary(sprintf('Heimspiel: %s vs %s', $homeTeam, $awayTeam));

                    $vCalendar->addComponent($vEvent);
                } catch (\InvalidArgumentException $e) {
                }
            }

            $collectedMonths++;
            $now = $now->modify('+1 month');
        } while ($collectedMonths < 3);

        return $vCalendar;
    }
}
