<?php
/**
 * Copyright 2010 - 2015, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2015, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Users\Test\TestCase\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Users\Model\Table\SocialAccountsTable;


/**
 * Test Case
 */
class SocialAccountBehaviorTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.users.social_accounts',
        'plugin.users.users'
    ];

    /**
     * setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $table = TableRegistry::get('Users.SocialAccounts');
        $table->addBehavior('Users.SocialAccount');
        $this->Table = $table;
        $this->Behavior = $table->behaviors()->SocialAccount;
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->table, $this->Behavior);
        parent::tearDown();
    }

    /**
     * Test validateEmail method
     *
     * @return void
     */
    public function testValidateEmail()
    {
        $token = 'token-1234';
        $result = $this->Behavior->validateAccount(SocialAccountsTable::PROVIDER_FACEBOOK, 'reference-1-1234', $token);
        $this->assertTrue($result->active);
        $this->assertEquals($token, $result->token);
    }

    /**
     * Test validateEmail method
     *
     * @expectedException \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function testValidateEmailInvalidToken()
    {
        $this->Behavior->validateAccount(1, 'reference-1234', 'invalid-token');
    }

    /**
     * Test validateEmail method
     *
     * @expectedException \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function testValidateEmailInvalidUser()
    {
        $this->Behavior->validateAccount(1, 'invalid-user', 'token-1234');
    }

    /**
     * Test validateEmail method
     *
     * @expectedException \Users\Exception\AccountAlreadyActiveException
     */
    public function testValidateEmailActiveAccount()
    {
        $this->Behavior->validateAccount(SocialAccountsTable::PROVIDER_TWITTER, 'reference-1-1234', 'token-1234');
    }

    /**
     * testAfterSaveSocialNotActiveUserNotActive
     * don't send email, user is not active
     *
     * @return void
     */
    public function testAfterSaveSocialNotActiveUserNotActive()
    {
        $event = new Event('eventName');
        $entity = $this->Table->find()->first();
        $this->assertTrue($this->Behavior->afterSave($event, $entity, []));
    }

    /**
     * testAfterSaveSocialActiveUserActive
     * social account is active, don't send email
     *
     * @return void
     */
    public function testAfterSaveSocialActiveUserActive()
    {
        $event = new Event('eventName');
        $entity = $this->Table->findById(3)->first();
        $this->assertTrue($this->Behavior->afterSave($event, $entity, []));
    }

    /**
     * testAfterSaveSocialActiveUserNotActive
     * social account is active, don't send email
     *
     * @return void
     */
    public function testAfterSaveSocialActiveUserNotActive()
    {
        $event = new Event('eventName');
        $entity = $this->Table->findById(2)->first();
        $this->assertTrue($this->Behavior->afterSave($event, $entity, []));
    }
}