<?php

namespace App\Controller\Api\SaveTweet\v1;

use App\Controller\Common\ErrorResponseTrait;
use App\Manager\TweetManager;
use App\Service\FeedService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use Symfony\Component\HttpFoundation\Response;

class Controller extends AbstractFOSRestController
{
    use ErrorResponseTrait;

    private TweetManager $tweetManager;

    private FeedService $feedService;

    public function __construct(TweetManager $tweetManager, FeedService $feedService)
    {
        $this->tweetManager = $tweetManager;
        $this->feedService = $feedService;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Rest\Post(path: '/api/v1/tweet')]
    #[RequestParam(name: 'authorId', requirements: '\d+')]
    #[RequestParam(name: 'text')]
    #[RequestParam(name: 'async', requirements: '0|1', nullable: true)]
    public function saveTweetAction(int $authorId, string $text, ?int $async): Response
    {
        $tweet = $this->tweetManager->saveTweet($authorId, $text);
        $success = $tweet !== null;
        if ($success) {
            if ($async === 1) {
                $this->feedService->spreadTweetAsync($tweet);
            } else {
                $this->feedService->spreadTweetSync($tweet);
            }
        }
        $code = $success ? 200 : 400;

        return $this->handleView($this->view(['success' => $success], $code));
    }
}
