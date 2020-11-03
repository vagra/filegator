<?php

/*
 * This file is part of the FileGator package.
 *
 * (c) Milos Stojanovic <alcalbg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 */

namespace Filegator\Controllers;

use Filegator\Config\Config;
use Filegator\Kernel\Request;
use Filegator\Kernel\Response;
use Filegator\Services\Auth\AuthInterface;
use Filegator\Services\Auth\User;
use Filegator\Services\Storage\Filesystem;
use Filegator\Services\Logger\LoggerInterface;
use Rakit\Validation\Validator;

class AuthController
{
    protected $config;

    protected $logger;

    protected $auth;

    protected $storage;

    public function __construct(Config $config, LoggerInterface $logger, AuthInterface $auth, Filesystem $storage)
    {
        $this->config = $config;

        $this->logger = $logger;

        $this->auth = $auth;

        $this->storage = $storage;
    }

    public function register(User $user, Request $request, Response $response, Validator $validator)
    {
        if (!$this->config->get('frontend_config.allow_register')) {
            return $response->json('Does not allow user register', 422);
        }

        $validation = $validator->validate($request->all(), [
            'username' => 'required|alpha_dash|between:4,16',
            'password' => 'required|between:6,16',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();

            return $response->json($errors->firstOfAll(), 422);
        }

        if ($this->auth->find($request->input('username'))) {
            return $response->json(['username' => 'Username already taken'], 422);
        }

        $username = $request->input('username');
        $password = $request->input('password');
        $homedir = '/'.$username;
        $role = 'user';
        $permissions = [
            'read','write','upload','download','batchdownload','zip','convert'
        ];

        try {
            $user->setName($username);
            $user->setUsername($username);
            $user->setHomedir($homedir);
            $user->setRole($role);
            $user->setPermissions($permissions);
            $ret = $this->auth->add($user, $password);

            if (!$this->storage->fileExists($homedir)
            || !$this->storage->isDir($homedir)) {
                $this->storage->createDir('/', $homedir);
            }
        } catch (\Exception $e) {
            return $response->json($e->getMessage(), 422);
        }

        $this->logger->log("{$username} registerd from IP ".$request->getClientIp());

        return $response->json(true);
    }

    public function login(Request $request, Response $response, AuthInterface $auth)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        if ($auth->authenticate($username, $password)) {
            $this->logger->log("Logged in {$username} from IP ".$request->getClientIp());

            return $response->json($auth->user());
        }

        $this->logger->log("Login failed for {$username} from IP ".$request->getClientIp());

        return $response->json('Login failed, please try again', 422);
    }

    public function logout(Response $response, AuthInterface $auth)
    {
        return $response->json($auth->forget());
    }

    public function getUser(Response $response, AuthInterface $auth)
    {
        $user = $auth->user() ?: $auth->getGuest();

        return $response->json($user);
    }

    public function changePassword(Request $request, Response $response, AuthInterface $auth, Validator $validator)
    {
        $validator->setMessage('required', 'This field is required');
        $validation = $validator->validate($request->all(), [
            'newname' => 'between:2,16',
            'oldpassword' => 'required',
            'newpassword' => 'between:6,16',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();

            return $response->json($errors->firstOfAll(), 422);
        }

        if (! $auth->authenticate($auth->user()->getUsername(), $request->input('oldpassword'))) {
            return $response->json(['oldpassword' => 'Wrong password'], 422);
        }

        return $response->json($auth->update($auth->user()->getUsername(), $auth->user(), $request->input('newpassword'), $request->input('newname')));
    }
}
