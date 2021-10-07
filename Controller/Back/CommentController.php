<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia                                                                       */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*      along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Comment\Controller\Back;

use Comment\Comment;
use Comment\Events\CommentChangeStatusEvent;
use Comment\Events\CommentCheckOrderEvent;
use Comment\Events\CommentCreateEvent;
use Comment\Events\CommentDeleteEvent;
use Comment\Events\CommentEvent;
use Comment\Events\CommentEvents;
use Comment\Events\CommentUpdateEvent;
use Comment\Form\CommentCreationForm;
use Comment\Form\CommentModificationForm;
use Comment\Form\ConfigurationForm;
use Comment\Model\CommentQuery;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Controller\Admin\AbstractCrudController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Template\ParserContext;
use Thelia\Core\Translation\Translator;
use Thelia\Model\ConfigQuery;
use Thelia\Model\MetaDataQuery;
use Thelia\Tools\URL;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/module/comment", name="comment_module")
 * Class CommentController
 * @package Comment\Controller\Back
 * @author Julien Chanséaume <jchanseaume@openstudio.fr>
 */
class CommentController extends AbstractCrudController
{

    public function __construct()
    {
        parent::__construct(
            'comment',
            'created_reverse',
            'order',
            AdminResources::CONFIG,
            CommentEvents::COMMENT_CREATE,
            CommentEvents::COMMENT_UPDATE,
            CommentEvents::COMMENT_DELETE,
            null, // No visibility toggle
            null, // no position change
            Comment::getModuleCode()
        );
    }

    /**
     * Return the creation form for this object
     */
    protected function getCreationForm()
    {
        return $this->createForm(CommentCreationForm::getName());
    }

    /**
     * Return the update form for this object
     */
    protected function getUpdateForm()
    {
        return $this->createForm(CommentModificationForm::getName());
    }

    /**
     * Hydrate the update form for this object, before passing it to the update template
     *
     * @param \Comment\Model\Comment $object
     */
    protected function hydrateObjectForm(ParserContext $parserContext,  $object)
    {
        // Prepare the data that will hydrate the form
        $data = [
            'id' => $object->getId(),
            'ref' => $object->getRef(),
            'ref_id' => $object->getRefId(),
            'customer_id' => $object->getCustomerId(),
            'username' => $object->getUsername(),
            'email' => $object->getEmail(),
            'locale' => $object->getLocale(),
            'title' => $object->getTitle(),
            'content' => $object->getContent(),
            'status' => $object->getStatus(),
            'verified' => $object->getVerified(),
            'rating' => $object->getRating()
        ];

        // Setup the object form
        return $this->createForm(CommentModificationForm::getName(), FormType::class, $data);
    }

    /**
     * Creates the creation event with the provided form data
     *
     * @param unknown $formData
     */
    protected function getCreationEvent($formData)
    {
        $event = $this->bindFormData(
            new CommentCreateEvent(),
            $formData
        );

        return $event;
    }

    /**
     * Creates the update event with the provided form data
     *
     * @param unknown $formData
     */
    protected function getUpdateEvent($formData)
    {
        $event = $this->bindFormData(
            new CommentUpdateEvent(),
            $formData
        );

        $event->setId($formData['id']);

        return $event;
    }

    protected function bindFormData($event, $formData)
    {
        $event->setRef($formData['ref']);
        $event->setRefId($formData['ref_id']);
        $event->setCustomerId($formData['customer_id']);
        $event->setUsername($formData['username']);
        $event->setEmail($formData['email']);
        $event->setLocale($formData['locale']);
        $event->setTitle($formData['title']);
        $event->setContent($formData['content']);
        $event->setStatus($formData['status']);
        $event->setVerified($formData['verified']);
        $event->setRating($formData['rating']);

        return $event;
    }

    /**
     * Creates the delete event with the provided form data
     */
    protected function getDeleteEvent()
    {
        $event = new CommentDeleteEvent();

        $event->setId($this->getRequest()->get('comment_id'));

        return $event;
    }

    /**
     * Return true if the event contains the object, e.g. the action has updated the object in the event.
     *
     * @param CommentEvent $event
     */
    protected function eventContainsObject($event)
    {
        return null !== $event->getComment();
    }

    /**
     * Get the created object from an event.
     *
     * @param CommentEvent $event
     *
     * @return \Comment\Model\Comment
     */
    protected function getObjectFromEvent($event)
    {
        return $event->getComment();
    }

    /**
     * Load an existing object from the database
     */
    protected function getExistingObject()
    {

        $comment_id = $this->getRequest()->get('comment_id');
        if (null === $comment_id) {
            $comment_id = $this->getRequest()->attributes('comment_id');
        }

        return CommentQuery::create()->findPk($comment_id);
    }

    /**
     * Returns the object label form the object event (name, title, etc.)
     *
     * @param \Comment\Model\Comment $object
     */
    protected function getObjectLabel($object)
    {
        return $object->getTitle();
    }

    /**
     * Returns the object ID from the object
     *
     * @param \Comment\Model\Comment $object
     */
    protected function getObjectId($object)
    {
        return $object->getId();
    }

    /**
     * Render the main list template
     *
     * @param string $currentOrder , if any, null otherwise.
     */
    protected function renderListTemplate($currentOrder)
    {
        return $this->render('comments', ['order' => $currentOrder]);
    }

