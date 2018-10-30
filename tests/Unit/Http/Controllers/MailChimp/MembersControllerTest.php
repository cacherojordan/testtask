<?php
declare(strict_types=1);

namespace Tests\App\Unit\Http\Controllers\MailChimp;

use App\Http\Controllers\MailChimp\MembersController;
use Tests\App\TestCases\MailChimp\ListTestCase;

class MembersControllerTest extends ListTestCase
{

    /**
     * Test controller returns error response when exception is thrown during create MailChimp request.
     *
     * @return void
     */
    public function testCreateMemberMailChimpException(): void
    {
        $list = $this->createListForMembersTesting(static::$listData);

        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('post'));

        $this->assertMailChimpExceptionResponse($controller->create($this->getRequest(static::$memberData), $list->getListId()));
    }

    /**
     * Test controller returns error response when exception is thrown during remove MailChimp request.
     *
     * @return void
     */
    public function testRemoveMemberFromListMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('delete'));
        $list = $this->createListForMembersTesting(static::$listData);
        $member = $this->createMember(static::$memberData, $list->getListId());

        // If there is no list id, skip
        if (null === $list->getId()) {
            self::markTestSkipped('Unable to remove, no id provided for list');

            return;
        }

        // If there is no member id, skip
        if (null === $member->getMemberId()) {
            self::markTestSkipped('Unable to remove, no id provided for member');

            return;
        }

        $this->assertMailChimpExceptionResponse($controller->remove($list->getId(), $member->getMemberId()));
    }

    /**
     * Test controller returns error response when exception is thrown during update MailChimp request.
     *
     * @return void
     */
    public function testUpdateMemberFromListMailChimpException(): void
    {
        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('patch'));
        $list = $this->createList(static::$listData);

        $list = $this->createListForMembersTesting(static::$listData);
        $member = $this->createMember(static::$memberData, $list->getListId());

        // If there is no list id, skip
        if (null === $list->getId()) {
            self::markTestSkipped('Unable to remove, no id provided for list');

            return;
        }

        // If there is no member id, skip
        if (null === $member->getMemberId()) {
            self::markTestSkipped('Unable to remove, no id provided for member');

            return;
        }


        $this->assertMailChimpExceptionResponse($controller->update($this->getRequest(['status' => 'subscribed']), $list->getListId(), $member->getMemberId()));
    }
}
