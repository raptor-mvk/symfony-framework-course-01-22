<?php

namespace App\Controller\Api\SaveTweet\v1;

use App\Controller\Common\ErrorResponseTrait;
use App\Manager\TweetManager;
use App\Service\AsyncService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;

class Controller extends AbstractFOSRestController
{
    use ErrorResponseTrait;

    private TweetManager $tweetManager;

    private AsyncService $asyncService;

    public function __construct(TweetManager $tweetManager, AsyncService $asyncService)
    {
        $this->tweetManager = $tweetManager;
        $this->asyncService = $asyncService;
    }

    /**
     * @Rest\Post("/api/v1/tweet")
     *
     * @RequestParam(name="authorId", requirements="\d+")
     * @RequestParam(name="text")
     * @RequestParam(name="async", requirements="0|1", nullable=true)
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function saveTweetAction(int $authorId, string $text, ?int $async): Response
    {
        $tweet = $this->tweetManager->saveTweet($authorId, $text);
        $success = $tweet !== null;
        if ($success) {
            if ($async === 1) {
                $this->asyncService->publishToExchange(AsyncService::PUBLISH_TWEET, $tweet->toAMPQMessage());
            } else {
                return $this->handleView(View::create(['message' => 'Sync post is no longer supported'], 400));
            }
        }
        $code = $success ? 200 : 400;

        return $this->handleView($this->view(['success' => $success], $code));
    }
}
