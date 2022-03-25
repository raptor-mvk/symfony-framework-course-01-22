<?php

namespace App\Controller\Api\SaveTweet\v1;

use App\Controller\Common\ErrorResponseTrait;
use App\Manager\TweetManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use Symfony\Component\HttpFoundation\Response;

class Controller extends AbstractFOSRestController
{
    use ErrorResponseTrait;

    private TweetManager $tweetManager;

    public function __construct(TweetManager $tweetManager)
    {
        $this->tweetManager = $tweetManager;
    }

    #[Rest\Post(path: '/api/v1/tweet')]
    #[RequestParam(name: 'authorId', requirements: '\d+')]
    #[RequestParam(name: 'text')]
    public function saveTweetAction(int $authorId, string $text): Response
    {
        $tweetId = $this->tweetManager->saveTweet($authorId, $text);
        [$data, $code] = ($tweetId === null) ? [['success' => false], 400] : [['tweet' => $tweetId], 200];
        return $this->handleView($this->view($data, $code));
    }
}