    /**
     * Render the edition template
     */
    protected function renderEditionTemplate()
    {
        return $this->render(
            'comment-edit',
            [
                'comment_id' => $this->getRequest()->get('comment_id')
            ]
        );
    }

    /**
     * Must return a RedirectResponse instance

     */
    protected function redirectToEditionTemplate()
    {
        $commentId = $this->getRequest()->get('comment_id');
        return $this->generateRedirect(
            URL::getInstance()->absoluteUrl("/admin/module/comment/update/$commentId")
        );
    }

    /**
     * Must return a RedirectResponse instance
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function redirectToListTemplate()
    {
        return $this->generateRedirectFromRoute('admin.comment.comments.default');
    }


    /**
     * @Route("/status", name="_status", methods="POST")
     */
    public function changeStatusAction(RequestStack $requestStack, EventDispatcherInterface $eventDispatcher)
    {
        if (null !== $response = $this->checkAuth([], ['comment'], AccessManager::UPDATE)
        ) {
            return $response;
        }

        $message = [
            "success" => false,
        ];

        $request = $requestStack->getCurrentRequest();
        $id = $request->request->get('id');
        $status = $request->request->get('status');

        if (null !== $id && null !== $status) {
            try {
                $event = new CommentChangeStatusEvent();
                $event
                    ->setId($id)
                    ->setNewStatus($status);

                $eventDispatcher->dispatch(
                    $event,
                    CommentEvents::COMMENT_STATUS_UPDATE
                );

                $message = [
                    "success" => true,
                    "data" => [
                        'id' => $id,
                        'status' => $event->getComment()->getStatus()
                    ]
                ];
            } catch (\Exception $ex) {
                $message["error"] = $ex->getMessage();
            }
        } else {
            $message["error"] = Translator::getInstance()->trans('Missing parameters', [], Comment::MESSAGE_DOMAIN);
        }

        return $this->jsonResponse(json_encode($message));
    }

    /**
     * @Route("/activation/{ref}/{refId}", name="_activation", methods="POST")
     */
    public function activationAction($ref, $refId)
    {
        if (null !== $response = $this->checkAuth([], ['comment'], AccessManager::UPDATE)
        ) {
            return $response;
        }

        $message = [
            "success" => false,
        ];

        $status = $this->getRequest()->request->get('status');

        switch ($status) {
            case "0":
            case "1":
                MetaDataQuery::setVal(\Comment\Model\Comment::META_KEY_ACTIVATED, $ref, $refId, $status);
                $message['success'] = true;
                break;
            case "-1":
                $deleted = MetaDataQuery::create()
                    ->filterByMetaKey(\Comment\Model\Comment::META_KEY_ACTIVATED)
                    ->filterByElementKey($ref)
                    ->filterByElementId($refId)
                    ->delete();
                if ($deleted === 1) {
                    $message['success'] = true;
                }
                break;
        }

        $message['status'] = MetaDataQuery::getVal(\Comment\Model\Comment::META_KEY_ACTIVATED, $ref, $refId, "-1");

        return $this->jsonResponse(json_encode($message));
    }


    /**
     * Save comment module configuration
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    /**
     * @Route("/configuration", name="_configuration", methods="POST")
     */
    public function saveConfiguration(ParserContext $parserContext)
    {

        if (null !== $response = $this->checkAuth([AdminResources::MODULE], ['comment'], AccessManager::UPDATE)
        ) {
            return $response;
        }

        $form = $this->createForm(ConfigurationForm::getName());
        $message = "";

        $response = null;

        try {
            $vform = $this->validateForm($form);
            $data = $vform->getData();

            ConfigQuery::write(
                'comment_activated',
                $data['activated'] ? '1' : '0'
            );
            ConfigQuery::write(
                'comment_moderate',
                $data['moderate'] ? '1' : '0'
            );
            ConfigQuery::write('comment_ref_allowed', $data['ref_allowed']);
            ConfigQuery::write(
                'comment_only_customer',
                $data['only_customer'] ? '1' : '0'
            );
            ConfigQuery::write(
                'comment_only_verified',
                $data['only_verified'] ? '1' : '0'
            );
            ConfigQuery::write(
                'comment_request_customer_ttl',
                $data['request_customer_ttl']
            );
            ConfigQuery::write(
                'comment_notify_admin_new_comment',
                $data['notify_admin_new_comment']
            );
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }
        if ($message) {
            $form->setErrorMessage($message);
            $parserContext->addForm($form);
            $parserContext->setGeneralError($message);

            return $this->render(
                "module-configure",
                ["module_code" => Comment::getModuleCode()]
            );
        }

        return RedirectResponse::create(
            URL::getInstance()->absoluteUrl("/admin/module/" . Comment::getModuleCode())
        );
    }

    /**
     * @Route("/request-customer", name="_request-customer", methods="POST")
     */
    public function requestCustomerCommentAction(EventDispatcherInterface $eventDispatcher)
    {
        // We do not check auth, as the related route may be invoked from a cron
        try {
            $eventDispatcher->dispatch(
                new CommentCheckOrderEvent(),
                CommentEvents::COMMENT_CUSTOMER_DEMAND
            );
        } catch (\Exception $ex) {
            // Any error
            return $this->errorPage($ex);
        }

        return $this->redirectToListTemplate();
    }
}
