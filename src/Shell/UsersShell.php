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

namespace Users\Shell;

use Cake\Auth\DefaultPasswordHasher;
use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Utility\Hash;
use Users\Model\Entity\User;

/**
 * Shell with utilities for the Users Plugin
 *
 * @property \Users\Model\Table\Users Users
 */
class UsersShell extends Shell
{

    /**
     * Work as a seed for username generator
     *
     * @var array
     */
    protected $_usernameSeed = ['aayla', 'admiral', 'anakin', 'chewbacca', 'darthvader', 'hansolo', 'luke', 'obiwan', 'leia', 'r2d2'];

    /**
     * initialize callback
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Users.Users');
    }

    /**
     *
     * @return OptionParser
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->description(__d('Users', 'Utilities for CakeDC Users Plugin'))
            ->addSubcommand('activateUser')->description(__d('Users', 'Activate an specific user'))
            ->addSubcommand('addSuperuser')->description(__d('Users', 'Add a new superadmin user for testing purposes'))
            ->addSubcommand('addUser')->description(__d('Users', 'Add a new user'))
            ->addSubcommand('changeRole')->description(__d('Users', 'Change the role for an specific user'))
            ->addSubcommand('deactivateUser')->description(__d('Users', 'Deactivate an specific user'))
            ->addSubcommand('deleteUser')->description(__d('Users', 'Delete an specific user'))
            ->addSubcommand('passwordEmail')->description(__d('Users', 'Reset the password via email'))
            ->addSubcommand('resetAllPasswords')->description(__d('Users', 'Reset the password for all users'))
            ->addSubcommand('resetPassword')->description(__d('Users', 'Reset the password for an specific user'))
            ->addOptions([
                'username' => ['short' => 'u', 'help' => 'The username for the new user'],
                'password' => ['short' => 'p', 'help' => 'The password for the new user'],
                'email' => ['short' => 'e', 'help' => 'The email for the new user']
            ])
        ;
        return $parser;
    }

    /**
     * Add a new user
     *
     * @return void
     */
    public function addUser()
    {
        $username = (empty($this->params['username']) ?
            $this->_usernameSeed[array_rand($this->_usernameSeed)] : $this->params['username']);
        $username = $this->Users->generateUniqueUsername($username);
        $password = (empty($this->params['password']) ?
            str_replace('-', '', \Cake\Utility\Text::uuid()) : $this->params['password']);
        $email = (empty($this->params['email']) ? $username . '@example.com' : $this->params['email']);
        $user = [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'active' => 1,
        ];

        $userEntity = $this->Users->newEntity($user);
        $userEntity->is_superuser = true;
        $userEntity->role = 'user';
        $savedUser = $this->Users->save($userEntity);
        $this->out(__d('Users', 'User added:'));
        $this->out(__d('Users', 'Id: {0}', $savedUser->id));
        $this->out(__d('Users', 'Username: {0}', $username));
        $this->out(__d('Users', 'Email: {0}', $savedUser->email));
        $this->out(__d('Users', 'Password: {0}', $password));
    }

    /**
     * Add a new superadmin user
     *
     * @return void
     */
    public function addSuperuser()
    {
        $username = $this->Users->generateUniqueUsername('superadmin');
        $password = str_replace('-', '', \Cake\Utility\Text::uuid());
        $user = [
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => $password,
            'active' => 1,
        ];

        $userEntity = $this->Users->newEntity($user);
        $userEntity->is_superuser = true;
        $userEntity->role = 'superuser';
        $savedUser = $this->Users->save($userEntity);
        $this->out(__d('Users', 'Superuser added:'));
        $this->out(__d('Users', 'Id: {0}', $savedUser->id));
        $this->out(__d('Users', 'Username: {0}', $username));
        $this->out(__d('Users', 'Email: {0}', $savedUser->email));
        $this->out(__d('Users', 'Password: {0}', $password));
    }

    /**
     * Reset password for all user
     *
     * Arguments:
     *
     * - Password to be set
     *
     * @return void
     */
    public function resetAllPasswords()
    {
        $password = Hash::get($this->args, 0);
        if (empty($password)) {
            $this->error(__d('Users', 'Please enter a password.'));
        }
        $hashedPassword = (new User)->hashPassword($password);
        $this->Users->updateAll(['password' => $hashedPassword], ['id IS NOT NULL']);
        $this->out(__d('Users', 'Password changed for all users'));
        $this->out(__d('Users', 'New password: {0}', $password));
    }

