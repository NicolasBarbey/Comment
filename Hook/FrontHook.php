<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/


namespace Comment\Hook;

use Comment\Comment;
use Comment\Events\CommentDefinitionEvent;
use Comment\Events\CommentEvents;
use Comment\Exception\InvalidDefinitionException;
use Thelia\Core\Event\Hook\BaseHookRenderEvent;
use Thelia\Core\Event\Hook\HookRenderBlockEvent;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Log\Tlog;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Exception\InvalidArgumentException;

/**
 * Class FrontHook
 * @package Comment\Hook
 * @author Julien Chanséaume <jchanseaume@openstudio.fr>
 */
class FrontHook extends BaseHook
{
    protected $parserContext = null;

    public function __construct()
    {
    }

    public function onShowComment(HookRenderEvent $event)
    {
        $data = $this->showComment($event);

        if (null !== $data) {
            $event->add($data);
        }
    }

    public function onShowBlockComment(HookRenderBlockEvent $event)
    {
        $data = $this->showComment($event);

        if (null !== $data) {
            $event->add(
                [
                    'id' => 'comment',
                    'title' => $this->trans("Comments", [], Comment::MESSAGE_DOMAIN),
                    'content' => $data
                ]
            );
        }
    }

    protected function showComment(BaseHookRenderEvent $event)
    {
        list($ref, $refId) = $this->getParams($event);

        $eventDefinition = new CommentDefinitionEvent();
        $eventDefinition
            ->setRef($ref)
            ->setRefId($refId)
            ->setCustomer($this->getCustomer())
            ->setConfig(Comment::getConfig());
        $message = '';

        try {
            $this->dispatcher->dispatch(
                $eventDefinition,
                CommentEvents::COMMENT_GET_DEFINITION
            );
            $eventDefinition->setValid(true);
        } catch (InvalidDefinitionException $ex) {
            if ($ex->isSilent()) {
                return null;
            }
            $eventDefinition->setValid(false);
            $message = $ex->getMessage();
        } catch (\Exception $ex) {
            Tlog::getInstance()->debug($ex->getMessage());
            return null;
        }

        return $this->render(
            "comment.html",
            [
                'definition' => $eventDefinition,
                'message' => $message
            ]
        );
    }

    /**
     * Add the javascript script to manage comments
     *
     * @param HookRenderEvent $event
     */
    public function jsComment(HookRenderEvent $event)
    {
        $allowedRef = explode(
            ',',
            ConfigQuery::read('comment_ref_allowed', Comment::CONFIG_REF_ALLOWED)
        );

        if (in_array($this->getView(), $allowedRef)) {

            list($ref, $refId) = $this->getParams($event);

            $event->add(
                $this->render(
                    "js.html",
                    [
                        'ref' => $ref,
                        'ref_id' => $refId
                    ]
                )
            );

            $event->add(
                $this->addJS('assets/js/comment.js')
            );
        }
    }

    protected function getParams(BaseHookRenderEvent $event)
    {
        $ref = $event->getArgument('ref')
            ? $event->getArgument('ref')
            : $this->getView();

        $refId = 0;

        if ($event->getArgument('ref_id')) {
            $refId = $event->getArgument('ref_id');
        } else {
            if ($this->getRequest()->attributes->has('id')) {
                $refId = (int)($this->getRequest()->attributes->get('id'));
            } elseif ($this->getRequest()->query->has($ref . '_id')) {
                $refId = (int)($this->getRequest()->query->get($ref . '_id'));
            }
        }

        if (null === $ref || 0 === $refId) {
            throw new InvalidArgumentException(
                $this->trans("Reference not found", [], Comment::MESSAGE_DOMAIN)
            );
        }

        return [$ref, $refId];
    }
}
