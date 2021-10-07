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


namespace Comment\Loop;

use Comment\Comment;
use Comment\Events\CommentEvents;
use Comment\Events\CommentReferenceGetterEvent;
use Comment\Model\CommentQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Type;
use Thelia\Type\BooleanOrBothType;

/**
 * Class CommentLoop
 * @package Comment\Loop
 * @author Julien Chanséaume <jchanseaume@openstudio.fr>
 */
class CommentLoop extends BaseLoop implements PropelSearchLoopInterface
{
    protected $timestampable = true;

    protected $cacheRef = [];

    /**
     * Definition of loop arguments
     *
     * @return \Thelia\Core\Template\Loop\Argument\ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntListTypeArgument('id'),
            Argument::createIntListTypeArgument('customer'),
            Argument::createAnyTypeArgument('ref'),
            Argument::createIntListTypeArgument('ref_id'),
            Argument::createIntListTypeArgument('status'),
            Argument::createBooleanOrBothTypeArgument('verified', BooleanOrBothType::ANY),
            Argument::createAnyTypeArgument('locale'),
            Argument::createAnyTypeArgument('load_ref', 0),
            new Argument(
                'order',
                new Type\TypeCollection(
                    new Type\EnumListType(
                        [
                            'id',
                            'id_reverse',
                            'status',
                            'status_reverse',
                            'verified',
                            'verified_reverse',
                            'abuse',
                            'abuse_reverse',
                            'created',
                            'created_reverse',
                            'updated',
                            'updated_reverse'
                        ]
                    )
                ),
                'manual'
            ),
            Argument::createAnyTypeArgument('ref_locale')
        );
    }

    /**
     * this method returns a Propel ModelCriteria
     *
     * @return \Propel\Runtime\ActiveQuery\ModelCriteria
     */
    public function buildModelCriteria()
    {
        $search = CommentQuery::create();

        $id = $this->getId();
        if (null !== $id) {
            $search->filterById($id, Criteria::IN);
        }

        $customer = $this->getCustomer();
        if (null !== $customer) {
            $search->filterByCustomerId($customer, Criteria::IN);
        }

        $ref = $this->getRef();
        $refId = $this->getRefId();
        if (null !== $ref || null !== $refId) {
            if (null === $ref || null === $refId) {
                throw new \InvalidArgumentException(
                    $this->translator->trans(
                        "If 'ref' argument is specified, 'ref_id' argument should be specified",
                        [],
                        Comment::MESSAGE_DOMAIN
                    )
                );
            }

            $search->filterByRef($ref);
            $search->filterByRefId($refId, Criteria::IN);
        }

        $status = $this->getStatus();
        if ($status !== null) {
            $search->filterByStatus($status);
        }

        $verified = $this->getVerified();
        if ($verified !== BooleanOrBothType::ANY) {
            $search->filterByVerified($verified ? 1 : 0);
        }

        $locale = $this->getLocale();
        if (null !== $locale) {
            $search->filterByLocale($locale);
        }

        $orders = $this->getOrder();
        if (null !== $orders) {
            foreach ($orders as $order) {
                switch ($order) {
                    case "id":
                        $search->orderById(Criteria::ASC);
                        break;
                    case "id_reverse":
                        $search->orderById(Criteria::DESC);
                        break;
                    case "visible":
                        $search->orderByStatus(Criteria::ASC);
                        break;
                    case "visible_reverse":
                        $search->orderByStatus(Criteria::DESC);
                        break;
                    case "verified":
                        $search->orderByVerified(Criteria::ASC);
                        break;
                    case "verified_reverse":
                        $search->orderByVerified(Criteria::DESC);
                        break;
                    case "abuse":
                        $search->orderByAbuse(Criteria::ASC);
                        break;
                    case "abuse_reverse":
                        $search->orderByAbuse(Criteria::DESC);
                        break;
                    case "rating":
                        $search->orderByRating(Criteria::ASC);
                        break;
                    case "rating_reverse":
                        $search->orderByRating(Criteria::DESC);
                        break;
                    case "created":
                        $search->addAscendingOrderByColumn('created_at');
                        break;
                    case "created_reverse":
                        $search->addDescendingOrderByColumn('created_at');
                        break;
                    case "updated":
                        $search->addAscendingOrderByColumn('updated_at');
                        break;
                    case "updated_reverse":
                        $search->addDescendingOrderByColumn('updated_at');
                        break;
                }
            }
        }

        return $search;
    }

    /**
     * @param LoopResult $loopResult
     *
     * @return LoopResult
     */
    public function parseResults(LoopResult $loopResult)
    {
        /** @var \Comment\Model\Comment $comment */
        foreach ($loopResult->getResultDataCollection() as $comment) {
            $loopResultRow = new LoopResultRow($comment);

            $loopResultRow
                ->set('ID', $comment->getId())
                ->set('USERNAME', $comment->getUsername())
                ->set('EMAIL', $comment->getEmail())
                ->set('CUSTOMER_ID', $comment->getCustomerId())
                ->set('REF', $comment->getRef())
                ->set('REF_ID', $comment->getRefId())
                ->set('TITLE', $comment->getTitle())
                ->set('CONTENT', $comment->getContent())
                ->set('RATING', $comment->getRating())
                ->set('STATUS', $comment->getStatus())
                ->set('VERIFIED', $comment->getVerified())
                ->set('ABUSE', $comment->getAbuse())
                ->set('CREATED', $comment->getCreatedAt()->format('d/m/Y h:m:s'));

            if (1 == $this->getLoadRef()) {
                // dispatch event to get the reference element
                $this->getReference(
                    $loopResultRow,
                    $comment->getRef(),
                    $comment->getRefId()
                );
            }

            $this->addOutputFields($loopResultRow, $comment);

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }

    /**
     * @param LoopResultRow $loopResultRow
     * @param string $ref
     * @param int $refId
     */
    protected function getReference(LoopResultRow $loopResultRow, $ref, $refId)
    {
        $key = sprintf('%s:%s', $ref, $refId);
        $data = [
            'REF_OBJECT' => null,
            'REF_TITLE' => null,
            'REF_TYPE_TITLE' => null,
            'REF_EDIT_URL' => null,
            'REF_VIEW_URL' => null
        ];

        $refLocale = $this->getRefLocale();
        if ($refLocale === null) {
            $refLocale =  $this->requestStack->getCurrentRequest()->getLocale();
        }

        if (!array_key_exists($key, $this->cacheRef)) {
            $event = new CommentReferenceGetterEvent($ref, $refId, $refLocale);

            $this->dispatcher->dispatch(
                $event,
                CommentEvents::COMMENT_REFERENCE_GETTER
            );

            $data['REF_OBJECT'] = $event->getObject();
            $data['REF_TITLE'] = $event->getTitle();
            $data['REF_TYPE_TITLE'] = $event->getTypeTitle();
            $data['REF_EDIT_URL'] = $event->getEditUrl();
            $data['REF_VIEW_URL'] = $event->getViewUrl();
        } else {
            $data = $this->cacheRef[$key];
        }

        foreach ($data as $k => $v) {
            $loopResultRow->set($k, $v);
        }
    }
}