    /**
     * Reset password for a user
     *
     * Arguments:
     *
     * - Username
     * - Password to be set
     *
     * @return void
     */
    public function resetPassword()
    {
        $username = Hash::get($this->args, 0);
        $password = Hash::get($this->args, 1);
        if (empty($username)) {
            $this->error(__d('Users', 'Please enter a username.'));
        }
        if (empty($password)) {
            $this->error(__d('Users', 'Please enter a password.'));
        }
        $data = [
            'password' => $password
        ];
        $this->_updateUser($username, $data);
        $this->out(__d('Users', 'Password changed for user: {0}', $username));
        $this->out(__d('Users', 'New password: {0}', $password));
    }

    /**
     * Change role for a user
     *
     * Arguments:
     *
     * - Username
     * - Role to be set
     *
     * @return void
     */
    public function changeRole()
    {
        $username = Hash::get($this->args, 0);
        $role = Hash::get($this->args, 1);
        if (empty($username)) {
            $this->error(__d('Users', 'Please enter a username.'));
        }
        if (empty($role)) {
            $this->error(__d('Users', 'Please enter a role.'));
        }
        $data = [
            'role' => $role
        ];
        $savedUser = $this->_updateUser($username, $data);
        $this->out(__d('Users', 'Role changed for user: {0}', $username));
        $this->out(__d('Users', 'New role: {0}', $savedUser->role));
    }

    /**
     * Activate an specific user
     *
     * Arguments:
     *
     * - Username
     *
     * @return void
     */
    public function activateUser()
    {
        $user = $this->_changeUserActive(true);
        $this->out(__d('Users', 'User was activated: {0}', $user->username));

    }

    /**
     * De-activate an specific user
     *
     * Arguments:
     *
     * - Username
     *
     * @return void
     */
    public function deactivateUser()
    {
        $user = $this->_changeUserActive(false);
        $this->out(__d('Users', 'User was de-activated: {0}', $user->username));
    }

    /**
     * Reset password via email for user
     *
     * @return void
     */
    public function passwordEmail()
    {
        $reference = Hash::get($this->args, 0);
        if (empty($reference)) {
            $this->error(__d('Users', 'Please enter a username or email.'));
        }
        $resetUser = $this->Users->resetToken($reference, [
            'expiration' => Configure::read('Users.Token.expiration'),
            'checkActive' => false,
            'sendEmail' => true,
        ]);
        if ($resetUser) {
            $msg = __d('Users', 'Please ask the user to check the email to continue with password reset process');
            $this->out($msg);
        } else {
            $msg = __d('Users', 'The password token could not be generated. Please try again');
            $this->error($msg);
        }
    }

    /**
     * Change user active field
     *
     * @param $active
     */
    protected function _changeUserActive($active)
    {
        $username = Hash::get($this->args, 0);
        if (empty($username)) {
            $this->error(__d('Users', 'Please enter a username.'));
        }
        $data = [
            'active' => $active
        ];
        return $this->_updateUser($username, $data);
    }

    /**
     * Update user by username
     *
     * @param $username
     * @param $data
     */
    protected function _updateUser($username, $data)
    {
        /** @var \Users\Model\Entity\User */
        $user = $this->Users->find()->where(['Users.username' => $username])->first();
        if (empty($user)) {
            $this->error(__d('Users', 'The user was not found.'));
        }
        $user = $this->Users->patchEntity($user, $data);
        collection($data)->filter(function ($value, $field) use ($user) {
            return !$user->accessible($field);
        })->each(function ($value, $field) use (&$user) {
            $user->{$field} = $value;
        });
        $savedUser = $this->Users->save($user);
        return $savedUser;
    }

    /**
     * Delete an specific user and associated social accounts
     *
     * @return void
     */
    public function deleteUser()
    {
        $username = Hash::get($this->args, 0);
        if (empty($username)) {
            $this->error(__d('Users', 'Please enter a username.'));
        }
        $user = $this->Users->find()->where(['Users.username' => $username])->first();
        $deleteAccounts = $this->Users->SocialAccounts->deleteAll(['user_id' => $user->id]);
        $deleteUser = $this->Users->delete($user);
        if ($deleteAccounts && $deleteUser) {
            $this->out(__d('Users', 'The user {0} was deleted successfully', $username));
        } else {
            $this->error(__d('Users', 'The user {0} was not deleted. Please try again', $username));
        }
    }

    //add filters LIKE in username and email to some tasks
    // --force to ignore "you are about to do X to Y users"
}