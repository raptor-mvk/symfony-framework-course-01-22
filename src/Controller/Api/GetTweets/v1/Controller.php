<?php

namespace App\Controller\Api\GetTweets\v1;

use App\Entity\Tweet;
use App\Manager\TweetManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Controller extends AbstractFOSRestController
{
    private TweetManager $tweetManager;

    public function __construct(TweetManager $tweetManager)
    {
        $this->tweetManager = $tweetManager;
    }

    #[Rest\Get(path: '/api/v1/tweet')]
    public function getTweetsAction(Request $request): Response
    {
        $perPage = $request->query->get('perPage');
        $page = $request->query->get('page');
        $tweets = $this->tweetManager->getTweets($page ?? 0, $perPage ?? 20);
        $code = empty($tweets) ? 204 : 200;
        $view = $this->view(['tweets' => $tweets], $code);

        return $this->handleView($view);
    }
}
