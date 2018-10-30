<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpMember;
use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mailchimp\Mailchimp;

class MembersController extends Controller
{
    /**
     * @var \Mailchimp\Mailchimp
     */
    private $mailChimp;

    /**
     * ListsController constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Mailchimp\Mailchimp $mailchimp
     */
    public function __construct(EntityManagerInterface $entityManager, Mailchimp $mailchimp)
    {
        parent::__construct($entityManager);

        $this->mailChimp = $mailchimp;
    }

    /**
     * Create MailChimp member.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request, string $listId): JsonResponse
    {
        // Retrieve mail chimp id
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }
        $mailChimpId = $list->getMailChimpId();

        // Instantiate entity
        $member = new MailChimpMember($request->all());
        $member = $member->setListId($mailChimpId);

        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
            // Save member into MailChimp
            $response = $this->mailChimp->post(\sprintf('lists/%s/members', $mailChimpId), $member->toMailChimpArray());
            // Save member into db
            $this->saveEntity($member);
        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * Create MailChimp member.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $listId
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $listId, string $memberId): JsonResponse
    {
        // Retrieve mail chimp id
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }
        $mailChimpId = $list->getMailChimpId();

        $member = $this->entityManager->getRepository(MailChimpMember::class)->find($memberId);
        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpMember[%s] not found', $memberId)],
                404
            );
        }

        $subscriberHash = md5(strtolower($member->getEmailAddress()));

        // Update properties
        $member->fill($request->all());

        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
            // Update member into MailChimp
            $response = $this->mailChimp->patch(\sprintf('lists/%s/members/%s', $mailChimpId, $subscriberHash), $member->toMailChimpArray());
            // Update member from db
            $this->saveEntity($member);
        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * Remove MailChimp member.
     *
     * @param string $listId
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(string $listId, string $memberId): JsonResponse
    {
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }
        $mailChimpId = $list->getMailChimpId();

        $member = $this->entityManager->getRepository(MailChimpMember::class)->find($memberId);

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpMember[%s] not found', $memberId)],
                404
            );
        }

        $subscriberHash = md5(strtolower($member->getEmailAddress()));

        try {
            // Remove list from MailChimp
            $this->mailChimp->delete(\sprintf('lists/%s/members/%s', $list->getMailChimpId(), $subscriberHash));

            // Remove list from database
            $this->removeEntity($member);
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse([]);
    }

    /**
     * Retrieve and return MailChimp members.
     *
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $listId, string $memberId = ''): JsonResponse
    {
        // Retrieve mail chimp id
        $list = $this->entityManager->getRepository(MailChimpList::class)->find($listId);
        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                404
            );
        }
        $mailChimpId = $list->getMailChimpId();

        if ($memberId) {
            $member = $this->entityManager->getRepository(MailChimpMember::class)->find($memberId);
            if ($member === null) {
                return $this->errorResponse(
                    ['message' => \sprintf('MailChimpMember[%s] not found', $memberId)],
                    404
                );
            }
            return $this->successfulResponse($member->toArray());
        } else {
            $members = $this->entityManager->getRepository(MailChimpMember::class)->findBy(['listId' => $list->getMailChimpId()]);
            $members = array_map(function($member) {
                return $member->toArray();
            }, $members);
            return $this->successfulResponse($members);
        }
    }

}
