<?php
declare(strict_types=1);

namespace Tests\App\Functional\Http\Controllers\MailChimp;

use Tests\App\TestCases\MailChimp\ListTestCase;
use App\Http\Controllers\MailChimp\MembersController;

class MembersControllerTest extends ListTestCase
{
    /**
     * Test create members successfully from a list
     *
     * @return void
     */
    public function testCreateMemberSuccessfully(): void
    {
        $list = $this->createListForMembersTesting(static::$listData);

        // example of mocking so that we don't connect to the mailchimp, only added this feature on this, because of no more time.
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForMemberTesting('post'));
        $response = $controller->create($this->getRequest(static::$memberData), $list->getListId());
        $content = \json_decode($response->content(), true);
        
        // if not use the mock
        // $this->post('/mailchimp/lists/' . $list->getListId() . '/members', static::$memberData);
        // $content = \json_decode($this->response->getContent(), true);

        self::assertArrayHasKey('list_id', $content);
        self::assertArrayHasKey('member_id', $content);
        self::assertEquals($content['list_id'], $list->getMailChimpId());
    }

    /**
     * Test create member returns error response with errors when member validation fails.
     *
     * @return void
     */
    public function testCreateListValidationFailed(): void
    {
        $list = $this->createListForMembersTesting(static::$listData);

        $this->post('/mailchimp/lists/' . $list->getListId() . '/members');

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(400);
        self::assertArrayHasKey('message', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertEquals('Invalid data given', $content['message']);
    }

    /**
     * Test remove member returns successfull response when deleting a member.
     * 
     * @return void
     */
    public function testRemoveMemberFromAListSuccessfully(): void
    {
        $list = $this->createListForMembersTesting(static::$listData);

        $this->post('/mailchimp/lists/' . $list->getListId() . '/members', static::$memberData);

        $content = \json_decode($this->response->getContent(), true);

        $this->delete('/mailchimp/lists/' . $list->getListId() . '/members/' . $content['member_id']);

        $this->assertResponseOk();
        self::assertEmpty(\json_decode($this->response->content(), true));
    }

    /**
     * Test remove member returns error response when list not found.
     *
     * @return void
     */
    public function testRemoveMemberErrorResponseWithInvalidListId(): void
    {
        $this->delete('/mailchimp/lists/invalid-list-id/members/invalid-member-id');

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals('MailChimpList[invalid-list-id] not found', $content['message']);
    }

    /**
     * Test remove member returns error response when member not found.
     *
     * @return void
     */
    public function testRemoveMemberErrorResponseWithInvalidMemberId(): void
    {
        $list = $this->createListForMembersTesting(static::$listData);
        $this->delete('/mailchimp/lists/' . $list->getListId() . '/members/invalid-member-id');

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals('MailChimpMember[invalid-member-id] not found', $content['message']);
    }

    /**
     * Test update member returns successfully response when updating existing member with updated values.
     *
     * @return void
     */
    public function testUpdateMemberSuccessfully(): void
    {
        $list = $this->createListForMembersTesting(static::$listData);

        $this->post('/mailchimp/lists/' . $list->getListId() . '/members', static::$memberData);
        $addContent = \json_decode($this->response->getContent(), true);

        $this->put('/mailchimp/lists/' . $list->getListId() . '/members/' . $addContent['member_id'], ['status' => 'subscribed']);
        $updateContent = \json_decode($this->response->getContent(), true);

        $this->assertResponseOk();
        self::assertArrayHasKey('status', $updateContent);
        self::assertEquals($updateContent['status'], 'subscribed');
    }

    /**
     * Test update member returns error response when list not found.
     *
     * @return void
     */
    public function testUpdateMemberErrorResponseWithInvalidListId(): void
    {
        $this->put('/mailchimp/lists/invalid-list-id/members/invalid-member-id', static::$memberData);

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals('MailChimpList[invalid-list-id] not found', $content['message']);
    }

    /**
     * Test update member returns error response when member not found.
     *
     * @return void
     */
    public function testUpdateMemberErrorResponseWithInvalidMemberId(): void
    {
        $list = $this->createListForMembersTesting(static::$listData);
        $this->put('/mailchimp/lists/' . $list->getListId() . '/members/invalid-member-id', static::$memberData);

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals('MailChimpMember[invalid-member-id] not found', $content['message']);
    }

    /**
     * Test show members returns successful response with list data when requesting existing list.
     *
     * @return void
     */
    public function testShowMembersSuccessfully(): void
    {
        $list = $this->createListForMembersTesting(static::$listData);

        $this->post('/mailchimp/lists/' . $list->getListId() . '/members', static::$memberData);
        $addContent = \json_decode($this->response->getContent(), true);

        $this->get(\sprintf('/mailchimp/lists/%s/members/%s', $list->getId(), $addContent['member_id']));
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseOk();

        foreach (static::$memberData as $key => $value) {
            self::assertArrayHasKey($key, $content);
            self::assertEquals($value, $content[$key]);
        }
    }

    /**
     * Test show member returns error response when list not found.
     *
     * @return void
     */
    public function testShowMemberErrorResponseWithInvalidListId(): void
    {
        $this->get(\sprintf('/mailchimp/lists/%s/members/%s', 'invalid-list-id', 'invalid-member-id'));
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals('MailChimpList[invalid-list-id] not found', $content['message']);
    }

    /**
     * Test show member returns error response when member not found.
     *
     * @return void
     */
    public function testShowMemberErrorResponseWithInvalidMemberId(): void
    {
        $list = $this->createListForMembersTesting(static::$listData);
        $this->get(\sprintf('/mailchimp/lists/%s/members/%s', $list->getListId(), 'invalid-member-id'));
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals('MailChimpMember[invalid-member-id] not found', $content['message']);
    }


}
