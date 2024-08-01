<?php

namespace Comment\EventListener;

use ReCaptcha\Event\ReCaptchaCheckEvent;
use ReCaptcha\Event\ReCaptchaEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Model\Base\ModuleQuery;
use Comment\Events\CommentEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Comment\Events\CommentCreateEvent;

class AddCommentListener implements EventSubscriberInterface
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var RequestStack */
    protected $requestStack;

    public function __construct(EventDispatcherInterface $eventDispatcher, RequestStack $requestStack)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->requestStack = $requestStack;
    }

    public function checkCaptcha(CommentCreateEvent $event)
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (!$currentRequest->request->has('admin_add_comment')) {
            return;
        }

        if (null !== ModuleQuery::create()->filterByCode("ReCaptcha")->filterByActivate(1)->findOne()) {
            $checkCaptchaEvent = new ReCaptchaCheckEvent();
            $this->eventDispatcher->dispatch($checkCaptchaEvent, ReCaptchaEvents::CHECK_CAPTCHA_EVENT);
            if ($checkCaptchaEvent->isHuman() == false) {
                throw new \Exception('Invalid captcha');
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            CommentEvents::COMMENT_CREATE => ['checkCaptcha', 256],
        ];
    }
}
